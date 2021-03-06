<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XhsImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'xhs_note_id',
        'fileId',
        'height',
        'width',
        'url',
    ];

}
