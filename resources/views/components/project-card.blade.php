@props(['project'])

{{--
    One row of a project list. `confirming` belongs to the delete control that
    "My projects" drops into the slot; the shared list leaves the slot empty.
--}}
<li wire:key="project-{{ $project->id }}" x-data="{ confirming: false }" x-init="$dream.enter($el)"
    {{ $attributes->merge(['class' => 'dream-card flex items-center justify-between gap-4 px-5 py-4']) }}>
    <div class="min-w-0">
        <a href="{{ $project->url }}" target="_blank" rel="noopener noreferrer"
            class="group inline-flex max-w-full items-center gap-1.5 font-display text-lg font-semibold decoration-violet-400 decoration-2 underline-offset-4 hover:underline">
            <span class="truncate">{{ $project->title }}</span>
            <x-icon name="link" class="size-4 shrink-0 text-violet-400 opacity-0 transition duration-300 group-hover:translate-x-0.5 group-hover:opacity-100 dark:text-violet-300" />
        </a>
        <p class="truncate text-sm text-ink-soft dark:text-moon-soft">{{ $project->url }}</p>
    </div>

    <div class="flex shrink-0 items-center gap-2">
        {{ $slot }}
    </div>
</li>
