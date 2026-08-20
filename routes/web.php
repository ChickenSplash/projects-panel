<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'projects')->name('projects');

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
});

Route::middleware('auth')->group(function () {
    Volt::route('/my-projects', 'my-projects')->name('my-projects');
    Volt::route('/gratitude', 'gratitude')->name('gratitude');
    Volt::route('/profile', 'profile')->name('profile');
    Volt::route('/api-token', 'api-token')->name('api-token');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('projects');
    })->name('logout');
});
