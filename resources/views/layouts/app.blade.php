<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        {{-- Applies the saved theme before first paint so there is no flash of the wrong colour scheme. --}}
        <script>
            window.applyTheme = () => {
                const choice = localStorage.getItem('theme') ?? 'system';
                const dark = choice === 'dark'
                    || (choice === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

                document.documentElement.classList.toggle('dark', dark);

                document.querySelectorAll('[data-theme-option]').forEach((button) => {
                    button.dataset.active = button.dataset.themeOption === choice ? 'true' : 'false';
                });
            };

            window.applyTheme();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => window.applyTheme());
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-dvh font-sans text-ink antialiased dark:text-moon">
        {{-- Drifting colour behind the whole app. Decorative, so it is hidden from assistive tech. --}}
        <div class="dream-sky" aria-hidden="true">
            <span class="aurora aurora--iris"></span>
            <span class="aurora aurora--blush"></span>
            <span class="aurora aurora--mint"></span>
            <span class="aurora aurora--sky"></span>
            <span class="stardust"></span>
        </div>

        <header class="mx-auto w-full max-w-3xl px-4 pt-6">
            <div class="dream-panel flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <a href="{{ route('projects') }}" wire:navigate class="flex items-center gap-3">
                    <span class="dream-orb"></span>
                    <span class="font-display text-lg font-semibold">Projects Panel</span>
                </a>

                <div class="flex items-center gap-2">
                    <div class="dream-switch">
                        @foreach (['system' => ['Auto', 'auto'], 'light' => ['Light', 'sun'], 'dark' => ['Dark', 'moon']] as $value => [$label, $icon])
                            <button type="button" data-theme-option="{{ $value }}" title="{{ $label }}">
                                <x-icon :name="$icon" class="size-4" />
                                <span class="sr-only">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>

                    @auth
                        {{-- Sits beside the account menu rather than in the tabs: it is a setting, not a place to browse. --}}
                        <a href="{{ route('api-token') }}" wire:navigate title="API token"
                            @class(['dream-btn-quiet flex items-center p-2.5', 'text-ink dark:text-moon' => request()->routeIs('api-token')])>
                            <x-icon name="key" class="size-4" />
                            <span class="sr-only">API token</span>
                        </a>

                        {{-- The account menu: a disclosure, so `aria-expanded` on the trigger is all it owes assistive tech. --}}
                        <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false">
                            <button type="button" class="dream-user" x-on:click="open = ! open" :aria-expanded="open">
                                <span class="dream-user-mark" aria-hidden="true">{{ Str::upper(Str::substr(auth()->user()->username, 0, 1)) }}</span>
                                <span class="max-w-36 truncate">{{ auth()->user()->username }}</span>
                                <x-icon name="chevron" class="size-3.5 shrink-0 transition duration-300"
                                    x-bind:class="open && 'rotate-180'" />
                            </button>

                            <div x-show="open" x-cloak x-on:click.outside="open = false"
                                x-transition.origin.top.right.duration.200ms class="dream-menu">
                                <a href="{{ route('profile') }}" wire:navigate class="dream-menu-item">
                                    <x-icon name="user" class="size-4" />
                                    Profile
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dream-menu-item">
                                        <x-icon name="exit" class="size-4" />
                                        Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="dream-link">Log in</a>
                    @endauth
                </div>
            </div>

            <nav class="mt-3 flex items-center gap-1.5 px-1">
                @foreach ([['projects', 'All projects'], ['my-projects', 'My projects']] as [$route, $label])
                    <a href="{{ route($route) }}" wire:navigate
                        @class([
                            'dream-tab',
                            'dream-tab-active' => request()->routeIs($route),
                            'dream-tab-idle' => ! request()->routeIs($route),
                        ])>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-4 pt-8 pb-16">
            {{ $slot }}
        </main>

        <footer class="pb-10 text-center text-xs font-semibold tracking-[0.18em] text-ink-soft/70 uppercase dark:text-moon-soft/60">
            Made of links and daydreams
        </footer>

        {{-- One toast for the whole app; components raise it with `$this->dispatch('notify', message: '…')`. --}}
        <div class="pointer-events-none fixed inset-x-0 bottom-8 z-50 flex justify-center px-4"
            x-data="{ message: '' }"
            x-on:notify.window="message = $event.detail.message; $dream.toast($refs.toast)">
            <p x-ref="toast" x-text="message" class="dream-toast"></p>
        </div>

        @livewireScripts
    </body>
</html>
