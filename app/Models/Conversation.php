<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_a',
        'participant_b',
        'listing_id',
        'last_message_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function participantA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_a');
    }

    public function participantB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_b');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('participant_a', $userId)->orWhere('participant_b', $userId);
        });
    }

    public function involves(int $userId): bool
    {
        return (int) $this->participant_a === $userId || (int) $this->participant_b === $userId;
    }

    public function otherParticipantId(int $userId): int
    {
        return (int) $this->participant_a === $userId
            ? (int) $this->participant_b
            : (int) $this->participant_a;
    }
}
