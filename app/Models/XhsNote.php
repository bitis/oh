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

    /**
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($note)  {
            $this->notify($note->title, $note->desc . "\n[Link](https://www.xiaohongshu.com/discovery/item/{$note['x_id']})" . "\n" . $note->time . "\n" . $note->nickname);
        });
    }

    public function images()
    {
        return $this->hasMany(XhsImage::class);
    }

    public function video()
    {
        return $this->hasMany(XhsVideo::class);
    }
}
