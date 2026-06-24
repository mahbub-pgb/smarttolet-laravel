<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoSms extends Model
{
    use HasFactory;

    protected $table = 'promo_sms';

    protected $fillable = [
        'created_by',
        'title',
        'message',
        'recipients',
        'audience',
        'status',
        'total',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'total' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
