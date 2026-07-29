<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    /** Cap per user — protects the scheduled command's fan-out and Adzuna quota from a single heavy user. */
    public const MAX_PER_USER = 10;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_baselined' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seenListings()
    {
        return $this->hasMany(SavedSearchSeenListing::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Query params for linking back to the Find Jobs page with this search prefilled. */
    public function toSearchQuery(): array
    {
        return array_filter([
            'role' => $this->role,
            'location' => $this->location,
            'site' => $this->site,
        ], fn ($v) => filled($v));
    }
}
