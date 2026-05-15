<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::controller(ChatController::class)
        ->prefix('chat')
        ->group(function () {
            Route::get('/', 'index')->name('chat.index');
            Route::get('{conversation}', 'show')
                ->whereNumber('conversation')
                ->name('chat.show');
        });
});

require __DIR__.'/settings.php';
