<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'successful' => 'boolean',
        'created_at' => 'datetime',
    ];
}
