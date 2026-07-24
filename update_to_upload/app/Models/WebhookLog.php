<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
    ];

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
}
