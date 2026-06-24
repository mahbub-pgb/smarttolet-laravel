<?php

declare(strict_types=1);

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channels are authorised here. The websocket connection is
| authenticated with the access token (see the broadcasting auth route),
| which resolves the user via the JWT guard.
|
*/

// Each user has a private channel for direct notifications / message events.
Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

// A conversation channel is open to its two participants.
Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return in_array((int) $user->id, [
        (int) $conversation->participant_a,
        (int) $conversation->participant_b,
    ], true);
});
