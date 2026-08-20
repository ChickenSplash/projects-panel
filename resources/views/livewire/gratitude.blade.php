<?php

use App\Models\GratitudeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    /** The day being journalled, as `Y-m-d`. */
    public string $date = '';

    public string $body = '';

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    public function shift(int $days): void
    {
        $day = Carbon::parse($this->date)->addDays($days);

        // Nothing to be grateful for yet on a day that has not happened.
        $this->date = $day->isFuture() ? today()->toDateString() : $day->toDateString();
    }

    public function add(): void
    {
        $this->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        $entry = $this->entry() ?? Auth::user()->gratitudeEntries()->create(['entry_date' => $this->date]);

        $entry->items()->create([
            'body' => $this->body,
            'position' => (int) $entry->items()->max('position') + 1,
        ]);

        $this->reset('body');

        $this->dispatch('notify', message: 'Noted, and kept');
    }

    public function delete(int $itemId): void
    {
        $entry = $this->entry();

        if (! $entry) {
            return;
        }

        // Reached through the entry, so only the owner's own items are in range.
        $entry->items()->whereKey($itemId)->delete();

        // A day with nothing left in it is not a day that was journalled.
        if ($entry->items()->doesntExist()) {
            $entry->delete();
        }

        $this->dispatch('notify', message: 'Let go of');
    }

    private function entry(): ?GratitudeEntry
    {
        return Auth::user()->gratitudeEntries()->firstWhere('entry_date', $this->date);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $day = Carbon::parse($this->date);

        return [
            'items' => $this->entry()?->items()->get() ?? collect(),
            'heading' => match (true) {
                $day->isToday() => 'Today',
                $day->isYesterday() => 'Yesterday',
                default => $day->isoFormat('dddd D MMMM'),
            },
            'isToday' => $day->isToday(),
        ];
    }
}; ?>

<div>
    <header x-data x-init="$dream.enter($el)">
        <h1 class="font-display text-4xl font-semibold">Gratitude</h1>
        <p class="mt-2 text-ink-soft dark:text-moon-soft">A few good things, one day at a time.</p>
    </header>

    <section x-data x-init="$dream.enter($el)" class="dream-panel mt-8 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ $heading }}</h2>
                <p class="text-sm text-ink-soft dark:text-moon-soft">{{ $date }}</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" wire:click="shift(-1)" class="dream-btn-quiet px-3 py-2" title="The day before">
                    <x-icon name="chevron" class="size-4 rotate-90" />
                    <span class="sr-only">Previous day</span>
                </button>

                <input type="date" wire:model.live="date" max="{{ today()->toDateString() }}"
                    class="dream-input w-auto" aria-label="Day">

                <button type="button" wire:click="shift(1)" class="dream-btn-quiet px-3 py-2 disabled:opacity-40"
                    title="The day after" @disabled($isToday)>
                    <x-icon name="chevron" class="size-4 -rotate-90" />
                    <span class="sr-only">Next day</span>
                </button>
            </div>
        </div>

        @error('date')
            <p x-data x-init="$dream.pop($el)" class="dream-error">{{ $message }}</p>
        @enderror

        <form wire:submit="add" class="mt-5 space-y-5">
            <x-field name="body" label="Grateful for" placeholder="The first coffee of the morning" />

            <button type="submit" class="dream-btn" wire:loading.attr="disabled">
                <x-icon name="sparkle" class="size-4" />
                <span wire:loading.remove wire:target="add">Add to the day</span>
                <span wire:loading wire:target="add">Adding…</span>
            </button>
        </form>
    </section>

    <ul class="mt-8 space-y-3">
        @forelse ($items as $item)
            {{-- `confirming` belongs to the two-tap delete, the same as a project row. --}}
            <li wire:key="item-{{ $item->id }}" x-data="{ confirming: false }" x-init="$dream.enter($el)"
                class="dream-card flex items-center justify-between gap-4 px-5 py-4">
                <p class="min-w-0 break-words">{{ $item->body }}</p>

                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" x-show="! confirming" x-on:click="confirming = true"
                        class="dream-btn-quiet px-3 py-2 hover:text-rose-500 dark:hover:text-rose-300" title="Delete">
                        <x-icon name="trash" class="size-4" />
                        <span class="sr-only">Delete this note</span>
                    </button>

                    <div x-show="confirming" x-cloak x-transition.opacity.duration.200ms class="flex items-center gap-2">
                        <button type="button" x-on:click="confirming = false" class="dream-btn-quiet px-3 py-2">
                            Keep
                        </button>
                        <button type="button" class="dream-btn-danger"
                            x-on:click="await $dream.leave($root); $wire.delete({{ $item->id }})">
                            Delete
                        </button>
                    </div>
                </div>
            </li>
        @empty
            <li x-data x-init="$dream.enter($el)" class="dream-panel px-6 py-14 text-center">
                <x-icon name="sparkle" class="mx-auto size-8 text-violet-400 dark:text-violet-300" />
                <p class="mt-3 font-display text-xl font-semibold">Nothing written down yet</p>
                <p class="mt-1 text-sm text-ink-soft dark:text-moon-soft">Add the first good thing about this day.</p>
            </li>
        @endforelse
    </ul>
</div>
