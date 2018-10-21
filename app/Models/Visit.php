<?php

namespace App\Models;

use App\Helpers\Math;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'image_id',
        'ip',
        'user_agent',
        'time_stamp'
    ];


    public function getCode()
    {
        $last = $this->orderBy('id', 'DESC')->first();
        $math = new Math();
        if (!$last) {
            return $math->toBase(0 + 1000000 + 1);
        }
        return $math->toBase($last->id + 1000000 + 1);
    }
}
