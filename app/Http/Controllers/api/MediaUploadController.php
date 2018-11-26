<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImageBackup;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use File;
use App\Models\File as FileModel;
use Intervention\Image\Facades\Image as InterventionImage;

class mediaUploadController extends Controller
{
    public function upload(Request $request, Image $image, User $user)
    {
        $file = $request->file('file');
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => "No file specified"
            ], 422);
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

        $code = $image->getCode() . str_random(4);
        // Image parsing done, checking if there's a user;
        $uid = null;
        if ($request->input('token')) {
            $user = $user->where('api_token', $request->input('token'))->first();
            if ($user) {
                $uid = $user->id;
            }
        }

        // get sha1 hash to check if image already exists
        $sha1Hash = sha1_file($file->getRealPath());

        $fileRecord = FileModel::where('sha1_hash', $sha1Hash)->where('size', $size)->first();
        if (!$fileRecord) {
            // Process image through imagick to remove metadata
            if ($ext == 'gif') {
                $tmp_name = str_random(20);
                $file->move('../temp_img/', $tmp_name . '.gif');
                $path = '../temp_img/' . $tmp_name . '.gif';

                $imagick = new \Imagick('../temp_img/' . $tmp_name . '.gif');
                $imagick->stripImage();
                $imagick = $imagick->getImagesBlob();

                unlink('../temp_img/' . $tmp_name . '.gif');
            } else {
                $content = File::get($file);

                $path = $file->getRealPath();

                $imagick = new \Imagick();
                $imagick->readImageBlob($content);
                $imagick->stripImage();
            }

            $thumb = InterventionImage::make($path)->fit(180, 180);

            $thumb->save('../storage/images/thumbnail/' . $code . '.png');

            Storage::disk('images')->put($code . '.' . $ext, $imagick);

        // check if image exists, if not store the image
//        $file = FileModel::where('sha1_hash', $sha1Hash)->where('size', $size)->first();
            // store image
            $disImage = $image->storeImage('../storage/images/' . $code . '.' . $ext);
            $disThumbnail = $image->storeImage('../storage/images/thumbnail/' . $code . '.png');

            $fileRecord = FileModel::create([
                'sha1_hash' => $sha1Hash,
                'size' => $size,
                'location' => $disImage['assign']->fid,
                'thumbnail_location' => $disThumbnail['assign']->fid
            ]);

            if (env('APP_ENV') == 'production') {
                $this->dispatch(new ProcessImageBackup($image));
            }
        }

        $image = $image->create([
            'code' => $code,
            'filename' => htmlspecialchars($name . '.' . $ext),
            'extension' => $ext,
            'ip' => $this->getIp(),
            'active' => true,
            'user_id' => $uid,
            'user_agent' => substr($request->header('User-Agent'), 0, 190),
            'file_id' => $fileRecord->id
        ]);

        Storage::disk('images')->delete($image->code . '.' . $image->extension);
        Storage::disk('thumbnails')->delete($image->code . '.png');

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
