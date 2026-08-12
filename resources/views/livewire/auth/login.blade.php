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

<div class="mx-auto max-w-sm">
    <h1 class="text-xl font-semibold">Log in</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Welcome back.</p>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email"
                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password"
                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <input wire:model="remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700">
            Remember me
        </label>

        <button type="submit"
            class="w-full cursor-pointer rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
            Log in
        </button>
    </form>

    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
        No account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-gray-900 underline dark:text-white">Register</a>
    </p>
</div>
