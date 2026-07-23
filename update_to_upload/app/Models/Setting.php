<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings'));
        static::deleted(fn () => Cache::forget('settings'));
    }

    private static function cached()
    {
        return Cache::rememberForever('settings', fn () => static::all()->keyBy('key'));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::cached()[$key] ?? null;

        if (!$setting || $setting->value === null) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /** Validation rules for a resume/cover-letter file upload, sourced from admin-configurable limits. */
    public static function uploadRules(): array
    {
        $maxKb = (int) self::get('max_resume_size_mb', 10) * 1024;
        $types = self::get('allowed_resume_types', 'pdf,doc,docx');

        return ['file', "mimes:{$types}", "max:{$maxKb}"];
    }
}
