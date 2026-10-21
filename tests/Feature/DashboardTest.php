<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    // --- Login sends each role to its own dashboard ---

    public function test_admin_logging_in_ends_up_at_the_admin_dashboard(): void
    {
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_user_logging_in_ends_up_at_the_user_dashboard(): void
    {
        $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('user.dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->user);
    }

    // --- /dashboard (still used by the other Breeze controllers) ---

    public function test_dashboard_sends_admins_to_the_admin_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_sends_users_to_the_user_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('user.dashboard'));
    }

    // --- Access ---

    public function test_user_gets_a_403_on_the_admin_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_from_both_dashboards(): void
    {
        $this->get(route('user.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    // --- What each dashboard shows ---

    public function test_user_dashboard_shows_their_own_tasks_counts_and_add_task(): void
    {
        Task::factory()->for($this->user)->count(2)->create(['status' => 'Pending']);
        Task::factory()->for($this->user)->create(['title' => 'Ritas done task', 'status' => 'Completed']);
        Task::factory()->for($this->admin)->create(['title' => 'Admins task']);

        $page = $this->actingAs($this->user)->get(route('user.dashboard'))->assertOk();

        $page->assertSee('My Dashboard')
            ->assertSee('data-count="total">3<', false)
            ->assertSee('data-count="pending">2<', false)
            ->assertSee('data-count="completed">1<', false)
            ->assertDontSee('data-count="users"', false)
            ->assertSee('id="addTaskForm"', false)
            ->assertSee('Ritas done task')
            ->assertDontSee('Admins task')
            ->assertDontSee('id="addTaskUser"', false);   // no "Assign to" for regular users
    }

    public function test_admin_dashboard_shows_every_task_totals_and_add_task_with_assign_to(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Ritas task', 'status' => 'Pending']);
        Task::factory()->for($this->admin)->create(['status' => 'Completed']);

        $page = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        $page->assertSee('Admin Dashboard')
            ->assertSee('data-count="users">2<', false)
            ->assertSee('data-count="total">2<', false)
            ->assertSee('data-count="pending">1<', false)
            ->assertSee('data-count="completed">1<', false)
            ->assertSee('id="addTaskForm"', false)
            ->assertSee('id="addTaskUser"', false)
            ->assertSeeInOrder(['Ritas task', 'Rita Regular']);
    }

    public function test_navbar_has_no_separate_dashboard_link_and_the_logo_goes_to_the_dashboard(): void
    {
        $page = $this->actingAs($this->user)->get(route('user.dashboard'))->assertOk();

        $page->assertDontSee('bi-speedometer2', false)
            ->assertSee('class="navbar-brand" href="'.route('dashboard').'"', false);
    }

    public function test_task_page_has_no_count_cards(): void
    {
        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('My Tasks')
            ->assertDontSee('data-count="total">', false);   // the page's script mentions data-count, so match the card itself
    }
}
