<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImageBackup;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Image as ImageModel;
use Illuminate\Support\Facades\Storage;
use File;
use Image;

class mediaUploadController extends Controller
{
    public function upload(Request $request, ImageModel $image, User $user)
    {
        $file = $request->file('file');
        if (!$file) {
            return response()->json([
                'status' => "error",
                'message' => "No file specified"
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $size = $file->getClientSize();
        $name = $file->getClientOriginalName();

        $name = explode('.' . $ext, $name)[0];
        $name = str_replace(' ', '_', $name);
        if (strlen($name) > 180) {
            $name = substr($name, 0, 180);
        }

        $allowed = array('jpg', 'png', 'gif', 'jpeg');
        if (!in_array($ext, $allowed)) {
            return Response('Invalid format', 403);
        }
        if ($size > 6000000) {
            return response('File is too big (exceeds 6mb limit)', 403);
        }

        // Process image through imagick to remove metadata
        if ($ext == 'gif') {
            $tmp_name = str_random(20);
            $file->move('../temp_img/', $tmp_name . '.gif');
            $path = '../temp_img/' . $tmp_name . '.gif';

            $imagick = new \Imagick('../temp_img/' . $tmp_name . '.gif');
            $imagick->stripImage();
            $imagick = $imagick->getImagesBlob();

            $thumb = Image::make($path)->fit(180, 180);

            unlink('../temp_img/' . $tmp_name . '.gif');
        } else {
            $content = File::get($file);

            $path = $file->getRealPath();

            $imagick = new \Imagick();
            $imagick->readImageBlob($content);
            $imagick->stripImage();

            $thumb = Image::make($path)->fit(180, 180);
        }

        // Image parsing done, checking if there's a user;
        $uid = null;
        if ($request->input('token')) {
            $user = $user->where('api_token', $request->input('token'))->first();
            if ($user) {
                $uid = $user->id;
            }
        }

        $code = $image->getCode() . str_random(4);

        $thumb->save('../storage/images/thumbnail/' . $code . '.jpg');

        Storage::disk('images')->put($code . '.' . $ext, $imagick);

        $disImage = $image->storeImage('../storage/images/' . $code . '.' . $ext);
        $disThumbnail = $image->storeImage('../storage/images/thumbnail/' . $code . '.jpg');

        $image = $image->create([
            'code' => $code,
            'filename' => htmlspecialchars($name . '.' . $ext),
            'extension' => $ext,
            'image' => $disImage['assign']->fid,
            'size' => $size,
            'ip' => $this->getIp(),
            'active' => true,
            'user_id' => $uid,
            'user_agent' => substr($request->header('User-Agent'), 0, 190),
            'thumbnail' => $disThumbnail['assign']->fid
        ]);

        $this->dispatch(new ProcessImageBackup($image));

        Storage::disk('images')->delete($image->code . '.' . $image->extension);
        Storage::disk('thumbnails')->delete($image->code . '.jpg');

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $image->code,
                'filename' => htmlspecialchars($image->filename),
                'extension' => $image->extension,
                'url' => env('APP_HOME') . '/' . $image->code,
                'direct_url' => env('IMAGE_CDN') . '/' . $image->code . '.' . $ext,
                'size' => $size
            ]
        ], 200);
    }

    public function getIp()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return substr($_SERVER['HTTP_X_REAL_IP'], 0, 190);
        }
        return '127.0.0.1';
    }
}
