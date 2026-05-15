<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from chat pages', function () {
    $this->get(route('chat.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the chat index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->where('chat.activeConversationId', null)
            ->has('chat.conversations', 4)
            ->has('chat.messages', 0)
        );
});

test('authenticated users can view a chat conversation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.show', 2))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->where('chat.activeConversation.id', 2)
            ->where('chat.activeConversation.name', 'Carlos Vega')
            ->has('chat.messages', 3)
        );
});
