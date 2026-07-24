<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class IpRule extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('ip_block_rules'));
        static::deleted(fn () => Cache::forget('ip_block_rules'));
    }

    /** @return array<int, string> */
    public static function blockedIps(): array
    {
        return Cache::rememberForever('ip_block_rules', fn () => static::where('type', 'block')->pluck('ip_or_cidr')->all());
    }
}
