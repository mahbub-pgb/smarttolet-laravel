<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactView extends Model
{
    use HasFactory;

    protected $fillable = ['listing_id', 'viewer_id', 'viewer_fingerprint'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
