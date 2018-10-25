<?php

namespace App\Http\Controllers;

use App\Helpers\SeaweedStorage;
use App\Helpers\WebDav;
use App\Models\Visit;
use Illuminate\Http\Request;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class HandleImageController extends Controller
{
    public function getinfo($code, Request $request, Image $image)
    {
        $image = $image->select('id', 'code', 'filename', 'extension', 'size', 'user_id')->where('code', $code)->where('active', 1)->first();

        if (!$image) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image not found'
            ]);
        }

        $visit = Visit::where('ip', $_SERVER['HTTP_X_REAL_IP'])->where('time_stamp', '>', time()-600)->where('image_id', $image->id)->first();
        if (!$visit) {
            Visit::create([
                'image_id' => $image->id,
                'ip' => substr($_SERVER['HTTP_X_REAL_IP'], 0, 190),
                'user_agent' => substr($request->header('User-Agent'), 0, 190),
                'time_stamp' => time()
            ]);
        }

        $viewCount = Visit::where('image_id', $image->id)->count();


        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $image->code,
                'filename' => $image->filename,
                'extension' => $image->extension,
                'size' => $image->size,
                'views' => $viewCount,
                'user_id' => $image->user_id,
                'url' => env('APP_HOME') . '/' . $image->code,
                'direct_url' => env('IMAGE_ROOT_DOMAIN') . '/' . $image->code . '.' . $image->extension
            ]
        ]);
    }


    public function test()
    {
        return view('upload_form');
    }
}
