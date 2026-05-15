<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(ChatController::class)
        ->prefix('chat')
        ->group(function () {
            Route::get('/', 'index')->name('chat.index');
            Route::get('/create', 'create')->name('chat.create');
            Route::post('/conversations', 'storeConversation')->name('chat.conversations.store');
            Route::get('{conversation}', 'show')
                ->whereNumber('conversation')
                ->name('chat.show');
            Route::post('{conversation}/messages', 'store')
                ->whereNumber('conversation')
                ->name('chat.messages.store');
        });
});

require __DIR__.'/settings.php';
