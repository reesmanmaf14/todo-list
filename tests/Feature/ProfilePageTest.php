<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The Profile page uses the same Bootstrap layout as the task list (the forms themselves are tested in ProfileTest)
class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Rita Regular']);
        $this->actingAs($this->user);
    }

    public function test_profile_page_uses_the_bootstrap_layout_with_all_three_forms(): void
    {
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('bootstrap@5.3.3', false)
            ->assertSee('aria-label="Account menu"', false)
            ->assertDontSee('@vite', false)
            ->assertSee('action="'.route('profile.update').'"', false)
            ->assertSee('action="'.route('password.update').'"', false)
            ->assertSee('action="'.route('profile.destroy').'"', false)
            ->assertSee('value="Rita Regular"', false)
            ->assertSee('id="confirmUserDeletionModal"', false);
    }

    public function test_saved_messages_are_shown_after_updates(): void
    {
        $this->patch(route('profile.update'), ['name' => 'Rita R', 'email' => $this->user->email]);
        $this->get(route('profile.edit'))->assertSee('Saved.');

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $this->get(route('profile.edit'))->assertSee('Saved.');
    }

    public function test_password_errors_are_shown_under_the_field(): void
    {
        $this->from(route('profile.edit'))->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->get(route('profile.edit'))
            ->assertSee('The password is incorrect.');
    }

    public function test_wrong_password_on_delete_reopens_the_confirmation_popup(): void
    {
        $this->get(route('profile.edit'))->assertDontSee("getElementById('confirmUserDeletionModal')).show()", false);

        $this->from(route('profile.edit'))->delete(route('profile.destroy'), ['password' => 'wrong-password']);

        $this->get(route('profile.edit'))
            ->assertSee('The password is incorrect.')
            ->assertSee("getElementById('confirmUserDeletionModal')).show()", false);

        $this->assertNotNull($this->user->fresh());
    }
}
