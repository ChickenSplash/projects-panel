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
    <h1 class="text-xl font-semibold">All projects</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Everything everyone has posted.</p>

    <ul class="mt-6 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        @forelse ($projects as $project)
            <li class="flex items-baseline justify-between gap-4 px-4 py-3">
                <div class="min-w-0">
                    <a href="{{ $project->url }}" target="_blank" rel="noopener noreferrer"
                        class="font-medium hover:underline">{{ $project->title }}</a>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $project->url }}</p>
                </div>
                <p class="shrink-0 text-sm text-gray-500 dark:text-gray-400">
                    {{ $project->user->name }} &middot; {{ $project->created_at->diffForHumans() }}
                </p>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                No projects yet. Be the first to post one.
            </li>
        @endforelse
    </ul>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>
