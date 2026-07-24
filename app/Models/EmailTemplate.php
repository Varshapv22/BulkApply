<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultTemplate(?int $userId = null): ?self
    {
        return static::where('user_id', $userId)->where('is_default', true)->first();
    }
}
