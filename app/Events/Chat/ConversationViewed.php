<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationViewed implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public ConversationParticipant $participant,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.conversations.{$this->conversation->id}"),
        ];
    }

    /**
     * Get the event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'chat.conversation.viewed';
    }

    /**
     * Get the data to broadcast with the event.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'participant_id' => $this->participant->id,
            'user_id' => $this->participant->user_id,
            'last_read_message_id' => $this->participant->last_read_message_id,
            'last_read_at' => $this->participant->last_read_at?->toISOString(),
        ];
    }
}
