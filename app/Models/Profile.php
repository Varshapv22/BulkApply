<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $guarded = [];

    /**
     * There is only ever one profile row (single-user app). Fetch it, or make
     * an empty one so views/controllers never have to null-check.
     */
    public static function current(): self
    {
        return static::first() ?? new static();
    }

    public function hasDocuments(): bool
    {
        return filled($this->resume_path) && filled($this->cover_letter_path);
    }
}
