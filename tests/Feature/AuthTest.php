<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_with_their_email_and_password(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Volt::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/my-projects');

        $this->assertAuthenticatedAs($user);
    }

    public function test_logging_in_fails_with_the_wrong_password(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        Volt::test('auth.login')
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_user_can_register(): void
    {
        Volt::test('auth.register')
            ->set('username', 'taylor')
            ->set('email', 'taylor@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/my-projects');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'taylor@example.com']);
    }

    public function test_registering_fails_when_the_username_is_already_taken(): void
    {
        User::factory()->create(['username' => 'taylor']);

        Volt::test('auth.register')
            ->set('username', 'taylor')
            ->set('email', 'someone-else@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['username' => 'unique']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_the_taken_username_message_is_the_one_we_wrote(): void
    {
        User::factory()->create(['username' => 'taylor']);

        Volt::test('auth.register')
            ->set('username', 'taylor')
            ->set('email', 'someone-else@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertSee('Username already taken');
    }

    public function test_the_header_offers_the_username_as_a_menu_once_logged_in(): void
    {
        $this->actingAs(User::factory()->create(['username' => 'taylor']))
            ->get('/')
            ->assertOk()
            ->assertSee('taylor')
            ->assertSee(route('profile'))
            ->assertSee('Log out');
    }

    public function test_a_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
