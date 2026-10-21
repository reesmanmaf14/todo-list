<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAccessTest extends TestCase
{
    use RefreshDatabase;

    // --- Guests (not logged in) ---

    public function test_guest_is_redirected_to_login_from_task_pages(): void
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->post(route('tasks.store'), ['title' => 'Sneaky'])->assertRedirect(route('login'));
        $this->put(route('tasks.update', $task), ['title' => 'Sneaky', 'status' => 'Pending'])->assertRedirect(route('login'));
        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('login'));

        $this->assertDatabaseMissing('tasks', ['title' => 'Sneaky']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_guest_ajax_requests_get_401(): void
    {
        // The page's JavaScript turns a 401 into "Your session has expired" with a Refresh button
        $task = Task::factory()->create();

        $this->postJson(route('tasks.store'), ['title' => 'Sneaky'])->assertUnauthorized();
        $this->putJson(route('tasks.update', $task), ['title' => 'Sneaky', 'status' => 'Pending'])->assertUnauthorized();
        $this->deleteJson(route('tasks.destroy', $task))->assertUnauthorized();
    }

    // --- Each user only sees and changes their own tasks ---

    public function test_task_list_shows_only_the_users_own_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['title' => 'My own task']);
        Task::factory()->create(['title' => 'Someone elses task']);
        Task::factory()->create(['title' => 'Unclaimed old task', 'user_id' => null]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('My own task')
            ->assertDontSee('Someone elses task')
            ->assertDontSee('Unclaimed old task');
    }

    public function test_new_task_belongs_to_logged_in_user_even_if_request_sends_another_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->postJson(route('tasks.store'), [
            'title' => 'Mine',
            'user_id' => $otherUser->id,
        ])->assertCreated();

        $this->assertDatabaseHas('tasks', ['title' => 'Mine', 'user_id' => $user->id]);
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $task = Task::factory()->create(['title' => 'Not yours']);

        $this->actingAs(User::factory()->create())
            ->putJson(route('tasks.update', $task), ['title' => 'Hacked', 'status' => 'Completed'])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Not yours', 'status' => 'Pending']);
    }

    public function test_user_cannot_delete_another_users_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_update_cannot_change_task_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->actingAs($user)->putJson(route('tasks.update', $task), [
            'title' => 'Still mine',
            'status' => 'Pending',
            'user_id' => $otherUser->id,
        ])->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Still mine', 'user_id' => $user->id]);
    }

    public function test_regular_form_requests_cannot_change_another_users_task(): void
    {
        // Same protection as the AJAX tests above, for submissions without JavaScript
        $task = Task::factory()->create(['title' => 'Not yours']);

        $this->actingAs(User::factory()->create());

        $this->put(route('tasks.update', $task), ['title' => 'Hacked', 'status' => 'Completed'])
            ->assertForbidden();
        $this->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Not yours', 'status' => 'Pending']);
    }

    public function test_deleting_an_account_deletes_its_tasks(): void
    {
        $task = Task::factory()->create();

        $task->user->delete();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    // --- php artisan tasks:claim (tasks from before login existed) ---

    public function test_claim_command_assigns_unowned_tasks_to_the_user(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        $oldTask = Task::factory()->create(['user_id' => null]);
        $otherUsersTask = Task::factory()->create();

        $this->artisan('tasks:claim', ['email' => 'me@example.com'])
            ->expectsOutput('Assigned 1 task(s) to me@example.com.')
            ->assertSuccessful();

        $this->assertSame($user->id, $oldTask->fresh()->user_id);
        $this->assertNotSame($user->id, $otherUsersTask->fresh()->user_id);
    }

    public function test_claim_command_fails_for_unknown_email(): void
    {
        $this->artisan('tasks:claim', ['email' => 'nobody@example.com'])
            ->assertFailed();
    }
}
