<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Events\MessageSent;
use App\Events\MessageStatusUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * @return LengthAwarePaginator<Message>
     */
    public function paginate(Conversation $conversation, int $perPage, int $page): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with('sender:id,name,photo')
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    public function send(Conversation $conversation, User $sender, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $body) {
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'body' => $body,
                'status' => Message::STATUS_SENT,
            ]);

            $conversation->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ])->save();

            return $message;
        });

        // Realtime to the conversation channel.
        broadcast(new MessageSent($message))->toOthers();

        // Persistent notification to the recipient.
        $recipientId = $conversation->otherParticipantId($sender->id);
        $this->notifications->notify($recipientId, 'message_received', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'from' => $sender->name ?? $sender->mobile,
            'preview' => mb_substr($body, 0, 80),
        ]);

        return $message->load('sender:id,name,photo');
    }

    /**
     * Mark the other participant's messages as read for this user, broadcasting
     * the status change.
     */
    public function markRead(Conversation $conversation, User $reader): int
    {
        $ids = $conversation->messages()
            ->where('sender_id', '!=', $reader->id)
            ->where('status', '!=', Message::STATUS_READ)
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            return 0;
        }

        Message::whereIn('id', $ids)->update([
            'status' => Message::STATUS_READ,
            'read_at' => now(),
        ]);

        broadcast(new MessageStatusUpdated($conversation->id, $ids, Message::STATUS_READ));

        return count($ids);
    }

    /**
     * Mark incoming messages as delivered (e.g. when the recipient connects).
     */
    public function markDelivered(Conversation $conversation, User $recipient): int
    {
        $ids = $conversation->messages()
            ->where('sender_id', '!=', $recipient->id)
            ->where('status', Message::STATUS_SENT)
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            return 0;
        }

        Message::whereIn('id', $ids)->update(['status' => Message::STATUS_DELIVERED]);

        broadcast(new MessageStatusUpdated($conversation->id, $ids, Message::STATUS_DELIVERED));

        return count($ids);
    }
}
