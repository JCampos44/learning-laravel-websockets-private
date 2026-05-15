<?php

use App\Events\Chat\ConversationViewed;
use App\Events\Chat\MessageSent;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Queue;

it('broadcast events are queued on the database connection', function (): void {
    expect(config('queue.connections.database.driver'))->toBe('database');
    expect(config('queue.connections.database.after_commit'))->toBeTrue();
});

it('queues the message broadcast job', function (): void {
    Queue::fake();

    $message = Message::factory()->create();

    MessageSent::dispatch($message);

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($message): bool {
        return $job->event instanceof MessageSent
            && $job->event->message->is($message);
    });
});

it('queues the conversation viewed broadcast job', function (): void {
    Queue::fake();

    $participant = ConversationParticipant::factory()->create();

    ConversationViewed::dispatch($participant->conversation, $participant);

    Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) use ($participant): bool {
        return $job->event instanceof ConversationViewed
            && $job->event->participant->is($participant);
    });
});

it('authorizes conversation broadcast channels for participants', function (): void {
    $creator = User::factory()->create();
    $participant = User::factory()->create();
    $conversation = createDirectConversation($creator, $participant);

    $this->actingAs($participant)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-chat.conversations.{$conversation->id}",
        ])
        ->assertOk();
});
