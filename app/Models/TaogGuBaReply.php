<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaogGuBaReply extends Model
{
    use HasFactory;

    protected $fillable = ['reply_id', 'user_name', 'date', 'from', 'from_url', 'content', 'url', 'images', 'original'];

    protected $casts = [
        'images' => 'array',
    ];
}
