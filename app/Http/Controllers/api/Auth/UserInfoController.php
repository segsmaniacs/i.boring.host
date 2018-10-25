<?php

namespace App\Http\Controllers\api\Auth;

use App\Helpers\SeaweedStorage;
use App\Helpers\WebDav;
use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Hash;

class UserInfoController extends Controller
{

    public function __construct()
    {
        Validator::extend('old_password', function ($attribute, $value, $parameters, $validator) {

            return Hash::check($value, current($parameters));

        }, 'Invalid Password');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json([
           'status' => 'success',
           'data' => [
               'id' => $user->id,
               'username' => $user->username,
               'email' => $user->email,
               'api_token' => $user->api_token
           ]
        ], 200);
    }

    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:191|unique:users,email',
            'password' => 'required|old_password:'.$request->user()->password
        ]);
        if ($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $user = $request->user();
        $user->email = $request->input('email');
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'api_token' => $user->api_token
            ]
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|old_password:'.$request->user()->password,
            'password' => 'required|string|min:6|confirmed'
        ]);
        if ($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $user = $request->user();
        $user->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'api_token' => $user->api_token
            ]
        ]);
    }

    public function getPosts(Request $request, Image $image)
    {
        $user = $request->user();
        $page = $request->input('page');
        if (!$page || !is_numeric($page)) {
            $page = 1;
        }

        $skip = 42 * $page - 42;

        $posts = $image->where('user_id', $user->id)->where('active', 1)
            ->skip($skip)->take(42)->orderBy('created_at', 'desc')->get();


        $postsArray = [];

        foreach ($posts as $post) {
            array_push($postsArray, [
                'id' => $post->id,
                'user_id' => $post->user_id,
                'code' => $post->code,
                'filename' => $post->filename,
                'extension' => $post->extension,
                'url' => env('APP_HOME') . '/' . $post->code,
                'direct_url' => env('IMAGE_ROOT_DOMAIN', url('/')) . '/' . $post->code . '.' . $post->extension,
                'thumbnail_url' => env('IMAGE_ROOT_DOMAIN', url('/')) . '/thumbnail/' . $post->code . '.jpg',
                'size' => $post->size
            ]);
        }

        return response()->json([
            'status' => 'success',
            'posts' => $postsArray
        ]);
    }

    public function deletePost(Request $request, Image $image)
    {
        $code = $request->input('code');
        $user = $request->user();

        $image = $image->where('code', $code)->first();
        if (!$image) {
            return response()->json([
               'status' => 'error',
               'data' => [
                   'message' => 'Image does not exist'
               ]
            ]);
        }
        if ($image->user_id != $user->id) {
            return response()->json([
               'status' => 'error',
               'data' => [
                   'message' => 'Image does not belong to you'
               ]
            ]);
        }

        $image->active = false;

        (new WebDav())->deleteFile($image->stack_location);
        $image->stack_location = null;
        
        $image->save();

//        Storage::disk('spaces')->delete(explode(',', $image->image)[0] . '/' . $image->code . '.' . $image->extension);

        $seaweedStorage = new SeaweedStorage();
        $seaweedStorage->delete($image->image);
        $seaweedStorage->delete($image->thumbnail);

        return response()->json([
           'status' => 'success',
           'data' => [
               'message' => 'Image removed'
           ]
        ]);
    }
}
