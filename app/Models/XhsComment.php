<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XhsComment extends Model
{
    use HasFactory;

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

    public function subComments()
    {
        return $this->hasMany(XhsComment::class, 'targetNoteId', 'id');
    }
}
