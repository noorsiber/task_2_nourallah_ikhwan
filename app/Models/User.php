<?php

namespace App\Models;

use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Import the trait
use Laravel\Fortify\TwoFactorAuthenticatable;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, TwoFactorAuthenticatable, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'phone_number',
        'email',
        'password',
        'profile_picture',
        'certificate',
        'verification_code',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function enableTwoFactorAuthentication(Request $request)
{
    $user = $request->user(); // Get the authenticated user
    $google2fa = app('pragmarx.google2fa'); // Use the app helper to get the instance

    // Generate the 2FA secret and set it on the user model
    $user->verification_code = $google2fa->generateSecretKey();
    $user->two_factor_enabled = true;

    
    if ($user->save()) {
        // Generate the QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->verification_code
        );

        return response()->json(['qrCodeUrl' => $qrCodeUrl], 200);
    }

    return response()->json(['message' => 'Could not enable 2FA'], 500);
}
    public function verifyTwoFactorToken($token)
    {
        return $this->verification_code === $token;
    }
}