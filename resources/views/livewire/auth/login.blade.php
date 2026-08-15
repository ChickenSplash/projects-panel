<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): mixed
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        session()->regenerate();

        return $this->redirectIntended(route('my-projects'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-sm" x-data x-init="$dream.enter($el)">
    <div class="dream-panel p-7">
        <h1 class="font-display text-3xl font-semibold">Log in</h1>
        <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">Welcome back.</p>

        <form wire:submit="login" class="mt-7 space-y-5">
            <x-field name="email" label="Email" type="email" autocomplete="email" />
            <x-field name="password" label="Password" type="password" autocomplete="current-password" />

            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-soft dark:text-moon-soft">
                <input wire:model="remember" type="checkbox"
                    class="size-4 cursor-pointer rounded-md border-white/80 accent-violet-500 dark:border-white/20">
                Remember me
            </label>

            <button type="submit" class="dream-btn w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Log in</span>
                <span wire:loading wire:target="login">Just a moment…</span>
            </button>
        </form>
    </div>

    <p class="mt-5 text-center text-sm text-ink-soft dark:text-moon-soft">
        No account?
        <a href="{{ route('register') }}" wire:navigate
            class="font-semibold text-violet-600 underline decoration-violet-300 underline-offset-4 dark:text-violet-300 dark:decoration-violet-500">Register</a>
    </p>
</div>
