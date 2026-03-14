<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    // Servers
    Route::livewire('servers', 'pages::servers.index')->name('servers.index');
    Route::livewire('servers/create', 'pages::servers.create')->name('servers.create')->middleware('require.2fa');

    // Projects
    Route::livewire('projects', 'pages::projects.index')->name('projects.index');
    Route::livewire('projects/create', 'pages::projects.create')->name('projects.create')->middleware('require.2fa');

    // Stacks
    Route::livewire('stacks', 'pages::stacks.index')->name('stacks.index');

    // Admin
    Route::middleware('admin')->group(function () {
        Route::livewire('admin/domains', 'pages::admin.domains')->name('admin.domains');
        Route::livewire('admin/users', 'pages::admin.users')->name('admin.users');
    });
});

require __DIR__ . '/settings.php';
