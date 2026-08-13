<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;

new class extends Component {
    public string $username = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->username = Auth::user()->username;
        $this->email = Auth::user()->email;
    }

    public function save(): void
    {
        $user = Auth::user();

        // Both columns are unique, so the row being edited has to be excused from its own values.
        $user->update($this->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]));

        $this->dispatch('notify', message: 'Saved, just as you are');
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update(['password' => $validated['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('notify', message: 'New password, new you');
    }

    public function delete(): mixed
    {
        $user = Auth::user();

        // Log out first: the session belongs to a row that is about to stop existing.
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        // Projects cascade with the user, so the shared list loses them too.
        $user->delete();

        return $this->redirect(route('projects'), navigate: true);
    }
}; ?>

<div class="space-y-8">
    <header x-data x-init="$dream.enter($el)">
        <h1 class="font-display text-4xl font-semibold">Profile</h1>
        <p class="mt-2 text-ink-soft dark:text-moon-soft">Change who you are here, or disappear entirely.</p>
    </header>

    <form wire:submit="save" x-data x-init="$dream.enter($el)" class="dream-panel space-y-5 p-6">
        <h2 class="font-display text-xl font-semibold">Your details</h2>

        <x-field name="username" label="Username" autocomplete="username" />
        <x-field name="email" label="Email" type="email" autocomplete="email" />

        <button type="submit" class="dream-btn" wire:loading.attr="disabled">
            <x-icon name="sparkle" class="size-4" />
            <span wire:loading.remove wire:target="save">Save changes</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </form>

    <form wire:submit="updatePassword" x-data x-init="$dream.enter($el)" class="dream-panel space-y-5 p-6">
        <h2 class="font-display text-xl font-semibold">Password</h2>

        <x-field name="current_password" label="Current password" type="password" autocomplete="current-password" />
        <x-field name="password" label="New password" type="password" autocomplete="new-password" />
        <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" />

        <button type="submit" class="dream-btn" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="updatePassword">Change password</span>
            <span wire:loading wire:target="updatePassword">Changing…</span>
        </button>
    </form>

    {{-- Two taps rather than a browser confirm dialog, the same as deleting a project. --}}
    <section x-data="{ confirming: false }" x-init="$dream.enter($el)" class="dream-panel space-y-4 p-6">
        <div>
            <h2 class="font-display text-xl font-semibold">Delete account</h2>
            <p class="mt-1.5 text-sm text-ink-soft dark:text-moon-soft">
                Your account and every project you posted go with it. There is no undo.
            </p>
        </div>

        <button type="button" x-show="! confirming" x-on:click="confirming = true"
            class="dream-btn-quiet hover:text-rose-500 dark:hover:text-rose-300">
            <x-icon name="trash" class="mr-1.5 -mt-0.5 inline size-4" />
            Delete my account
        </button>

        <div x-show="confirming" x-cloak x-transition.opacity.duration.200ms class="flex flex-wrap items-center gap-3">
            <p class="text-sm font-semibold">Delete {{ auth()->user()->username }} for good?</p>

            <button type="button" x-on:click="confirming = false" class="dream-btn-quiet">Keep me</button>

            <button type="button" wire:click="delete" class="dream-btn-danger" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="delete">Delete for good</span>
                <span wire:loading wire:target="delete">Fading…</span>
            </button>
        </div>
    </section>
</div>
