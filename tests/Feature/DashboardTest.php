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

    public function test_login_goes_to_the_dashboard_even_after_being_bounced_from_the_task_page(): void
    {
        // Logged out, opening /tasks sends you to login (Laravel remembers /tasks as the "intended" page)
        $this->get(route('tasks.index'))->assertRedirect(route('login'));

        $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('user.dashboard'));
    }

    public function test_logging_out_and_back_in_goes_to_the_dashboard(): void
    {
        $this->actingAs($this->admin)->post('/logout')->assertRedirect('/');

        // Logout lands on "/", which (as a guest) ends at the login page
        $this->get('/')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->post('/login', ['email' => $this->admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
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

    // --- Each dashboard is the task page for that role ---

    public function test_user_dashboard_is_the_my_tasks_page(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Ritas task']);
        Task::factory()->for($this->admin)->create(['title' => 'Admins task']);

        $this->actingAs($this->user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('My Tasks')
            ->assertSee('id="addTaskForm"', false)
            ->assertSee('Ritas task')
            ->assertDontSee('Admins task')
            ->assertDontSee('id="addTaskUser"', false);   // no "Assign to" for regular users
    }

    public function test_admin_dashboard_is_the_all_tasks_page(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Ritas task']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('All Tasks')
            ->assertSee('id="addTaskForm"', false)
            ->assertSee('id="addTaskUser"', false)
            ->assertSeeInOrder(['Ritas task', 'Rita Regular']);
    }

    public function test_tasks_page_redirects_to_the_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_saving_a_task_without_javascript_shows_its_message_on_the_dashboard(): void
    {
        // tasks.store redirects to /tasks -> /dashboard -> /user/dashboard: the flash message must survive all three
        $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('tasks.store'), ['title' => 'Plain form task'])
            ->assertOk()
            ->assertSee('My Tasks')
            ->assertSee('Task created successfully!')
            ->assertSee('Plain form task');
    }

    public function test_the_logo_goes_to_the_dashboard(): void
    {
        $this->actingAs($this->user)
            ->get(route('user.dashboard'))
            ->assertSee('class="navbar-brand" href="'.route('dashboard').'"', false);
    }
}
