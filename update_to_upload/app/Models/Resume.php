<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = ['user_id', 'name', 'file_path', 'is_default'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
