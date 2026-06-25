<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single rejection event in a listing's moderation history.
 *
 * @property int $id
 * @property int $listing_id
 * @property int|null $moderator_id
 * @property string $reason
 */
class ListingRejection extends Model
{
    protected $fillable = [
        'listing_id',
        'moderator_id',
        'reason',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
