<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->user = User::factory()->create()->assignRole('user');
    }

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'user',
        ], $overrides);
    }

    // --- Admin ---

    public function test_admin_sees_the_add_user_form_and_navbar_link(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Add User')
            ->assertSee('href="'.route('admin.users.index').'"', false)
            ->assertSee('<option value="admin"', false)
            ->assertSee('<option value="user"', false);
    }

    public function test_admin_can_add_a_regular_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->validInput())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User New Person was created as user.');

        $created = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertTrue($created->hasRole('user'));
        $this->assertFalse($created->hasRole('admin'));

        // Stored hashed, never as plain text
        $this->assertNotSame('secret-password', $created->password);
        $this->assertTrue(Hash::check('secret-password', $created->password));
    }

    public function test_admin_can_add_another_admin(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->validInput(['role' => 'admin']))
            ->assertSessionHas('success', 'User New Person was created as admin.');

        $created = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertTrue($created->hasRole('admin'));
        $this->assertTrue($created->can('manage users'));
    }

    public function test_adding_a_user_keeps_the_admin_logged_in(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), $this->validInput());

        $this->assertAuthenticatedAs($this->admin);
    }

    // --- Validation ---

    public function test_missing_fields_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);

        $this->assertSame(2, User::count());
    }

    public function test_duplicate_email_mismatched_password_and_unknown_role_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), $this->validInput([
                'email' => $this->user->email,
                'password_confirmation' => 'something-else',
                'role' => 'superadmin',
            ]))
            ->assertSessionHasErrors(['email', 'password', 'role']);

        $this->assertSame(2, User::count());
    }

    // --- Everyone else ---

    public function test_regular_user_is_forbidden(): void
    {
        $this->actingAs($this->user);

        $this->get(route('admin.users.create'))->assertForbidden();
        $this->post(route('admin.users.store'), $this->validInput(['role' => 'admin']))->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_regular_user_does_not_see_the_navbar_link(): void
    {
        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'), false);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.create'))->assertRedirect(route('login'));
        $this->post(route('admin.users.store'), $this->validInput())->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }
}
