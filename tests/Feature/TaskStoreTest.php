<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Task routes require login
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_ajax_request_creates_task_and_returns_json(): void
    {
        $dueDate = now()->addDay()->format('Y-m-d');

        $response = $this->postJson(route('tasks.store'), [
            'title' => 'Buy milk',
            'description' => 'Two litres',
            'due_date' => $dueDate,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Task created successfully!')
            ->assertJsonPath('task.title', 'Buy milk')
            ->assertJsonPath('task.status', 'Pending')
            ->assertJsonPath('task.due_date', $dueDate)
            ->assertJsonStructure(['row', 'modal']);

        $this->assertDatabaseHas('tasks', ['title' => 'Buy milk', 'status' => 'Pending', 'user_id' => $this->user->id]);
    }

    public function test_ajax_request_with_invalid_data_returns_422_with_errors(): void
    {
        $response = $this->postJson(route('tasks.store'), [
            'title' => '',
            'due_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'due_date']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_regular_form_submission_redirects_with_flash_message(): void
    {
        $response = $this->post(route('tasks.store'), [
            'title' => 'Walk the dog',
        ]);

        $response->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task created successfully!');

        $this->assertDatabaseHas('tasks', ['title' => 'Walk the dog']);
    }

    public function test_regular_form_submission_with_invalid_data_redirects_back_with_errors(): void
    {
        $response = $this->from(route('tasks.index'))->post(route('tasks.store'), [
            'title' => '',
        ]);

        $response->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_index_page_renders_with_and_without_tasks(): void
    {
        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('No tasks yet. Add your first task!');

        $this->post(route('tasks.store'), ['title' => 'Existing task']);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Existing task');
    }
}
