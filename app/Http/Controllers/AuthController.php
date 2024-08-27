<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)  {
        $fields = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'phone_number' => 'required|integer|unique:users,phone_number',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'profile_picture' => 'required|string',
            'certificate' => 'required|string'
        ]);
    //     if ($request->file('profile_picture')->isValid()) {
    //         // Store the file in the 'uploads' directory on the 'public' disk
    //         $filePath = $request->file('profile_picture')->store('uploads', 'public');
    //         // Return success response
    //         return back()->with('success', 'File uploaded successfully')->with('profile_picture', $filePath);
    //     }
    //     // Return error response
    //     return back()->with('error', 'File upload failed');
    

    // if ($request->file('certificate')->isValid()) {
    //     // Store the file in the 'uploads' directory on the 'public' disk
    //     $filePath = $request->file('certificate')->store('uploads', 'public');
    //     // Return success response
    //     return back()->with('success', 'File uploaded successfully')->with('certificate', $filePath);
    // }
    // // Return error response
    // return back()->with('error', 'File upload failed');

        $user = User::create([
            'name' => $fields['name'],
            'username' => $fields['username'],
            'phone_number' => $fields['phone_number'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['email']),
            'profile_picture' => $fields['profile_picture'],
            'certificate' => $fields['certificate']
        ]);

        // $token = $user->createToken('myapptoken')->plainTextToken;
        // $accessToken = $user->createToken('access_token',[], Carbon::now()->addMinutes(10));
        // $refreshToken = $user->createToken('refresh_token',[ ], Carbon::now()->addMinutes(20));
        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));


        $response = [
            'user' => $user,
            // 'token' => $token,
            'token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'profile_picture' => $fields['profile_picture']
        ];

        return response($response, 201);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        //Check email
        $user = User::where('email', $fields['email'])->first();

        //Check password
        if(!$user || !Hash::check($fields['password'], $user->password)){
            return response([
                'message' => 'Bad credentials'
            ], 401);
        }
    }
    
    public function logout(Request $request) {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logged Out'
        ];
    }

    public function refreshToken(Request $request)
    {
        $accessToken = $request->user()->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        return response(['message' => "Token generated", 'token' => $accessToken->plainTextToken]);
    }

}
