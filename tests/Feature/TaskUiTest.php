<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // The <tr> of one task, so assertions don't match other rows or the page's JavaScript
    private function rowHtml(Task $task): string
    {
        $page = $this->get(route('tasks.index'))->assertOk()->getContent();

        preg_match('/<tr data-task-id="'.$task->id.'".*?<\/tr>/s', $page, $row);

        return $row[0];
    }

    // --- Due-date badges (due_state) ---

    public function test_due_state_is_worked_out_from_status_and_due_date(): void
    {
        $this->assertSame('overdue', Task::factory()->make(['due_date' => now()->subDay()->toDateString()])->due_state);
        $this->assertSame('today', Task::factory()->make(['due_date' => now()->toDateString()])->due_state);
        $this->assertNull(Task::factory()->make(['due_date' => now()->addDay()->toDateString()])->due_state);
        $this->assertNull(Task::factory()->make(['due_date' => null])->due_state);
        $this->assertNull(Task::factory()->make(['due_date' => now()->subDay()->toDateString(), 'status' => 'Completed'])->due_state);
    }

    public function test_overdue_and_due_today_badges_are_shown_on_pending_tasks(): void
    {
        $overdue = Task::factory()->for($this->user)->create(['due_date' => now()->subDays(2)->toDateString()]);
        $today = Task::factory()->for($this->user)->create(['due_date' => now()->toDateString()]);

        $this->assertStringContainsString('Overdue', $this->rowHtml($overdue));
        $this->assertStringContainsString('data-due-state="overdue"', $this->rowHtml($overdue));
        $this->assertStringContainsString('Due today', $this->rowHtml($today));
    }

    public function test_completed_task_has_no_badge_and_is_faded(): void
    {
        $task = Task::factory()->for($this->user)->create([
            'status' => 'Completed',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $row = $this->rowHtml($task);

        $this->assertStringNotContainsString('Overdue', $row);
        $this->assertStringContainsString('class="task-completed"', $row);
        $this->assertStringContainsString('data-status="Completed"', $row);
        $this->assertStringContainsString('Mark Pending', $row);
    }

    public function test_json_responses_include_due_state(): void
    {
        $this->postJson(route('tasks.store'), ['title' => 'Due now', 'due_date' => now()->toDateString()])
            ->assertCreated()
            ->assertJsonPath('task.due_state', 'today');

        $task = Task::where('title', 'Due now')->firstOrFail();

        $this->patchJson(route('tasks.status', $task), ['status' => 'Completed'])
            ->assertOk()
            ->assertJsonPath('task.due_state', null);
    }

    // --- Page structure ---

    public function test_page_has_filter_tabs_icons_and_an_empty_state_button(): void
    {
        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('bootstrap-icons', false)
            ->assertSee('id="taskFilters"', false)
            ->assertSee('data-filter="Pending"', false)
            ->assertSee('data-filter="Completed"', false)
            ->assertSee('data-filter="overdue"', false)
            ->assertSee('No tasks yet. Add your first task!')
            ->assertSee('bi-clipboard-check', false);
    }

    // --- Popups ---

    public function test_delete_asks_in_a_popup_instead_of_the_browser_confirm_box(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('id="deleteTaskModal"', false)
            ->assertSee('Delete this task?')
            ->assertSee('data-role="confirm-button"', false);

        $row = $this->rowHtml($task);

        $this->assertStringContainsString('data-role="delete-form"', $row);
        $this->assertStringNotContainsString('confirm(', $row);
    }

    public function test_add_and_edit_task_labels_point_at_their_fields(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('for="addTaskTitle"', false)
            ->assertSee('id="addTaskTitle"', false)
            ->assertSee('for="editTaskTitle-'.$task->id.'"', false)
            ->assertSee('id="editTaskTitle-'.$task->id.'"', false);
    }
}
