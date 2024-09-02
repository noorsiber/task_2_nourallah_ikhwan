<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
// use App\Mail\SampleEmail;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    public function register(Request $request)  {
        $fields = $request->validate([
            'username' => 'required|string|unique:users,username',
            'phone_number' => 'required|integer|unique:users,phone_number',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'certificate' => 'required|mimes:pdf|max:2048'
        ]);
         // Handle Profile Photo
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_picture', 'public');
        }

        // Handle Certificate
        if ($request->hasFile('certificate')) {
            $certificatePath = $request->file('certificate')->store('certificates', 'public');
        }

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
            'username' => $fields['username'],
            'phone_number' => $fields['phone_number'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'profile_picture' => $profilePicturePath,
            'certificate' => $certificatePath
        ]);

        // $token = $user->createToken('myapptoken')->plainTextToken;
        // $accessToken = $user->createToken('access_token',[], Carbon::now()->addMinutes(10));
        // $refreshToken = $user->createToken('refresh_token',[ ], Carbon::now()->addMinutes(20));
        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));
        /*$mail_message = */ Mail::to('testreceiver@gmail.com')->send(new WelcomeMail("Jon"));

        //  $mail_message= (Mail::to('testreceiver@gmail.com')->send(new WelcomeMail("Jon"))) == True ? "successfully sent an email" : "didn't send an email";
        
        

        $response = [
            'user' => $user,
            // 'token' => $token,
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            // 'regesteration_email' => $mail_message
        ];

        return response($response, 201);
    }

    public function login(Request $request) {
        // $fields = $request->validate([
        //     'email' => 'required|string',
        //     'password' => 'required|string',
        // ]);

        // //Check email
        // $user = User::where('email', $fields['email'])->first();

        // //Check password
        // if(!$user || !Hash::check($fields['password'], $user->password)){
        //     return response([
        //         'message' => 'Bad credentials'
        //     ], 401);
        // }
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
    
            if ($user->two_factor_enabled) {
                return response()->json(['message' => 'Please enter your 2FA token.'], 401);
            }
    
            // Generate and return token
            $twoFAtoken = $user->createToken('2FAToken')->plainTextToken;
            return response()->json(['token' => $twoFAtoken]);
        }
    
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    public function verifyTwoFactor(Request $request)
    {
        // Validate the request
        $request->validate([
            'two_factor_secret' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Attempt to authenticate the user
        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Now the user is authenticated
        $user = Auth::user();

        // Verify the 2FA token
        if ($user->verifyTwoFactorToken($request->two_factor_secret)) {
            $twoFATokenVerification = $user->createToken('2FATokenVerification')->plainTextToken;
            return response()->json(['2fatoken_verification' => $twoFATokenVerification]);
            
        }

        return response()->json(['message' => 'Invalid token'], 401);
    }
        // $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        // $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

        // $response = [
        //     'user' => $user,
        //     // 'token' => $token,
        //     'token' => $accessToken->plainTextToken,
        //     'refresh_token' => $refreshToken->plainTextToken,
        //     // 'profile_picture' => $fields['profile_picture']
        // ];

        // return response($response, 201);
    
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

    public function enableTwoFactorAuthentication(Request $request)
    {
        // \Log::info('Authenticated User:', ['user' => $request->user()]);
        $user = $request->user(); // Get the authenticated user
        $google2fa = app('pragmarx.google2fa');

        // Generate the 2FA secret and set it on the user model
        $user->two_factor_secret = $google2fa->generateSecretKey();
        $user->two_factor_enabled = true;

        // Save the user instance
        if ($user->save()) {
            // Generate the QR code URL
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'), // Your app name
                $user->email, // User's email
                $user->two_factor_secret // The secret key
            );

            return response()->json(['qrCodeUrl' => $qrCodeUrl], 200);
        }

        return response()->json(['message' => 'Could not enable 2FA'], 500);
    }

}
