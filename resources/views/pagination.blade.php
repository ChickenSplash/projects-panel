{{--
    Livewire's own paginator views are Tailwind 3 flavoured and carry numbered
    pages this list has no use for. Newer / older is the whole interaction.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-6 flex items-center justify-between gap-3">
        <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
            @disabled($paginator->onFirstPage())
            class="dream-btn-quiet disabled:pointer-events-none disabled:opacity-40">
            Newer
        </button>

        <p class="text-xs font-bold tracking-[0.14em] text-ink-soft uppercase dark:text-moon-soft">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </p>

        <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
            @disabled(! $paginator->hasMorePages())
            class="dream-btn-quiet disabled:pointer-events-none disabled:opacity-40">
            Older
        </button>
    </nav>
@endif
