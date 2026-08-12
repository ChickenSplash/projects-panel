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
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-3">
                <a href="{{ route('projects') }}" wire:navigate class="font-semibold">Projects Panel</a>

                <div class="flex items-center gap-3">
                    <div class="flex gap-0.5 rounded-lg border border-gray-200 p-0.5 dark:border-gray-700">
                        @foreach (['system' => 'Auto', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                            <button type="button" data-theme-option="{{ $value }}"
                                class="cursor-pointer rounded-md px-2 py-1 text-xs font-medium text-gray-500 hover:text-gray-900 data-[active=true]:bg-gray-900 data-[active=true]:text-white dark:text-gray-400 dark:hover:text-white dark:data-[active=true]:bg-white dark:data-[active=true]:text-gray-900">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cursor-pointer text-sm text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Log out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                            Log in
                        </a>
                    @endauth
                </div>
            </div>

            <nav class="mx-auto flex max-w-3xl gap-6 px-4">
                @foreach ([['projects', 'All projects'], ['my-projects', 'My projects']] as [$route, $label])
                    <a href="{{ route($route) }}" wire:navigate
                        @class([
                            '-mb-px border-b-2 py-2 text-sm font-medium',
                            'border-gray-900 text-gray-900 dark:border-white dark:text-white' => request()->routeIs($route),
                            'border-transparent text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! request()->routeIs($route),
                        ])>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
