<?php

use App\Events\Chat\ConversationViewed;
use App\Events\Chat\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('non participants cannot view a conversation', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = createDirectConversation($owner, $participant);

    $this->actingAs($intruder)
        ->get(route('chat.show', $conversation))
        ->assertNotFound();
});

test('opening a conversation marks it as viewed', function () {
    Event::fake([ConversationViewed::class]);

    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createDirectConversation($user, $other, [
        [
            'sender' => $other,
            'body' => 'Estoy validando el layout en mobile y desktop.',
        ],
        [
            'sender' => $user,
            'body' => 'Buena idea. Si el sidebar colapsa bien, la experiencia se siente más natural.',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('chat.show', $conversation))
        ->assertOk();

    $latestMessage = Message::query()
        ->where('conversation_id', $conversation->id)
        ->latest('id')
        ->firstOrFail();

    $this->assertDatabaseHas('conversation_participants', [
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'last_read_message_id' => $latestMessage->id,
    ]);

    Event::assertDispatched(ConversationViewed::class, function (ConversationViewed $event) use ($conversation, $user): bool {
        return $event->conversation->is($conversation)
            && $event->participant->user_id === $user->id;
    });
});

test('users can send a message to a conversation', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = createDirectConversation($user, $other);

    $response = $this->actingAs($user)->post(route('chat.messages.store', $conversation), [
        'body' => '  Hola, ya quedó el backend del chat.  ',
    ]);

    $response->assertRedirect(route('chat.show', $conversation, absolute: false));

    $message = Message::query()
        ->where('conversation_id', $conversation->id)
        ->latest('id')
        ->firstOrFail();

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'conversation_id' => $conversation->id,
        'sender_id' => $user->id,
        'body' => 'Hola, ya quedó el backend del chat.',
    ]);

    $conversation->refresh();

    expect($conversation->last_message_id)->toBe($message->id);
    expect($conversation->last_message_at)->not->toBeNull();

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation, $user, $message): bool {
        return $event->message->is($message)
            && $event->message->conversation_id === $conversation->id
            && $event->message->sender_id === $user->id;
    });
});

test('users can create a direct conversation with another user', function () {
    $creator = User::factory()->create();
    $participant = User::factory()->create();

    $response = $this->actingAs($creator)->post(route('chat.conversations.store'), [
        'participant_id' => $participant->id,
    ]);

    $conversation = Conversation::query()->firstOrFail();

    $response->assertRedirect(route('chat.show', $conversation, absolute: false));

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'type' => 'direct',
        'created_by_user_id' => $creator->id,
        'direct_key' => collect([$creator->id, $participant->id])->sort()->join(':'),
    ]);

    $this->assertDatabaseHas('conversation_participants', [
        'conversation_id' => $conversation->id,
        'user_id' => $creator->id,
        'role' => 'owner',
    ]);

    $this->assertDatabaseHas('conversation_participants', [
        'conversation_id' => $conversation->id,
        'user_id' => $participant->id,
        'role' => 'member',
    ]);
});

test('users reuse an existing direct conversation', function () {
    $creator = User::factory()->create();
    $participant = User::factory()->create();
    $conversation = createDirectConversation($creator, $participant);

    $response = $this->actingAs($participant)->post(route('chat.conversations.store'), [
        'participant_id' => $creator->id,
    ]);

    $response->assertRedirect(route('chat.show', $conversation, absolute: false));

    expect(Conversation::query()->count())->toBe(1);
});
