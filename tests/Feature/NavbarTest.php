<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // The <nav> of a page, split into the account dropdown menu and everything else in the bar
    private function navbarParts(string $url): array
    {
        $page = $this->get($url)->assertOk()->getContent();

        preg_match('/<nav.*?<\/nav>/s', $page, $nav);
        preg_match('/<ul class="dropdown-menu.*?<\/ul>/s', $nav[0], $menu);

        return [
            'menu' => $menu[0],
            'bar' => str_replace($menu[0], '', $nav[0]),
        ];
    }

    public function test_name_profile_and_logout_are_inside_the_account_menu(): void
    {
        $user = User::factory()->create(['name' => 'Rita Regular'])->assignRole('user');

        $this->actingAs($user);
        $parts = $this->navbarParts(route('tasks.index'));

        $this->assertStringContainsString('Rita Regular', $parts['menu']);
        $this->assertStringContainsString($user->email, $parts['menu']);
        $this->assertStringContainsString('href="'.route('profile.edit').'"', $parts['menu']);

        // Log out is still a POST form with a CSRF token
        $this->assertStringContainsString('method="POST" action="'.route('logout').'"', $parts['menu']);
        $this->assertStringContainsString('name="_token"', $parts['menu']);

        // Nothing about the user in the bar itself
        $this->assertStringNotContainsString('Rita Regular', $parts['bar']);
        $this->assertStringNotContainsString('Log out', $parts['bar']);
    }

    public function test_manage_users_button_is_for_admins_only_and_highlighted_on_its_pages(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->create()->assignRole('user');

        $this->actingAs($user);
        $this->assertStringNotContainsString(route('admin.users.index'), $this->navbarParts(route('tasks.index'))['bar']);

        $this->actingAs($admin);
        $this->assertStringContainsString('btn-outline-light" href="'.route('admin.users.index').'"', $this->compact($this->navbarParts(route('tasks.index'))['bar']));

        // Solid on every Manage Users page: the list, Add User and Edit User
        foreach ([route('admin.users.index'), route('admin.users.create'), route('admin.users.edit', $user)] as $page) {
            $this->assertStringContainsString('btn-light" href="'.route('admin.users.index').'"', $this->compact($this->navbarParts($page)['bar']));
        }
    }

    public function test_navbar_shows_the_crema_logo_and_name(): void
    {
        $this->actingAs(User::factory()->create()->assignRole('user'));

        $page = $this->get(route('tasks.index'))->assertOk();
        $bar = $this->navbarParts(route('tasks.index'))['bar'];

        $this->assertStringContainsString('class="brand-logo"', $bar);
        $this->assertStringContainsString('Crema', $bar);
        $this->assertStringNotContainsString('My To-Do List', $bar);

        $page->assertSee('<title>Crema</title>', false)
            ->assertSee('favicon.svg', false);
    }

    public function test_logging_out_from_the_menu_works(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    // Collapse whitespace so attributes split over several lines can be matched
    private function compact(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }
}
