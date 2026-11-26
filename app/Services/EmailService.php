<?php

namespace App\Services;

use App\Models\EmailInvitation;
use App\Models\PasswordReset;
use App\Models\User;


class EmailService
{
    /**
     * Issue a new random, unpredictable authentication token for a user.
     */
    public static function create(User $user): string
    {
        $token = bin2hex(random_bytes(32));

        $expires = now()->plus(minutes: 60)->timestamp;
   
         EmailInvitation::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expires,
            
        ]);

        return $token;
    }



    public static function createpass(User $user): string
    {
        $token = bin2hex(random_bytes(32));

        $expires = now()->plus(minutes: 5)->timestamp;
   
         PasswordReset::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expires,
            
        ]);

        return $token;
    }

    /**
     * Invalidate (delete) the token currently used for the request.
     */
    
}
