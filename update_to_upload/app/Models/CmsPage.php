<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $guarded = [];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
