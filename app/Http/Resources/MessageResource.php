<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            'status' => $this->status,
            'read_at' => $this->read_at?->toIso8601String(),
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'photo' => $this->sender->photo,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
