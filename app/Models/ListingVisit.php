<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'visitor_id',
        'visitor_fingerprint',
        'source',
        'visited_on',
    ];

    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_id');
    }
}
