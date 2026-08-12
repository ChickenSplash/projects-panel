<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_see_every_project(): void
    {
        $project = Project::factory()->create(['title' => 'Someone elses project']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Someone elses project')
            ->assertSee($project->user->name);
    }

    public function test_guests_cannot_reach_the_posting_page(): void
    {
        $this->get('/my-projects')->assertRedirect('/login');
    }

    public function test_a_user_can_post_a_project(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('my-projects')
            ->set('title', 'My project')
            ->set('url', 'https://example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'title' => 'My project',
            'url' => 'https://example.com',
        ]);
    }

    public function test_a_project_needs_a_title_and_a_valid_link(): void
    {
        Volt::actingAs(User::factory()->create())
            ->test('my-projects')
            ->set('title', '')
            ->set('url', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'url' => 'url']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_user_only_sees_and_deletes_their_own_projects(): void
    {
        $user = User::factory()->create();
        $mine = Project::factory()->for($user)->create(['title' => 'Mine']);
        $theirs = Project::factory()->create(['title' => 'Theirs']);

        Volt::actingAs($user)
            ->test('my-projects')
            ->assertSee('Mine')
            ->assertDontSee('Theirs')
            ->call('delete', $theirs->id)
            ->call('delete', $mine->id);

        $this->assertDatabaseMissing('projects', ['id' => $mine->id]);
        $this->assertDatabaseHas('projects', ['id' => $theirs->id]);
    }
}
