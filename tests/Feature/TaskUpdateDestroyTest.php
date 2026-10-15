<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskUpdateDestroyTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(array $attributes = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Buy milk',
            'description' => 'Two litres',
            'status' => 'Pending',
            'due_date' => now()->addDay()->format('Y-m-d'),
        ], $attributes));
    }

    public function test_ajax_edit_updates_task_and_returns_json(): void
    {
        $task = $this->makeTask();
        $newDueDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->putJson(route('tasks.update', $task), [
            'title' => 'Buy oat milk',
            'description' => '',
            'due_date' => $newDueDate,
            'status' => 'Pending',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Task updated successfully!')
            ->assertJsonPath('task.id', $task->id)
            ->assertJsonPath('task.title', 'Buy oat milk')
            ->assertJsonPath('task.description', null)
            ->assertJsonPath('task.due_date', $newDueDate)
            ->assertJsonPath('task.status', 'Pending');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Buy oat milk']);
    }

    public function test_ajax_status_toggle_keeps_overdue_due_date(): void
    {
        // Same payload the Complete button sends: current values + new status
        $pastDate = now()->subDays(5)->format('Y-m-d');
        $task = $this->makeTask(['due_date' => $pastDate]);

        $response = $this->putJson(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $pastDate,
            'status' => 'Completed',
        ]);

        $response->assertOk()
            ->assertJsonPath('task.status', 'Completed')
            ->assertJsonPath('task.due_date', $pastDate);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'Completed']);
    }

    public function test_ajax_edit_with_invalid_data_returns_422_with_errors(): void
    {
        $task = $this->makeTask();

        $response = $this->putJson(route('tasks.update', $task), [
            'title' => '',
            'due_date' => now()->subDay()->format('Y-m-d'),
            'status' => 'Done',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'due_date', 'status']);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Buy milk', 'status' => 'Pending']);
    }

    public function test_regular_edit_submission_redirects_with_flash_message(): void
    {
        $task = $this->makeTask();

        $response = $this->put(route('tasks.update', $task), [
            'title' => 'Buy bread',
            'status' => 'Completed',
        ]);

        $response->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task updated successfully!');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Buy bread', 'status' => 'Completed']);
    }

    public function test_regular_edit_submission_with_invalid_data_redirects_back_with_errors(): void
    {
        $task = $this->makeTask();

        $response = $this->from(route('tasks.index'))->put(route('tasks.update', $task), [
            'title' => '',
            'status' => 'Pending',
        ]);

        $response->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('title');
    }

    public function test_regular_status_toggle_submission_redirects_with_flash_message(): void
    {
        // Same fields the Complete button's form posts when JavaScript is off
        $task = $this->makeTask();

        $this->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date,
            'status' => 'Completed',
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task updated successfully!');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'Completed']);
    }

    public function test_ajax_update_of_missing_task_returns_404(): void
    {
        $this->putJson(route('tasks.update', 999), [
            'title' => 'Ghost',
            'status' => 'Pending',
        ])->assertNotFound();
    }

    public function test_ajax_delete_removes_task_and_returns_json(): void
    {
        $task = $this->makeTask();

        $this->deleteJson(route('tasks.destroy', $task))
            ->assertOk()
            ->assertJsonPath('message', 'Task deleted successfully!');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_ajax_delete_of_missing_task_returns_404(): void
    {
        $this->deleteJson(route('tasks.destroy', 999))->assertNotFound();
    }

    public function test_regular_delete_submission_redirects_with_flash_message(): void
    {
        $task = $this->makeTask();

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task deleted successfully!');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_index_page_includes_hooks_used_by_javascript(): void
    {
        $task = $this->makeTask();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('data-task-id="'.$task->id.'"', false)
            ->assertSee('data-role="edit-form"', false)
            ->assertSee('data-role="toggle-form"', false)
            ->assertSee('data-role="delete-form"', false)
            ->assertSee('jquery-3.7.1.min.js', false)
            ->assertDontSee('js/ajax.js', false)
            ->assertSee('<meta name="csrf-token"', false);
    }
}
