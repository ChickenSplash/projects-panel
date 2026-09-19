<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Anyone can register, so contact messages are for the one account whose email
        // matches ADMIN_EMAIL. Emails are unique, so nobody else can claim that address
        // once the admin has registered with it.
        Gate::define('read-messages', function (User $user): bool {
            $admin = config('app.admin_email');

            return filled($admin) && strcasecmp($user->email, $admin) === 0;
        });
    }
}
