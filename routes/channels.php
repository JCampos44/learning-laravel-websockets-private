<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.conversations.{conversation}', function (
    User $user,
    Conversation $conversation,
): bool {
    return $conversation->participantRecords()
        ->where('user_id', $user->id)
        ->whereNull('left_at')
        ->exists();
});
