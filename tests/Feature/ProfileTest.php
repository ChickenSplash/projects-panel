<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_the_profile_page(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_the_form_starts_out_holding_the_current_details(): void
    {
        $user = User::factory()->create(['username' => 'taylor', 'email' => 'taylor@example.com']);

        Volt::actingAs($user)
            ->test('profile')
            ->assertSet('username', 'taylor')
            ->assertSet('email', 'taylor@example.com');
    }

    public function test_a_user_can_change_their_username_and_email(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('profile')
            ->set('username', 'nightowl')
            ->set('email', 'nightowl@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'nightowl',
            'email' => 'nightowl@example.com',
        ]);
    }

    public function test_a_username_someone_else_holds_is_refused(): void
    {
        User::factory()->create(['username' => 'taylor']);
        $user = User::factory()->create(['username' => 'nightowl']);

        Volt::actingAs($user)
            ->test('profile')
            ->set('username', 'taylor')
            ->call('save')
            ->assertHasErrors(['username' => 'unique'])
            ->assertSee('Username already taken');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'nightowl']);
    }

    public function test_saving_without_touching_your_own_username_is_fine(): void
    {
        $user = User::factory()->create(['username' => 'taylor']);

        Volt::actingAs($user)
            ->test('profile')
            ->set('email', 'moved@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'taylor', 'email' => 'moved@example.com']);
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('profile')
            ->set('current_password', 'password')
            ->set('password', 'a-brand-new-password')
            ->set('password_confirmation', 'a-brand-new-password')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('a-brand-new-password', $user->refresh()->password));
    }

    public function test_changing_the_password_needs_the_current_one(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('profile')
            ->set('current_password', 'not-the-password')
            ->set('password', 'a-brand-new-password')
            ->set('password_confirmation', 'a-brand-new-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_deleting_the_account_takes_its_projects_and_the_session_with_it(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Volt::actingAs($user)
            ->test('profile')
            ->call('delete')
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
