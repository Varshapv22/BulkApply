<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public static function log(string $type, string $message, array $payload = []): void
    {
        static::create(['type' => $type, 'message' => $message, 'payload' => $payload]);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
