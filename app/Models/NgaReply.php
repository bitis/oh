<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NgaReply extends Model
{
    use HasFactory;

    protected $fillable = ['reply_id', 'content', 'author', 'authorid', 'subject', 'subject_id', 'postdate', 'notified'];
}
