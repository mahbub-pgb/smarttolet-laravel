<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Exceptions\ApiException;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;

class ConversationService
{
    /**
     * Find or create the unique conversation between two users about a listing.
     * Participants are stored in a canonical order (lower id first) so the
     * unique constraint matches regardless of who initiates.
     */
    public function findOrCreate(User $initiator, int $otherUserId, ?int $listingId = null): Conversation
    {
        if ($initiator->id === $otherUserId) {
            throw ApiException::badRequest('You cannot start a conversation with yourself.', 'invalid_participant');
        }

        if (! User::whereKey($otherUserId)->exists()) {
            throw ApiException::notFound('Recipient not found.', 'user_not_found');
        }

        if ($listingId !== null && ! Listing::whereKey($listingId)->exists()) {
            throw ApiException::notFound('Listing not found.', 'listing_not_found');
        }

        [$a, $b] = $this->orderedPair($initiator->id, $otherUserId);

        try {
            return Conversation::firstOrCreate(
                ['participant_a' => $a, 'participant_b' => $b, 'listing_id' => $listingId],
            );
        } catch (QueryException) {
            // Lost a race to the unique index; fetch the existing row.
            return Conversation::where('participant_a', $a)
                ->where('participant_b', $b)
                ->where('listing_id', $listingId)
                ->firstOrFail();
        }
    }

    /**
     * @return LengthAwarePaginator<Conversation>
     */
    public function listForUser(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        return Conversation::query()
            ->forUser($user->id)
            ->with(['participantA:id,name,photo', 'participantB:id,name,photo', 'listing:id,title,slug'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Authorise + return a conversation the user participates in.
     */
    public function authorizeAccess(User $user, int $conversationId): Conversation
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation) {
            throw ApiException::notFound('Conversation not found.', 'conversation_not_found');
        }

        if (! $conversation->involves($user->id)) {
            throw ApiException::forbidden('You are not part of this conversation.', 'not_participant');
        }

        return $conversation;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function orderedPair(int $x, int $y): array
    {
        return $x < $y ? [$x, $y] : [$y, $x];
    }
}
