<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'owner_id',
        'url',
        'public_id',
        'disk',
        'type',
        'mime',
        'size',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'meta' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
