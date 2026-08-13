<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    public function test_a_token_posts_a_project_for_its_owner(): void
    {
        $user = User::factory()->create();

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertCreated()
            ->assertJson(['title' => 'My Project', 'link' => 'https://example.com']);

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'title' => 'My Project',
            'url' => 'https://example.com',
        ]);
    }

    public function test_the_endpoint_needs_a_valid_token(): void
    {
        $this->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertUnauthorized();

        $this->withToken('not-a-token')
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_posted_project_needs_a_title_and_a_valid_link(): void
    {
        $this->withToken($this->tokenFor(User::factory()->create()))
            ->postJson('/api/projects', ['title' => '', 'link' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'link']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_browser_session_is_not_enough(): void
    {
        // The API routes carry no CSRF token, so the session must not authenticate them.
        $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_revoked_token_stops_working(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        Volt::actingAs($user)->test('api-token')->call('revoke');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($token)
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertUnauthorized();
    }

    public function test_generating_a_token_replaces_the_previous_one(): void
    {
        $user = User::factory()->create();
        $first = $this->tokenFor($user);
        $firstId = $user->tokens()->sole()->id;

        Volt::actingAs($user)
            ->test('api-token')
            ->call('generate')
            ->assertSet('plainTextToken', fn (?string $token) => $token !== null && $token !== $first);

        $this->assertSame(1, $user->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $firstId]);

        $this->withToken($first)
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertUnauthorized();
    }

    public function test_deleting_an_account_takes_its_tokens_with_it(): void
    {
        $user = User::factory()->create();
        $this->tokenFor($user);

        Volt::actingAs($user)->test('profile')->call('delete');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guests_cannot_reach_the_token_page(): void
    {
        $this->get('/api-token')->assertRedirect('/login');
    }
}
