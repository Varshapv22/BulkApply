<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverLetter extends Model
{
    protected $fillable = ['user_id', 'name', 'file_path', 'text', 'mode', 'is_default'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
