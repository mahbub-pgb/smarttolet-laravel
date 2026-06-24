<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];

    protected function casts(): array
    {
        return [
            'value' => 'array', // stored as JSON; scalar values are wrapped
            'is_secret' => 'boolean',
        ];
    }
}
