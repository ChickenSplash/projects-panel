{{--
    The OAuth consent screen, shown once when a connector first asks for access. Passport hands
    it `$client`, `$user`, `$scopes` and `$authToken`; of those only `auth_token` is posted back,
    since the request being approved is held in the session rather than in the form.
--}}
<x-layouts::app title="Authorise {{ $client->name }}">
    <div class="space-y-8">
        <header x-data x-init="$dream.enter($el)">
            <h1 class="font-display text-4xl font-semibold">Let it in?</h1>
            <p class="mt-2 text-ink-soft dark:text-moon-soft">
                <span class="font-semibold text-ink dark:text-moon">{{ $client->name }}</span>
                is asking to reach your account.
            </p>
        </header>

        <section x-data x-init="$dream.enter($el)" class="dream-panel space-y-6 p-6">
            <div class="flex items-center gap-3">
                <span class="dream-user-mark" aria-hidden="true">{{ Str::upper(Str::substr($user->username, 0, 1)) }}</span>
                <div>
                    <p class="text-sm font-semibold">{{ $user->username }}</p>
                    <p class="text-sm text-ink-soft dark:text-moon-soft">{{ $user->email }}</p>
                </div>
            </div>

            <div>
                <h2 class="font-display text-xl font-semibold">What it will be able to do</h2>

                <ul class="mt-3 space-y-2 text-sm text-ink-soft dark:text-moon-soft">
                    {{-- Whatever it asked for, in its own words. A request that names no scope
                         still gets told what a token is good for here. --}}
                    @forelse ($scopes as $scope)
                        <li class="flex items-start gap-2.5">
                            <x-icon name="sparkle" class="mt-0.5 size-4 shrink-0 text-violet-500 dark:text-violet-300" />
                            {{ $scope->description }}
                        </li>
                    @empty
                        <li class="flex items-start gap-2.5">
                            <x-icon name="sparkle" class="mt-0.5 size-4 shrink-0 text-violet-500 dark:text-violet-300" />
                            Post projects to your shelf, exactly as you would from the form.
                        </li>
                    @endforelse
                </ul>

                <p class="mt-3 text-sm text-ink-soft dark:text-moon-soft">
                    Nothing else: it cannot read, edit or delete anything, here or anywhere else.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('passport.authorizations.approve') }}">
                    @csrf
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">

                    <button type="submit" class="dream-btn">
                        <x-icon name="sparkle" class="size-4" />
                        Allow
                    </button>
                </form>

                <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">

                    <button type="submit" class="dream-btn-quiet hover:text-rose-500 dark:hover:text-rose-300">
                        Not now
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-layouts::app>
