<?php

use App\Models\Project;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'projects' => Project::query()->with('user')->latest()->paginate(15),
        ];
    }
}; ?>

<div>
    <header x-dream>
        <h1 class="font-display text-4xl font-semibold">All projects</h1>
        <p class="mt-2 text-ink-soft dark:text-moon-soft">Everything everyone has posted.</p>
    </header>

    <ul class="mt-8 space-y-3">
        @forelse ($projects as $project)
            <x-project-card :project="$project">
                <p class="text-right text-sm text-ink-soft dark:text-moon-soft">
                    <span class="font-semibold text-ink dark:text-moon">{{ $project->user->username }}</span>
                    <br>
                    {{ $project->created_at->diffForHumans() }}
                </p>
            </x-project-card>
        @empty
            <li x-dream class="dream-panel px-6 py-14 text-center">
                <x-icon name="sparkle" class="mx-auto size-8 text-violet-400 dark:text-violet-300" />
                <p class="mt-3 font-display text-xl font-semibold">Nothing here yet</p>
                <p class="mt-1 text-sm text-ink-soft dark:text-moon-soft">Be the first to post one.</p>
            </li>
        @endforelse
    </ul>

    <div x-dream>
        {{ $projects->links('pagination') }}
    </div>
</div>
