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
    }

    public function delete(int $projectId): void
    {
        // Scoped to the current user's projects, so there is nothing else to authorise.
        Auth::user()->projects()->whereKey($projectId)->delete();
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
    <h1 class="text-xl font-semibold">My projects</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Post a project with a link.</p>

    <form wire:submit="save" class="mt-6 space-y-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div>
            <label for="title" class="block text-sm font-medium">Title</label>
            <input wire:model="title" id="title" type="text" placeholder="My side project"
                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
            @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="url" class="block text-sm font-medium">Link</label>
            <input wire:model="url" id="url" type="url" placeholder="https://example.com"
                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
            @error('url') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="cursor-pointer rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
            Post project
        </button>
    </form>

    <ul class="mt-6 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        @forelse ($projects as $project)
            <li class="flex items-baseline justify-between gap-4 px-4 py-3">
                <div class="min-w-0">
                    <a href="{{ $project->url }}" target="_blank" rel="noopener noreferrer"
                        class="font-medium hover:underline">{{ $project->title }}</a>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $project->url }}</p>
                </div>
                <button type="button" wire:click="delete({{ $project->id }})" wire:confirm="Delete this project?"
                    class="shrink-0 cursor-pointer text-sm text-red-600 hover:underline dark:text-red-400">
                    Delete
                </button>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                You have not posted anything yet.
            </li>
        @endforelse
    </ul>
</div>
