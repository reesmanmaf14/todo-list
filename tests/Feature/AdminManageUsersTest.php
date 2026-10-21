<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Manage Users: the list, Edit User (details, role, password) and Delete User
class AdminManageUsersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Ada Admin', 'email' => 'ada@example.com'])->assignRole('admin');
        $this->user = User::factory()->create(['name' => 'Uma User', 'email' => 'uma@example.com'])->assignRole('user');
    }

    private function editInput(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->user->name,
            'email' => $this->user->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'user',
        ], $overrides);
    }

    // --- Users list ---

    public function test_admin_sees_every_user_with_role_and_task_counts(): void
    {
        Task::factory()->count(2)->for($this->user)->create(['status' => 'Pending']);
        Task::factory()->for($this->user)->create(['status' => 'Completed']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Ada Admin')
            ->assertSee('uma@example.com')
            ->assertSee('class="badge role-admin"', false)
            ->assertSee('class="badge role-user"', false)
            ->assertSee('2 pending')
            ->assertSee('1 completed')
            ->assertSee('href="'.route('admin.users.create').'"', false)
            ->assertSee('href="'.route('tasks.index').'" class="btn btn-secondary"', false)
            ->assertSee('Back to Tasks')
            ->assertSee('href="'.route('admin.users.edit', $this->user).'#delete"', false);
    }

    // --- Popups on the list ---

    public function test_list_has_an_add_user_popup_and_an_edit_popup_per_user(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('id="addUserModal"', false)
            ->assertSee('action="'.route('admin.users.store').'"', false)
            ->assertSee('id="editUserModal-'.$this->user->id.'"', false)
            ->assertSee('id="editUserModal-'.$this->admin->id.'"', false)
            ->assertSee('action="'.route('admin.users.update', $this->user).'"', false)
            // Labels point at their fields
            ->assertSee('for="addUserEmail"', false)
            ->assertSee('id="addUserEmail"', false)
            // No Delete button for yourself
            ->assertDontSee('href="'.route('admin.users.edit', $this->admin).'#delete"', false);
    }

    public function test_failed_add_user_popup_reopens_with_its_errors(): void
    {
        $page = $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->post(route('admin.users.store'), ['form_type' => 'create-user', 'name' => 'Typed Name'])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("getElementById(\"addUserModal\")", $page);
        $this->assertStringContainsString('value="Typed Name"', $page);
        $this->assertStringContainsString('The email field is required.', $page);
    }

    public function test_failed_edit_user_popup_reopens_only_that_popup(): void
    {
        $page = $this->actingAs($this->admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->put(route('admin.users.update', $this->user), $this->editInput([
                'form_type' => 'edit-user',
                'user_id' => $this->user->id,
                'email' => 'ada@example.com',   // taken by the admin
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('getElementById("editUserModal-'.$this->user->id.'")', $page);
        $this->assertSame(1, substr_count($page, 'The email has already been taken.'));
        $this->assertSame('uma@example.com', $this->user->refresh()->email);
    }

    public function test_list_can_be_searched_and_filtered_by_role(): void
    {
        $this->actingAs($this->admin);

        // Rows are matched by data-user-id: the admin's own name is also in the navbar account menu
        $adminRow = 'data-user-id="'.$this->admin->id.'"';
        $userRow = 'data-user-id="'.$this->user->id.'"';

        $this->get(route('admin.users.index', ['search' => 'uma@']))
            ->assertSee($userRow, false)
            ->assertDontSee($adminRow, false);

        $this->get(route('admin.users.index', ['role' => 'admin']))
            ->assertSee($adminRow, false)
            ->assertDontSee($userRow, false);

        $this->get(route('admin.users.index', ['search' => 'nobody-here']))
            ->assertSee('No users match your search.');

        // An unknown role in the URL is ignored instead of failing
        $this->get(route('admin.users.index', ['role' => 'superadmin']))
            ->assertOk()
            ->assertSee('Uma User');
    }

    public function test_list_is_paginated_and_keeps_the_filters(): void
    {
        User::factory()->count(20)->create()->each->assignRole('user');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'user']))
            ->assertOk()
            ->assertSee('role=user&amp;page=2', false);
    }

    // --- Edit ---

    public function test_admin_can_change_name_email_and_role(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->user), $this->editInput([
                'name' => 'Uma Updated',
                'email' => 'uma.new@example.com',
                'role' => 'admin',
            ]))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User Uma Updated was updated.');

        $this->user->refresh();

        $this->assertSame('Uma Updated', $this->user->name);
        $this->assertSame('uma.new@example.com', $this->user->email);
        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertFalse($this->user->hasRole('user'));
    }

    public function test_empty_password_keeps_the_old_one_and_a_new_one_replaces_it(): void
    {
        $oldHash = $this->user->password;

        $this->actingAs($this->admin)->put(route('admin.users.update', $this->user), $this->editInput());
        $this->assertSame($oldHash, $this->user->refresh()->password);

        $this->actingAs($this->admin)->put(route('admin.users.update', $this->user), $this->editInput([
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]));
        $this->assertTrue(Hash::check('brand-new-password', $this->user->refresh()->password));
    }

    public function test_edit_validation_errors(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->user), $this->editInput([
                'name' => '',
                'email' => 'ada@example.com',          // taken by the admin
                'password' => 'brand-new-password',
                'password_confirmation' => 'different',
                'role' => 'superadmin',
            ]))
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);

        // Keeping your own email is not a duplicate
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->user), $this->editInput())
            ->assertSessionHasNoErrors();
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.users.edit', $this->admin))
            ->put(route('admin.users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.edit', $this->admin))
            ->assertSessionHasErrors('role');

        $this->assertTrue($this->admin->refresh()->hasRole('admin'));
    }

    // --- Delete ---

    public function test_deleting_a_user_also_deletes_their_tasks(): void
    {
        Task::factory()->count(2)->for($this->user)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User Uma User and their tasks were deleted.');

        $this->assertModelMissing($this->user);
        $this->assertSame(0, Task::count());
    }

    public function test_tasks_can_be_moved_to_another_user_before_deleting(): void
    {
        $receiver = User::factory()->create(['name' => 'Rae Receiver'])->assignRole('user');
        Task::factory()->count(3)->for($this->user)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->user), ['transfer_to' => $receiver->id])
            ->assertSessionHas('success', 'User Uma User was deleted. 3 tasks moved to Rae Receiver.');

        $this->assertModelMissing($this->user);
        $this->assertSame(3, $receiver->tasks()->count());
    }

    public function test_tasks_cannot_be_moved_to_the_deleted_user_or_a_missing_user(): void
    {
        Task::factory()->for($this->user)->create();

        $this->actingAs($this->admin);

        $this->delete(route('admin.users.destroy', $this->user), ['transfer_to' => $this->user->id])
            ->assertSessionHasErrors('transfer_to');
        $this->delete(route('admin.users.destroy', $this->user), ['transfer_to' => 999999])
            ->assertSessionHasErrors('transfer_to');

        $this->assertModelExists($this->user);
        $this->assertSame(1, Task::count());
    }

    public function test_admin_cannot_delete_their_own_account_here(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertRedirect(route('admin.users.edit', $this->admin))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($this->admin);

        // And the page offers no Delete button for yourself
        $this->get(route('admin.users.edit', $this->admin))
            ->assertOk()
            ->assertDontSee('id="deleteUserModal"', false);
    }

    public function test_edit_page_shows_the_task_count_and_move_options(): void
    {
        Task::factory()->count(2)->for($this->user)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $this->user))
            ->assertOk()
            ->assertSee('value="uma@example.com"', false)
            ->assertSee('Uma User owns 2 tasks.')
            ->assertSee('Move to Ada Admin (ada@example.com)')
            ->assertDontSee('Move to Uma User');
    }

    // --- Everyone else ---

    public function test_regular_user_is_forbidden_everywhere(): void
    {
        $this->actingAs($this->user);

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.users.edit', $this->admin))->assertForbidden();
        $this->put(route('admin.users.update', $this->user), $this->editInput(['role' => 'admin']))->assertForbidden();
        $this->delete(route('admin.users.destroy', $this->admin))->assertForbidden();

        $this->assertFalse($this->user->refresh()->hasRole('admin'));
        $this->assertModelExists($this->admin);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.users.edit', $this->user))->assertRedirect(route('login'));
        $this->put(route('admin.users.update', $this->user), $this->editInput())->assertRedirect(route('login'));
        $this->delete(route('admin.users.destroy', $this->user))->assertRedirect(route('login'));

        $this->assertModelExists($this->user);
    }
}
