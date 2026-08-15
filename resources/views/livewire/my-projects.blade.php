<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|url:http,https|max:2048')]
    public string $url = '';

    public function save(): void
    {
        $this->validate();

        Auth::user()->projects()->create([
            'title' => $this->title,
            'url' => $this->url,
        ]);

        $this->reset('title', 'url');

        $this->dispatch('notify', message: 'Posted into the ether');
    }

    public function delete(int $projectId): void
    {
        // Scoped to the current user's projects, so there is nothing else to authorise.
        Auth::user()->projects()->whereKey($projectId)->delete();

        $this->dispatch('notify', message: 'Gone without a trace');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'projects' => Auth::user()->projects()->latest()->get(),
        ];
    }
}; ?>

<div>
    <header x-dream>
        <h1 class="font-display text-4xl font-semibold">My projects</h1>
        <p class="mt-2 text-ink-soft dark:text-moon-soft">Post a project with a link.</p>
    </header>

    <form wire:submit="save" x-dream class="dream-panel mt-8 space-y-5 p-6">
        <x-field name="title" label="Title" placeholder="My side project" />
        <x-field name="url" label="Link" type="url" placeholder="https://example.com" />

        <button type="submit" class="dream-btn" wire:loading.attr="disabled">
            <x-icon name="sparkle" class="size-4" />
            <span wire:loading.remove wire:target="save">Post project</span>
            <span wire:loading wire:target="save">Posting…</span>
        </button>
    </form>

    <ul class="mt-8 space-y-3">
        @forelse ($projects as $project)
            <x-project-card :project="$project">
                {{-- Two taps instead of a browser confirm dialog, so the whole flow stays in the page. --}}
                <button type="button" x-show="! confirming" x-on:click="confirming = true"
                    class="dream-btn-quiet px-3 py-2 hover:text-rose-500 dark:hover:text-rose-300" title="Delete">
                    <x-icon name="trash" class="size-4" />
                    <span class="sr-only">Delete {{ $project->title }}</span>
                </button>

                <div x-show="confirming" x-cloak x-transition.opacity.duration.200ms class="flex items-center gap-2">
                    <button type="button" x-on:click="confirming = false" class="dream-btn-quiet px-3 py-2">
                        Keep
                    </button>
                    <button type="button" class="dream-btn-danger"
                        x-on:click="await $dream.leave($root); $wire.delete({{ $project->id }})">
                        Delete
                    </button>
                </div>
            </x-project-card>
        @empty
            <li x-dream class="dream-panel px-6 py-14 text-center">
                <x-icon name="sparkle" class="mx-auto size-8 text-violet-400 dark:text-violet-300" />
                <p class="mt-3 font-display text-xl font-semibold">Your shelf is empty</p>
                <p class="mt-1 text-sm text-ink-soft dark:text-moon-soft">Post something above and it will land here.</p>
            </li>
        @endforelse
    </ul>
</div>
