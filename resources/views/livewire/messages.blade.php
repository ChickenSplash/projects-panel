<?php

use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

new class extends Component {
    // Runs on every Livewire request, not just the first page load, so the actions
    // below are covered too.
    public function boot(): void
    {
        Gate::authorize('read-messages');
    }

    public function toggleRead(int $messageId): void
    {
        $message = Message::findOrFail($messageId);

        // Set directly: read_at is not fillable, since only this page should change it.
        $message->read_at = $message->read_at ? null : now();
        $message->save();
    }

    public function delete(int $messageId): void
    {
        Message::whereKey($messageId)->delete();

        $this->dispatch('notify', message: 'Message deleted');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'messages' => Message::latest()->get(),
        ];
    }
}; ?>

<div>
    <header x-data x-init="$dream.enter($el)">
        <h1 class="font-display text-4xl font-semibold">Messages</h1>
        <p class="mt-2 text-ink-soft dark:text-moon-soft">Sent from the contact form on your portfolio.</p>
    </header>

    <ul class="mt-8 space-y-3">
        @forelse ($messages as $message)
            <li wire:key="message-{{ $message->id }}" x-data="{ confirming: false }" x-init="$dream.enter($el)"
                class="dream-panel space-y-3 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 font-display text-lg font-semibold">
                            @unless ($message->read_at)
                                <span class="size-2 shrink-0 rounded-full bg-violet-500" title="Unread"></span>
                            @endunless
                            <span class="break-words">{{ $message->subject }}</span>
                        </p>
                        <p class="mt-0.5 text-sm text-ink-soft dark:text-moon-soft">
                            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}"
                                class="dream-link">{{ $message->email }}</a>
                            · <time datetime="{{ $message->created_at->toIso8601String() }}"
                                title="{{ $message->created_at->format('j M Y, H:i') }}">{{ $message->created_at->diffForHumans() }}</time>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" x-show="! confirming" wire:click="toggleRead({{ $message->id }})"
                            class="dream-btn-quiet px-3 py-2 text-sm">
                            {{ $message->read_at ? 'Mark unread' : 'Mark read' }}
                        </button>

                        <button type="button" x-show="! confirming" x-on:click="confirming = true"
                            class="dream-btn-quiet px-3 py-2 hover:text-rose-500 dark:hover:text-rose-300" title="Delete">
                            <x-icon name="trash" class="size-4" />
                            <span class="sr-only">Delete message</span>
                        </button>

                        <div x-show="confirming" x-cloak x-transition.opacity.duration.200ms class="flex items-center gap-2">
                            <button type="button" x-on:click="confirming = false" class="dream-btn-quiet px-3 py-2">
                                Keep
                            </button>
                            <button type="button" class="dream-btn-danger"
                                x-on:click="await $dream.leave($root); $wire.delete({{ $message->id }})">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <p class="text-sm leading-relaxed break-words whitespace-pre-line">{{ $message->body }}</p>
            </li>
        @empty
            <li x-data x-init="$dream.enter($el)" class="dream-panel px-6 py-14 text-center">
                <x-icon name="mail" class="mx-auto size-8 text-violet-400 dark:text-violet-300" />
                <p class="mt-3 font-display text-xl font-semibold">No messages yet</p>
                <p class="mt-1 text-sm text-ink-soft dark:text-moon-soft">Anything sent from your portfolio lands here.</p>
            </li>
        @endforelse
    </ul>
</div>
