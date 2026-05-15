<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;

test('chat models define their relationships', function () {
    $creator = User::factory()->create();
    $participant = User::factory()->create();

    $conversation = Conversation::create([
        'type' => 'direct',
        'title' => null,
        'created_by_user_id' => $creator->id,
        'direct_key' => "{$creator->id}:{$participant->id}",
        'last_message_at' => now(),
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $creator->id,
        'body' => 'Hola',
        'metadata' => ['kind' => 'text'],
        'edited_at' => null,
    ]);

    $conversation->forceFill([
        'last_message_id' => $message->id,
    ])->save();

    $creatorMembership = ConversationParticipant::create([
        'conversation_id' => $conversation->id,
        'user_id' => $creator->id,
        'role' => 'owner',
        'joined_at' => now(),
        'last_read_at' => now(),
        'last_read_message_id' => $message->id,
    ]);

    $participantMembership = ConversationParticipant::create([
        'conversation_id' => $conversation->id,
        'user_id' => $participant->id,
        'role' => 'member',
        'joined_at' => now(),
        'last_read_at' => now(),
        'last_read_message_id' => $message->id,
    ]);

    expect($conversation->creator->is($creator))->toBeTrue();
    expect($conversation->lastMessage->is($message))->toBeTrue();
    expect($conversation->messages)->toHaveCount(1);
    expect($conversation->messages->first()->is($message))->toBeTrue();
    expect($conversation->participants)->toHaveCount(2);
    expect($conversation->participantRecords)->toHaveCount(2);
    expect($creator->createdConversations)->toHaveCount(1);
    expect($creator->createdConversations->first()->is($conversation))->toBeTrue();
    expect($creator->conversationParticipants->first()->is($creatorMembership))->toBeTrue();
    expect($creator->conversations->first()->is($conversation))->toBeTrue();
    expect($participant->conversationParticipants->first()->is($participantMembership))->toBeTrue();
    expect($participant->conversations->first()->is($conversation))->toBeTrue();
    expect($message->conversation->is($conversation))->toBeTrue();
    expect($message->sender->is($creator))->toBeTrue();
    expect($creatorMembership->conversation->is($conversation))->toBeTrue();
    expect($creatorMembership->user->is($creator))->toBeTrue();
    expect($creatorMembership->lastReadMessage->is($message))->toBeTrue();
    expect($participantMembership->conversation->is($conversation))->toBeTrue();
    expect($participantMembership->user->is($participant))->toBeTrue();
    expect($participantMembership->lastReadMessage->is($message))->toBeTrue();
});
