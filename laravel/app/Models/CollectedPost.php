<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectedPost extends Model
{
    protected $fillable = ['page_id', 'post_id', 'message', 'created_time'];

    protected $casts = ['created_time' => 'datetime'];
}
