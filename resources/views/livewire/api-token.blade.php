<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    // Sanctum only ever hands back the plain text once, at creation, so it lives here
    // until the page is left. Locked: the client has no business setting it.
    #[Locked]
    public ?string $plainTextToken = null;

    public function generate(): void
    {
        $user = Auth::user();

        // One token per account, so generating again replaces the old one.
        $user->tokens()->delete();

        $this->plainTextToken = $user->createToken('api')->plainTextToken;

        $this->dispatch('notify', message: 'Token conjured');
    }

    public function revoke(): void
    {
        Auth::user()->tokens()->delete();

        $this->plainTextToken = null;

        $this->dispatch('notify', message: 'Token undone');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'token' => Auth::user()->tokens()->first(),
        ];
    }
}; ?>

<div class="space-y-8">
    <header x-dream>
        <h1 class="font-display text-4xl font-semibold">API token</h1>
        <p class="mt-2 text-ink-soft dark:text-moon-soft">Post projects from outside the app.</p>
    </header>

    <section x-dream class="dream-panel space-y-5 p-6">
        @if ($plainTextToken)
            <div>
                <h2 class="font-display text-xl font-semibold">Your new token</h2>
                <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">
                    Copy it now — it is not shown again once you leave this page.
                </p>
            </div>

            <p class="rounded-2xl border border-white/80 bg-white/70 px-4 py-3 font-mono text-sm break-all dark:border-white/10 dark:bg-white/5">
                {{ $plainTextToken }}
            </p>
        @elseif ($token)
            <div>
                <h2 class="font-display text-xl font-semibold">You have a token</h2>
                <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">
                    Made {{ $token->created_at->diffForHumans() }}. Generating another replaces it.
                </p>
            </div>
        @else
            <div>
                <h2 class="font-display text-xl font-semibold">No token yet</h2>
                <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">
                    Generate one and it is shown to you once.
                </p>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" wire:click="generate" class="dream-btn" wire:loading.attr="disabled">
                <x-icon name="sparkle" class="size-4" />
                {{ $token ? 'Generate a new one' : 'Generate token' }}
            </button>

            @if ($token)
                <button type="button" wire:click="revoke" class="dream-btn-quiet hover:text-rose-500 dark:hover:text-rose-300"
                    wire:loading.attr="disabled">
                    Revoke
                </button>
            @endif
        </div>
    </section>

    <section x-dream class="dream-panel space-y-4 p-6">
        <div>
            <h2 class="font-display text-xl font-semibold">Posting a project</h2>
            <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">
                It lands on your shelf exactly as if you had posted it here.
            </p>
        </div>

        <pre class="overflow-x-auto rounded-2xl border border-white/80 bg-white/70 px-4 py-3 font-mono text-xs leading-relaxed dark:border-white/10 dark:bg-white/5">curl -X POST {{ url('/api/projects') }} \
  -H "Authorization: Bearer &lt;token&gt;" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title": "My Project", "link": "https://example.com"}'</pre>
    </section>
</div>
