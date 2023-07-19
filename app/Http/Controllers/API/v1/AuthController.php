<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        // Validate Inputs
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt Login Using Only Email and Password
        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);
        $user = Auth::user();

        // Create Appropriate Response
        $response = (!$token)
            ? response()->json([
                'message' => 'Invalid Email or Password'
            ], 401)
            : response()->json([
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'Bearer'
                ]
            ], 200);

        //Send Response
        return $response;
    }

    public function register(Request $request)
    {
        // Validate Inputs
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email',
            'sex' => 'required|string',
            'password' => 'required|string|min:8|max:20',
            'confirm_password' => 'required|string|min:8|max:20'
        ]);

        // Check If Email Exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'User Already Exists'
            ], 409);
        }

        // Create User
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'sex' => $request->sex,
            'password' => Hash::make($request->password),
        ]);

        $credentials = $request->only('email', 'password');

        // Login User
        $token = Auth::attempt($credentials);

        // Return Response
        return response()->json([
            'message' => 'User Created Successfully',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'Bearer'
            ]
        ], 201);
    }

    public function logout()
    {
        // Log Users Out
        Auth::logout();
        return response()->json([
            'message' => 'You have successfully logged out'
        ], 200);
    }

    public function refresh()
    {
        // Refresh Authentication Token
        return response()->json([
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ], 200);
    }
}