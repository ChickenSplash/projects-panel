<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;

new class extends Component {
    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): mixed
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        Auth::login(User::create($validated));

        return $this->redirect(route('my-projects'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-sm" x-data x-init="$dream.enter($el)">
    <div class="dream-panel p-7">
        <h1 class="font-display text-3xl font-semibold">Register</h1>
        <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">Create an account to post projects.</p>

        <form wire:submit="register" class="mt-7 space-y-5">
            <x-field name="username" label="Username" autocomplete="username" />
            <x-field name="email" label="Email" type="email" autocomplete="email" />
            <x-field name="password" label="Password" type="password" autocomplete="new-password" />
            <x-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />

            <button type="submit" class="dream-btn w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="register">Register</span>
                <span wire:loading wire:target="register">Just a moment…</span>
            </button>
        </form>
    </div>

    <p class="mt-5 text-center text-sm text-ink-soft dark:text-moon-soft">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate
            class="font-semibold text-violet-600 underline decoration-violet-300 underline-offset-4 dark:text-violet-300 dark:decoration-violet-500">Log in</a>
    </p>
</div>
