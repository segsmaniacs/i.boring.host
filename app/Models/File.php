<?php

namespace App\Models;

use App\Helpers\WebDav;
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

    public function images()
    {
        return $this->hasMany(Image::class, 'file_id', 'id');
    }

    public function storeFileToWebDav($file, $ext, $content)
    {
        $last = $file ? $file : $file->orderBy('id', 'DESC')->first();
        $nextID = $last ? $last->id + 1 : 1;

        $stackLocation = 'boring.host/2018/' . floor($nextID / 5000) . '/' . $file->sha1_hash . '.' . $ext;

//        $storageFolder = floor($nextID / 5000);
        (new WebDav())->createFile($stackLocation, $content);

        return $stackLocation;
    }
}