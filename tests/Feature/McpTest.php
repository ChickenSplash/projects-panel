<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProjectsServer;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    private const CALLBACK = 'https://claude.ai/api/mcp/auth_callback';

    protected function setUp(): void
    {
        parent::setUp();

        // Passport signs its tokens with a keypair that is generated rather than committed,
        // so a fresh checkout has none. Cheap to make, and gitignored, so make one when missing.
        if (! file_exists(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys');
        }
    }

    /**
     * Register a client the way Claude does: no secret, so the code is bound to a PKCE
     * verifier instead.
     *
     * @return array<string, mixed>
     */
    private function registerClient(): array
    {
        return $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => [self::CALLBACK],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_method' => 'none',
        ])->assertCreated()->json();
    }

    /**
     * The authorization URL a client without a secret has to send: the challenge is not
     * optional for those, and leaving it off is a 400 rather than a consent screen.
     *
     * @param  array<string, mixed>  $client
     */
    private function authorizeUrl(array $client, string $verifier): string
    {
        return '/oauth/authorize?'.http_build_query([
            'client_id' => $client['client_id'],
            'redirect_uri' => self::CALLBACK,
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'code_challenge' => strtr(rtrim(base64_encode(hash('sha256', $verifier, true)), '='), '+/', '-_'),
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Walk the rest of the flow: approve the client in the browser, then swap the code
     * for a token.
     */
    private function accessTokenFor(User $user): string
    {
        $client = $this->registerClient();
        $verifier = Str::random(64);

        // Named explicitly: an earlier request through another guard leaves it the default,
        // and the consent screen is a session flow.
        $this->actingAs($user, 'web')
            ->get($this->authorizeUrl($client, $verifier))
            ->assertOk()
            ->assertSee('Claude');

        $approved = $this->post('/oauth/authorize', ['auth_token' => session('authToken')]);

        $approved->assertRedirect();
        parse_str((string) parse_url((string) $approved->headers->get('Location'), PHP_URL_QUERY), $callback);
        $this->assertArrayHasKey('code', $callback, 'The approval did not come back with an authorization code.');

        return $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client['client_id'],
            'redirect_uri' => self::CALLBACK,
            'code_verifier' => $verifier,
            'code' => $callback['code'],
        ])->assertOk()->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function rpc(?string $token, string $method, array $params = []): TestResponse
    {
        $body = array_filter([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params === [] ? null : $params,
        ], fn ($value) => $value !== null);

        return $this->withToken((string) $token)->postJson('/mcp/projects', $body);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function callTool(?string $token, array $arguments): TestResponse
    {
        return $this->rpc($token, 'tools/call', ['name' => 'create-project', 'arguments' => $arguments]);
    }

    public function test_the_tool_posts_a_project_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        ProjectsServer::actingAs($user)
            ->tool(CreateProjectTool::class, ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('My Project');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'title' => 'My Project',
            'url' => 'https://example.com',
        ]);
    }

    public function test_the_tool_needs_a_title_and_a_full_url(): void
    {
        // Validation comes back as a tool result rather than an exception, so the model can
        // read what it got wrong and try again instead of the connector simply failing.
        ProjectsServer::actingAs(User::factory()->create())
            ->tool(CreateProjectTool::class, ['title' => '', 'link' => 'example.com'])
            ->assertHasErrors(['Send a title', 'including the scheme']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_listing_reads_back_the_shelf_newest_first(): void
    {
        $user = User::factory()->create();
        $older = Project::factory()->for($user)->create(['created_at' => now()->subDay()]);
        $newer = Project::factory()->for($user)->create(['created_at' => now()]);

        ProjectsServer::actingAs($user)
            ->tool(ListProjectsTool::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent([
                'total' => 2,
                'showing' => 2,
                'projects' => [
                    ['id' => $newer->id, 'title' => $newer->title, 'link' => $newer->url, 'posted_at' => $newer->created_at->toIso8601String()],
                    ['id' => $older->id, 'title' => $older->title, 'link' => $older->url, 'posted_at' => $older->created_at->toIso8601String()],
                ],
            ]);
    }

    public function test_listing_shows_nobody_elses_shelf(): void
    {
        $user = User::factory()->create();
        $mine = Project::factory()->for($user)->create();
        Project::factory()->for(User::factory())->create(['title' => 'Somebody Elses']);

        ProjectsServer::actingAs($user)
            ->tool(ListProjectsTool::class)
            ->assertOk()
            ->assertDontSee('Somebody Elses')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('total', 1)
                ->where('showing', 1)
                ->where('projects.0.id', $mine->id)
                ->has('projects', 1)
                ->etc());
    }

    public function test_listing_caps_what_it_reads_out_and_still_counts_the_rest(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->count(4)->create();

        ProjectsServer::actingAs($user)
            ->tool(ListProjectsTool::class, ['limit' => 2])
            ->assertOk()
            // The total is the whole shelf rather than the page, so the model can tell there
            // is more of it and ask for the rest.
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('total', 4)
                ->where('showing', 2)
                ->has('projects', 2));
    }

    public function test_listing_refuses_a_limit_it_cannot_honour(): void
    {
        ProjectsServer::actingAs(User::factory()->create())
            ->tool(ListProjectsTool::class, ['limit' => 500])
            ->assertHasErrors(['at most 100']);
    }

    public function test_listing_an_empty_shelf_is_an_answer_rather_than_an_error(): void
    {
        ProjectsServer::actingAs(User::factory()->create())
            ->tool(ListProjectsTool::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(['total' => 0, 'showing' => 0, 'projects' => []]);
    }

    public function test_deleting_takes_a_project_off_the_shelf(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['title' => 'My Project']);

        ProjectsServer::actingAs($user)
            ->tool(DeleteProjectTool::class, ['id' => $project->id])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('My Project');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_deleting_cannot_reach_somebody_elses_project(): void
    {
        $project = Project::factory()->for(User::factory())->create();

        ProjectsServer::actingAs(User::factory()->create())
            ->tool(DeleteProjectTool::class, ['id' => $project->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_deleting_says_the_same_thing_about_a_stranger_as_about_a_ghost(): void
    {
        // Telling the two apart would let a connector work out what other people have posted
        // from the way it is turned down, so both get the same sentence.
        $user = User::factory()->create();
        $theirs = Project::factory()->for(User::factory())->create();
        $ghost = $theirs->id + 1000;

        ProjectsServer::actingAs($user)
            ->tool(DeleteProjectTool::class, ['id' => $theirs->id])
            ->assertHasErrors(["No project with id {$theirs->id} on this account's shelf"]);

        ProjectsServer::actingAs($user)
            ->tool(DeleteProjectTool::class, ['id' => $ghost])
            ->assertHasErrors(["No project with id {$ghost} on this account's shelf"]);
    }

    public function test_deleting_needs_an_id(): void
    {
        ProjectsServer::actingAs(User::factory()->create())
            ->tool(DeleteProjectTool::class, [])
            ->assertHasErrors(['Send the id']);
    }

    public function test_the_endpoint_turns_a_stranger_away_and_says_where_to_authenticate(): void
    {
        $response = $this->postJson('/mcp/projects', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);

        $response->assertUnauthorized();

        // The challenge is how a client discovers there is an OAuth server to talk to at all.
        $this->assertStringContainsString(
            url('/.well-known/oauth-protected-resource/mcp/projects'),
            (string) $response->headers->get('WWW-Authenticate'),
        );
    }

    public function test_the_discovery_documents_point_at_this_app(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJson([
                'issuer' => url('/'),
                'authorization_endpoint' => url('/oauth/authorize'),
                'token_endpoint' => url('/oauth/token'),
                'registration_endpoint' => url('/oauth/register'),
                'code_challenge_methods_supported' => ['S256'],
            ]);

        $this->getJson('/.well-known/oauth-protected-resource/mcp/projects')
            ->assertOk()
            ->assertJson([
                'resource' => url('/mcp/projects'),
                'authorization_servers' => [url('/')],
            ]);
    }

    public function test_an_oauth_token_posts_a_project_for_the_user_who_approved_it(): void
    {
        $user = User::factory()->create();

        $response = $this->callTool($this->accessTokenFor($user), [
            'title' => 'My Project',
            'link' => 'https://example.com',
        ]);

        // A JSON-RPC error is still HTTP 200, so the status alone proves nothing.
        $response->assertOk()->assertJsonMissingPath('error');
        $this->assertStringContainsString('My Project', (string) $response->json('result.content.0.text'));

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'title' => 'My Project',
            'url' => 'https://example.com',
        ]);
    }

    public function test_the_tools_are_advertised_under_the_names_the_model_is_told_to_call(): void
    {
        $response = $this->rpc($this->accessTokenFor(User::factory()->create()), 'tools/list');

        $response->assertOk();

        $tools = collect($response->json('result.tools'))->keyBy('name');

        $this->assertSame(
            ['create-project', 'list-projects', 'delete-project'],
            $tools->keys()->all(),
        );

        // Without a description a tool is listed but never chosen.
        $tools->each(fn (array $tool) => $this->assertNotEmpty($tool['description'], "[{$tool['name']}] has no description."));

        // The hints a client reads before deciding whether to ask the person first.
        $this->assertTrue($tools['list-projects']['annotations']['readOnlyHint']);
        $this->assertTrue($tools['delete-project']['annotations']['destructiveHint']);
    }

    public function test_a_sanctum_token_is_not_a_way_into_the_mcp_endpoint(): void
    {
        // The two token systems sit side by side; neither should open the other's door.
        $user = User::factory()->create();

        $this->callTool($user->createToken('api')->plainTextToken, [
            'title' => 'My Project',
            'link' => 'https://example.com',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_an_oauth_token_is_not_a_way_into_the_sanctum_endpoint(): void
    {
        $this->withToken($this->accessTokenFor(User::factory()->create()))
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_a_browser_session_is_not_a_way_into_the_mcp_endpoint(): void
    {
        // The route carries no CSRF token, so a session must not be enough to post with.
        $this->actingAs(User::factory()->create(), 'web')
            ->postJson('/mcp/projects', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => ['name' => 'create-project', 'arguments' => [
                    'title' => 'My Project',
                    'link' => 'https://example.com',
                ]],
            ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_the_consent_screen_needs_a_logged_in_account(): void
    {
        $this->get($this->authorizeUrl($this->registerClient(), Str::random(64)))
            ->assertRedirect('/login');
    }

    public function test_a_public_client_cannot_skip_pkce(): void
    {
        // Without a secret, the challenge is the only thing tying the code to the client
        // that asked for it, so Passport must refuse the request rather than fall back.
        $client = $this->registerClient();

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client['client_id'],
                'redirect_uri' => self::CALLBACK,
                'response_type' => 'code',
                'scope' => 'mcp:use',
            ]))->assertStatus(400);
    }

    public function test_denying_the_consent_screen_hands_back_no_token(): void
    {
        $client = $this->registerClient();

        $this->actingAs(User::factory()->create(), 'web')
            ->get($this->authorizeUrl($client, Str::random(64)))
            ->assertOk();

        $denied = $this->delete('/oauth/authorize', ['auth_token' => session('authToken')]);

        $denied->assertRedirect();
        parse_str((string) parse_url((string) $denied->headers->get('Location'), PHP_URL_QUERY), $callback);

        $this->assertArrayNotHasKey('code', $callback);
        $this->assertSame('access_denied', $callback['error'] ?? null);
    }

    public function test_the_sanctum_endpoint_is_untouched(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/projects', ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertCreated();

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_acting_as_a_user_over_the_api_guard_reaches_the_tool(): void
    {
        // The shortcut the rest of the suite would use if it did not want the whole flow.
        $user = Passport::actingAs(User::factory()->create(), ['mcp:use']);

        ProjectsServer::actingAs($user, 'api')
            ->tool(CreateProjectTool::class, ['title' => 'My Project', 'link' => 'https://example.com'])
            ->assertOk();

        $this->assertDatabaseHas('projects', ['user_id' => $user->id, 'title' => 'My Project']);
    }
}
