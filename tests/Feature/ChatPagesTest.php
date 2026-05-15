<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from chat pages', function () {
    $this->get(route('chat.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view the chat index', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $olderConversation = createDirectConversation($user, $other, [
        [
            'sender' => $other,
            'body' => 'Te comparto el esquema final esta tarde.',
        ],
    ]);
    $olderConversation->forceFill([
        'last_message_at' => now()->subHour(),
    ])->save();

    $newerOther = User::factory()->create();
    $newerConversation = createDirectConversation($user, $newerOther, [
        [
            'sender' => $newerOther,
            'body' => 'Estoy validando el layout en mobile y desktop.',
        ],
    ]);
    $newerConversation->forceFill([
        'last_message_at' => now(),
    ])->save();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->where('chat.activeConversationId', null)
            ->has('chat.conversations', 2)
            ->where('chat.conversations.0.id', $newerConversation->id)
            ->where('chat.conversations.1.id', $olderConversation->id)
            ->where('chat.conversations.0.lastMessage', 'Estoy validando el layout en mobile y desktop.')
            ->where('chat.conversations.0.unreadCount', 1)
            ->has('chat.messages', 0)
        );
});

test('authenticated users can view a chat conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createDirectConversation($user, $other, [
        [
            'sender' => $other,
            'body' => 'Ya dejé listo el resumen de la migración.',
        ],
        [
            'sender' => $user,
            'body' => 'Perfecto. Quiero que la vista principal se vea más limpia antes de conectar el backend.',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('chat/Index')
            ->where('chat.activeConversation.id', $conversation->id)
            ->where('chat.activeConversation.name', $other->name)
            ->has('chat.messages', 2)
            ->where('chat.messages.0.status', 'read')
            ->where('chat.messages.1.status', 'delivered')
        );
});
