<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmailReply extends Model
{
    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
        'is_read'     => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
}
