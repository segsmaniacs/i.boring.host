<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::group(['prefix' => '', 'middleware' => ['throttle:20,5']], function () {

    Route::post('/upload', 'api\MediaUploadController@upload');
    Route::get('/upload', function() {
        return response("that's not how this works.");
    });

    Route::post('/login', 'api\Auth\LoginController@login')->name('api.login');
    Route::post('/register', 'api\Auth\RegisterController@register')->name('api.register');

});

Route::group(['prefix' => '', 'middleware' => ['throttle:50,5', 'auth:api']], function () {

    Route::get('/user/info', 'api\Auth\UserInfoController@index');
    Route::get('/user/posts', 'api\Auth\UserInfoController@getPosts');
    Route::post('/user/delete', 'api\Auth\UserInfoController@deletePost');

    Route::post('/user/update/email', 'api\Auth\UserInfoController@updateEmail');
    Route::post('/user/update/password', 'api\Auth\UserInfoController@updatePassword');
});


Route::group(['prefix' => '', 'middleware' => ['throttle:100,5' ]], function () {

    Route::get('/info/{code}', 'HandleImageController@getinfo');

});