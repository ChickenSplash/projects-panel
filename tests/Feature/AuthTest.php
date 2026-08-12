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

    public function test_a_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
