<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearchSeenListing extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
    ];

    public function savedSearch()
    {
        return $this->belongsTo(SavedSearch::class);
    }
}
