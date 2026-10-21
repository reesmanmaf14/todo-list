<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Ada Admin'])->assignRole('admin');
        $this->user = User::factory()->create(['name' => 'Rita Regular'])->assignRole('user');
    }

    // --- Admin: sees and manages every task ---

    public function test_admin_sees_all_tasks_with_their_owner(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Ritas task']);
        Task::factory()->create(['title' => 'Unclaimed old task', 'user_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('All Tasks')
            ->assertSee('Assigned to')
            ->assertSee('Ritas task')
            ->assertSee('Rita Regular')
            ->assertSee('Unclaimed old task')
            ->assertSee('Unassigned');
    }

    public function test_regular_user_sees_no_admin_extras(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Ritas task']);
        Task::factory()->for($this->admin)->create(['title' => 'Admins task']);

        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('My Tasks')
            ->assertSee('Ritas task')
            ->assertDontSee('Admins task')
            ->assertDontSee('Assigned to')
            ->assertDontSee('<select name="user_id"', false);
    }

    public function test_admin_can_create_a_task_for_another_user(): void
    {
        $this->actingAs($this->admin)->postJson(route('tasks.store'), [
            'title' => 'For Rita',
            'user_id' => $this->user->id,
        ])->assertCreated()
            ->assertJsonPath('task.user_id', $this->user->id)
            ->assertJsonPath('task.user.name', 'Rita Regular');

        $this->assertDatabaseHas('tasks', ['title' => 'For Rita', 'user_id' => $this->user->id]);
    }

    public function test_admin_task_without_a_chosen_user_belongs_to_the_admin(): void
    {
        $this->actingAs($this->admin)->post(route('tasks.store'), ['title' => 'My admin task'])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['title' => 'My admin task', 'user_id' => $this->admin->id]);
    }

    public function test_admin_cannot_assign_to_a_user_that_does_not_exist(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('tasks.store'), ['title' => 'Ghost', 'user_id' => 99999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->assertDatabaseMissing('tasks', ['title' => 'Ghost']);
    }

    public function test_admin_can_edit_and_reassign_any_task(): void
    {
        $task = Task::factory()->for($this->admin)->create();

        $this->actingAs($this->admin)->putJson(route('tasks.update', $task), [
            'title' => 'Now Ritas',
            'status' => 'Pending',
            'user_id' => $this->user->id,
        ])->assertOk()
            ->assertJsonPath('task.user.name', 'Rita Regular');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Now Ritas', 'user_id' => $this->user->id]);
    }

    public function test_admin_status_toggle_keeps_the_owner(): void
    {
        // The Complete/Mark Pending button sends no user_id
        $task = Task::factory()->for($this->user)->create(['due_date' => null]);

        $this->actingAs($this->admin)->putJson(route('tasks.update', $task), [
            'title' => $task->title,
            'status' => 'Completed',
        ])->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'Completed', 'user_id' => $this->user->id]);
    }

    public function test_admin_can_delete_any_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->admin)->deleteJson(route('tasks.destroy', $task))->assertOk();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    // --- Assigned tasks: the owner may only change the status ---

    private function assignTaskToUser(): Task
    {
        $this->actingAs($this->admin)
            ->postJson(route('tasks.store'), ['title' => 'Assigned', 'user_id' => $this->user->id])
            ->assertCreated();

        return Task::where('title', 'Assigned')->firstOrFail();
    }

    public function test_admin_assignment_records_who_assigned_the_task(): void
    {
        $task = $this->assignTaskToUser();

        $this->assertSame($this->admin->id, $task->assigned_by);

        // A task the admin creates for themselves is a normal task
        $this->actingAs($this->admin)->postJson(route('tasks.store'), ['title' => 'Mine', 'user_id' => $this->admin->id]);
        $this->assertNull(Task::where('title', 'Mine')->firstOrFail()->assigned_by);
    }

    public function test_user_can_complete_but_not_edit_or_delete_an_assigned_task(): void
    {
        $task = $this->assignTaskToUser();

        $this->actingAs($this->user);

        $this->patchJson(route('tasks.status', $task), ['status' => 'Completed'])
            ->assertOk()
            ->assertJsonPath('task.status', 'Completed')
            ->assertJsonPath('task.assigner.name', 'Ada Admin');

        $this->putJson(route('tasks.update', $task), ['title' => 'Renamed', 'status' => 'Pending'])->assertForbidden();
        $this->deleteJson(route('tasks.destroy', $task))->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Assigned', 'status' => 'Completed']);
    }

    public function test_status_button_works_without_javascript_on_an_assigned_task(): void
    {
        $task = $this->assignTaskToUser();

        $this->actingAs($this->user)
            ->patch(route('tasks.status', $task), ['status' => 'Completed'])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task updated successfully!');

        $this->assertSame('Completed', $task->fresh()->status);
    }

    public function test_status_route_changes_only_the_status(): void
    {
        $task = $this->assignTaskToUser();

        $this->actingAs($this->user)->patchJson(route('tasks.status', $task), [
            'status' => 'Completed',
            'title' => 'Sneaky rename',
            'user_id' => $this->admin->id,
        ])->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Assigned', 'user_id' => $this->user->id, 'status' => 'Completed']);
    }

    public function test_status_route_rejects_other_users_and_bad_values(): void
    {
        $task = $this->assignTaskToUser();
        $stranger = User::factory()->create()->assignRole('user');

        $this->actingAs($stranger)->patchJson(route('tasks.status', $task), ['status' => 'Completed'])->assertForbidden();
        $this->actingAs($this->user)->patchJson(route('tasks.status', $task), ['status' => 'Done'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertSame('Pending', $task->fresh()->status);
    }

    public function test_assigned_task_shows_no_edit_or_delete_buttons_for_its_owner(): void
    {
        $task = $this->assignTaskToUser();

        $page = $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertDontSee('Assigned by Ada Admin')
            ->assertDontSee('id="editTaskModal-'.$task->id.'"', false)
            ->getContent();

        // Look only inside this task's table row (the page's JavaScript also mentions these data-role names)
        preg_match('/<tr data-task-id="'.$task->id.'".*?<\/tr>/s', $page, $row);

        $this->assertStringContainsString(route('tasks.status', $task), $row[0]);
        $this->assertStringNotContainsString('data-role="edit-button"', $row[0]);
        $this->assertStringNotContainsString('data-role="delete-form"', $row[0]);
    }

    public function test_user_keeps_full_control_of_their_own_tasks(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user);

        $this->get(route('tasks.index'))->assertSee('editTaskModal-'.$task->id, false);
        $this->putJson(route('tasks.update', $task), ['title' => 'Renamed', 'status' => 'Pending'])->assertOk();
        $this->deleteJson(route('tasks.destroy', $task))->assertOk();
    }

    public function test_admin_editing_without_changing_the_owner_does_not_lock_the_task(): void
    {
        // The admin's Edit form always sends the current owner; that must not count as assigning
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->admin)->putJson(route('tasks.update', $task), [
            'title' => 'Typo fixed',
            'status' => 'Pending',
            'user_id' => $this->user->id,
        ])->assertOk();

        $this->assertNull($task->fresh()->assigned_by);
    }

    public function test_admin_reassigning_to_themselves_makes_it_a_normal_task(): void
    {
        $task = $this->assignTaskToUser();

        $this->actingAs($this->admin)->putJson(route('tasks.update', $task), [
            'title' => 'Assigned',
            'status' => 'Pending',
            'user_id' => $this->admin->id,
        ])->assertOk()
            ->assertJsonPath('task.assigner', null);

        $this->assertNull($task->fresh()->assigned_by);
    }

    public function test_user_cannot_touch_the_admins_tasks(): void
    {
        $task = Task::factory()->for($this->admin)->create(['title' => 'Admin only']);

        $this->actingAs($this->user);

        $this->putJson(route('tasks.update', $task), ['title' => 'Hacked', 'status' => 'Completed'])->assertForbidden();
        $this->deleteJson(route('tasks.destroy', $task))->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Admin only']);
    }

    // --- Roles can't be gained through the website ---

    public function test_registration_always_creates_a_regular_user(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $user = User::where('email', 'sneaky@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
    }

    // --- php artisan users:make-admin ---

    public function test_make_admin_command_promotes_an_existing_user(): void
    {
        $this->artisan('users:make-admin', ['email' => $this->user->email])->assertSuccessful();

        $this->assertTrue($this->user->fresh()->hasRole('admin'));
    }

    public function test_make_admin_command_fails_for_unknown_email(): void
    {
        $this->artisan('users:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
