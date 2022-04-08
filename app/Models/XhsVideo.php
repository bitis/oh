<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XhsVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'xsh_note_id',
        'x_id',
        'height',
        'width',
        'url',
    ];
}
