<?php

namespace App\Http\Controllers\api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Lang;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function login(Request $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return response()->json([
                'status' => 'error',
                'message' => 'You are going to fast'
            ], 403);
        }

        $login_type = filter_var($request->input('username'), FILTER_VALIDATE_EMAIL )
            ? 'email'
            : 'username';

        $request->merge([
            $login_type => $request->input('username')
        ]);

        if (Auth::attempt($request->only($login_type, 'password'))) {
            $user = $request->user();
            if (!$user->api_token) {
                $user->api_token = str_random(40);
                $user->save();
            }
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'api_token' => $user->api_token
                ]
            ]);
        } else {
            $this->incrementLoginAttempts($request);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ], 200);
        }
    }
}
