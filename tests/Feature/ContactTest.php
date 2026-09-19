<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'admin@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin_email' => self::ADMIN]);
        Mail::fake();
    }

    /** @return array<string, string> */
    private function validMessage(): array
    {
        return [
            'email' => 'visitor@example.com',
            'subject' => 'Hello',
            'message' => 'I like your site.',
        ];
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => self::ADMIN]);
    }

    private function contactMessage(): Message
    {
        return Message::create(['email' => 'visitor@example.com', 'subject' => 'Hello', 'body' => 'Hi there']);
    }

    public function test_a_message_is_stored_and_emailed_to_the_admin(): void
    {
        $this->postJson('/api/contact', $this->validMessage())
            ->assertCreated()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('messages', [
            'email' => 'visitor@example.com',
            'subject' => 'Hello',
            'body' => 'I like your site.',
            'read_at' => null,
        ]);

        Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail) => $mail->hasTo(self::ADMIN)
            && $mail->hasReplyTo('visitor@example.com')
            && $mail->hasSubject('Portfolio: Hello'));
    }

    public function test_no_email_is_sent_without_an_admin_address(): void
    {
        config(['app.admin_email' => null]);

        $this->postJson('/api/contact', $this->validMessage())->assertCreated();

        $this->assertDatabaseCount('messages', 1);
        Mail::assertNothingSent();
    }

    public function test_a_message_needs_an_email_subject_and_body(): void
    {
        $this->postJson('/api/contact', ['email' => 'not-an-email', 'subject' => '', 'message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'subject', 'message']);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_the_honeypot_quietly_drops_bots(): void
    {
        $this->postJson('/api/contact', [...$this->validMessage(), 'website' => 'https://spam.example'])
            ->assertCreated();

        $this->assertDatabaseCount('messages', 0);
        Mail::assertNothingSent();
    }

    public function test_senders_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/contact', $this->validMessage())->assertCreated();
        }

        $this->postJson('/api/contact', $this->validMessage())->assertTooManyRequests();

        $this->assertDatabaseCount('messages', 5);
    }

    public function test_only_the_admin_can_open_the_messages_page(): void
    {
        $this->get('/messages')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())->get('/messages')->assertForbidden();

        $this->contactMessage();

        $this->actingAs($this->admin())->get('/messages')
            ->assertOk()
            ->assertSee('Hello')
            ->assertSee('Hi there');
    }

    public function test_the_messages_tab_only_shows_for_the_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertDontSee('Messages');

        $this->actingAs($this->admin())->get('/')->assertSee('Messages');
    }

    public function test_the_admin_can_mark_read_and_delete_messages(): void
    {
        $message = $this->contactMessage();

        $page = Volt::actingAs($this->admin())->test('messages');

        $page->call('toggleRead', $message->id);
        $this->assertNotNull($message->fresh()->read_at);

        $page->call('toggleRead', $message->id);
        $this->assertNull($message->fresh()->read_at);

        $page->call('delete', $message->id);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_other_users_cannot_run_the_page_actions(): void
    {
        $message = $this->contactMessage();

        Volt::actingAs(User::factory()->create())
            ->test('messages')
            ->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id]);
    }
}
