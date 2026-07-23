<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ApiConfig extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'encrypted',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('api_configs'));
        static::deleted(fn () => Cache::forget('api_configs'));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $values = Cache::rememberForever('api_configs', function () {
            return static::where('active', true)->get()->mapWithKeys(fn ($c) => [$c->key => $c->value]);
        });

        return filled($values[$key] ?? null) ? $values[$key] : $default;
    }
}
