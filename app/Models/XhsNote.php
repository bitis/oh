<?php

namespace App\Models;

use App\Models\Traits\DingTalkNotify;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XhsNote extends Model
{
    use HasFactory, DingTalkNotify;

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
            static::notify($note->title, $note->desc . "\n\n[Link](https://www.xiaohongshu.com/discovery/item/{$note['x_id']})" . "\n\n" . $note->time . "\n\n" . $note->nickname);
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
