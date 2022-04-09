<?php

namespace App\Models;

use App\Models\Traits\DingTalkNotify;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XhsComment extends Model
{
    use HasFactory, DingTalkNotify;

    protected $fillable = [
        'x_id',
        'parent_id',
        'isSubComment',
        'content',
        'likes',
        'isLiked',
        'time',
        'nickname',
        'user_id',
    ];

    /**
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($comment)  {
            $this->notify('评论', $comment->content . "\t" . $comment->time . "\t" . $comment->nickname);
        });
    }

    public function subComments()
    {
        return $this->hasMany(XhsComment::class, 'targetNoteId', 'id');
    }
}
