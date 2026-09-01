<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('claims/register', 'pages::register-claim')->name('claims.register');
    Route::livewire('claims/{claim}', 'pages::show-claim')->name('claims.show');
});

require __DIR__.'/settings.php';
