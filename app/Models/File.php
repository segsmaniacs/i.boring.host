<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    protected $table = 'files';

    protected $fillable = [
        'sha1_hash',
        'size',
        'location',
        'thumbnail_location',
        'stack_location'
    ];
}