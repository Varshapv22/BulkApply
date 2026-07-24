<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('feature_flags'));
        static::deleted(fn () => Cache::forget('feature_flags'));
    }

    /** True if the flag is enabled, or doesn't exist (fail-open — an unseeded flag shouldn't break the app). */
    public static function enabled(string $key): bool
    {
        $flags = Cache::rememberForever('feature_flags', fn () => static::pluck('enabled', 'key'));

        return $flags[$key] ?? true;
    }
}
