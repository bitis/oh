<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XhsNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'x_id',
        'title',
        'desc',
        'isLiked',
        'type',
        'isLiked',
        'time',
    ];

    public function images()
    {
        return $this->hasMany(XhsImage::class);
    }

    public function video()
    {
        return $this->hasMany(XhsVideo::class);
    }
}
