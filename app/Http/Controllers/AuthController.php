<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use App\Events\UserLoggedIn;
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

    
        $user = User::create([
            'username' => $fields['username'],
            'phone_number' => $fields['phone_number'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'profile_picture' => $profilePicturePath,
            'certificate' => $certificatePath
        ]);


        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));
        
        //send a welcome email
        Mail::to('testreceiver@gmail.com')->send(new WelcomeMail('jon'));        
        
        $response = [
            'user' => $user,
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ];

        return response($response, 201);
    }

    public function generateVerificationCode()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = substr(str_shuffle(str_repeat($characters,6)), 0, 6);
        return $code;
    }
    
    public function login(Request $request) {
        // Validate the incoming request
        $request->validate([
            'identifier' => 'required|string', // Use 'identifier' for email or phone
            'password' => 'required|string',
        ]);

        // Check if the identifier is an email or phone number
        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        
        // Attempt to authenticate the user
        $credentials = [$field => $request->identifier, 'password' => $request->password];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
    
            if ($user->two_factor_enabled) {
                // Send the existing verification code to the user
                event(new UserLoggedIn($user, $user->verification_code));

                return response()->json(['message' => 'Please enter your 2FA token.'], 401);
            }

            // If 2FA is not enabled, generate and return the token
            $authToken = $user->createToken('2FAToken')->plainTextToken;
            return response()->json(['token' => $authToken]);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    public function verifyTwoFactor(Request $request)
    {
        // Validate the request
        $request->validate([
            'two_factor_token' => 'required',
        ]);

        $user = Auth::user();
        if(!$user){
            return response()->json(['messge' => 'Unauthorized'], 401);
        }

        // Check if the verification code matches and is not expired
        if ($user->verification_code === $request->two_factor_token &&
        now()->isBefore($user->verification_code_expires_at)) {
        
            // Reset the verification code and expiry
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            $user->save();

            // Generate and return a new authentication token
            $authToken = $user->createToken('2FAToken')->plainTextToken;
            return response()->json(['token' => $authToken], 200);
        }
        return response()->json(['message' => 'Invalid or expired token'], 401);
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

    public function enableTwoFactorAuthentication(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'password' => 'required',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Check if the provided password is correct
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password'], 403);
        }

        // Generate a verification code
        $twoFAToken = $this->generateVerificationCode();

        // Store the verification code and set expiry time
        $user->verification_code = $twoFAToken;
        $user->verification_code_expires_at = now()->addMinutes(10);
        $user->two_factor_enabled = true; // Set 2FA enabled flag
        $user->save();

        // Dispatch an event to send the verification code (e.g., via email or SMS)
        event(new UserLoggedIn($user, $twoFAToken));

        return response()->json(['message' => 'Two-factor authentication enabled.'], 200);
    }

}