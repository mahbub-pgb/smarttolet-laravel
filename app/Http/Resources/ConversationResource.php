<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $me = $request->user()?->id;
        $other = $me && (int) $this->participant_a === $me ? $this->participantB : $this->participantA;

        return [
            'id' => $this->id,
            'listing' => $this->whenLoaded('listing', fn () => $this->listing ? [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
                'slug' => $this->listing->slug,
            ] : null),
            'participant' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
                'photo' => $other->photo,
            ] : null,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
