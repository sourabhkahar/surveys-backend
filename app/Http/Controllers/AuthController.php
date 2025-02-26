<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|string|unique:users,email',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols()
                ]
            ]);

            $user = User::Create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $token  = $user->createToken('main')->plainTextToken;

            return response([
                'status' => 'success',
                'data' => ['user' => $user, 'token' => $token],
                'message' => 'user create successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email|string|exists:users,email',
                'password' => [
                    'required',
                ],
                'remember' => 'boolean'
            ]);
            $remember = $credentials['remember'] ?? false;
            unset($credentials['remember']);

            if (!Auth::attempt($credentials, $remember)) {
                return response([
                    'status' => '422',
                    'message' => 'Invalid Credentials!'
                ]);
            }
            $user = Auth::user();
            $token = $user->createToken('main')->plainTextToken;

            return response([
                'status' => 'success',
                'data' => ['user' => $user, 'token' => $token],
                'message' => 'User Login successfully!'
            ]);
        } catch (\Exception $e) {
            return response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function logout(){
        try {
            $user = Auth::user();
            $user->currentAccessToken()->delete();
            
            return response([
                'status' => 'success',
                'message' => 'User logout successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
