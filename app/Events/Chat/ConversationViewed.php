<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public ConversationParticipant $participant,
    ) {}
}
