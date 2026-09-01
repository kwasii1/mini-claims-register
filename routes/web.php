<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('claims', 'pages::index-claims')->name('claims.index');
    Route::livewire('claims/register', 'pages::register-claim')->name('claims.register');
    Route::livewire('claims/{claim}', 'pages::show-claim')->name('claims.show');
});
