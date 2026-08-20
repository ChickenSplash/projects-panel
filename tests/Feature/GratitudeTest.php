<?php

namespace Tests\Feature;

use App\Models\GratitudeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class GratitudeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_the_journal(): void
    {
        $this->get('/gratitude')->assertRedirect('/login');
    }

    public function test_the_first_item_of_a_day_creates_that_day(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('gratitude')
            ->set('body', 'Rain on the window')
            ->call('add')
            ->assertHasNoErrors()
            ->assertSet('body', '');

        $this->assertDatabaseHas('gratitude_entries', [
            'user_id' => $user->id,
            'entry_date' => today()->toDateString(),
        ]);

        $this->assertDatabaseHas('gratitude_items', [
            'entry_id' => $user->gratitudeEntries()->sole()->id,
            'body' => 'Rain on the window',
            'position' => 1,
        ]);
    }

    public function test_further_items_join_the_same_day_in_order(): void
    {
        $user = User::factory()->create();

        $component = Volt::actingAs($user)->test('gratitude');

        foreach (['One', 'Two', 'Three'] as $body) {
            $component->set('body', $body)->call('add');
        }

        $this->assertDatabaseCount('gratitude_entries', 1);

        $this->assertSame(
            ['One', 'Two', 'Three'],
            $user->gratitudeEntries()->sole()->items()->pluck('body')->all(),
        );
    }

    public function test_an_item_needs_a_body_and_a_day_that_has_happened(): void
    {
        Volt::actingAs(User::factory()->create())
            ->test('gratitude')
            ->set('body', '')
            ->call('add')
            ->assertHasErrors(['body' => 'required']);

        Volt::actingAs(User::factory()->create())
            ->test('gratitude')
            ->set('date', today()->addDay()->toDateString())
            ->set('body', 'Tomorrow')
            ->call('add')
            ->assertHasErrors(['date' => 'before_or_equal']);

        $this->assertDatabaseCount('gratitude_items', 0);
    }

    public function test_each_day_holds_its_own_items(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('gratitude')
            ->set('body', 'Today thing')
            ->call('add')
            ->call('shift', -1)
            ->assertSet('date', today()->subDay()->toDateString())
            ->assertDontSee('Today thing')
            ->set('body', 'Yesterday thing')
            ->call('add')
            ->assertSee('Yesterday thing')
            ->call('shift', 1)
            ->assertSee('Today thing')
            ->assertDontSee('Yesterday thing');

        $this->assertDatabaseCount('gratitude_entries', 2);
    }

    public function test_the_day_after_today_is_out_of_reach(): void
    {
        Volt::actingAs(User::factory()->create())
            ->test('gratitude')
            ->call('shift', 1)
            ->assertSet('date', today()->toDateString());
    }

    public function test_a_user_only_deletes_their_own_items(): void
    {
        $user = User::factory()->create();
        $mine = $user->gratitudeEntries()->create(['entry_date' => today()->toDateString()]);
        $mine->items()->create(['body' => 'Mine', 'position' => 1]);
        $keep = $mine->items()->create(['body' => 'Also mine', 'position' => 2]);

        $theirs = GratitudeEntry::factory()->create();
        $theirItem = $theirs->items()->create(['body' => 'Theirs', 'position' => 1]);

        Volt::actingAs($user)
            ->test('gratitude')
            ->call('delete', $mine->items()->first()->id)
            ->call('delete', $theirItem->id);

        $this->assertSame(['Also mine'], $mine->items()->pluck('body')->all());
        $this->assertDatabaseHas('gratitude_items', ['id' => $theirItem->id]);
        $this->assertModelExists($keep);
    }

    public function test_emptying_a_day_removes_it(): void
    {
        $user = User::factory()->create();
        $entry = $user->gratitudeEntries()->create(['entry_date' => today()->toDateString()]);
        $item = $entry->items()->create(['body' => 'Only one', 'position' => 1]);

        Volt::actingAs($user)->test('gratitude')->call('delete', $item->id);

        $this->assertDatabaseCount('gratitude_entries', 0);
    }

    public function test_deleting_a_user_takes_their_journal_with_them(): void
    {
        $user = User::factory()->create();
        $entry = $user->gratitudeEntries()->create(['entry_date' => today()->toDateString()]);
        $entry->items()->create(['body' => 'Gone too', 'position' => 1]);

        $user->delete();

        $this->assertDatabaseCount('gratitude_entries', 0);
        $this->assertDatabaseCount('gratitude_items', 0);
    }
}
