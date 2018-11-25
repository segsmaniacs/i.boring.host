<?php

namespace App\Models;

use App\Helpers\Math;
use App\Helpers\SeaweedStorage;
use App\Helpers\WebDav;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'filename',
        'extension',
        'image',
        'size',
        'ip',
        'active',
        'user_id',
        'user_agent',
        'thumbnail',
        'file_id',
        'stack_location'
    ];

    public function getCode()
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $math = new Math();
        if (!$last) {
            return $math->toBase(1);
        }
        // because I started at a mil so there's some overlap
        if ($last->id >= 1000000 && $last->id < 1002000) {
            return $math->toBase($last->id + 1) . str_random(4);
        }
        return $math->toBase($last->id + 1);
    }

    public function getLongCode()
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $math = new Math();
        if (!   $last) {
            return $math->toBase(0 + 1000000 + 1);
        }
        return $math->toBase($last->id + 1000000 + 1);
    }

    /**
     * Get the file
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }


    public function storeImage($filePath)
    {
        $storage = new SeaweedStorage();
        $assigned = $storage->assign();

        return $storage->upload($assigned, $filePath);
    }

    public function storeImageToWebDav($image, $code, $ext, $content)
    {
        $last = $image ? $image : $this->orderBy('id', 'DESC')->first();
        $nextID = $last ? $last->id + 1 : 1;

        $stackLocation = 'boring.host/2018/' . floor($nextID / 5000) . '/' . $code . '.' . $ext;

//        $storageFolder = floor($nextID / 5000);
        (new WebDav())->createFile($stackLocation, $content);

        return $stackLocation;
    }
}
