<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Custom, hand-rolled token authentication.
 *
 * 1. Reads AuthorizationKey or Authorization: Bearer header.
 * 2. Extracts plain text token.
 * 3. Looks up token hash in user_tokens table.
 * 4. Rejects with 401 if missing, unknown, or expired.
 * 5. Binds authenticated user to request and Auth manager.
 */
class AuthToken
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $plainTextToken = $request->header('AuthorizationKey') ?? $request->bearerToken();
        $plainrememberToken = $request->header('X-Remember-Token') ;
        

        $sevenDaysInSeconds = 7 * 24 * 60 * 60;

        

        if (empty($plainTextToken) && empty($plainrememberToken)  ) {
            return Response::error('Missing authentication token.', 401);
        }

        $token_hash = hash('sha256', $plainTextToken);
        $remember_token_hash = hash('sha256', $plainrememberToken);

        /** @var UserToken|null $token */
        $token = UserToken::with('user')->where('token_hash', $token_hash)->first();
        $remember_token = User::where('remember_token', $remember_token_hash)->first();
         

        // Check if last_activity exists and the difference is greater than 7 days
        if($remember_token){
           
            if ($remember_token->last_activity && (now()->timestamp - $remember_token->last_activity) > $sevenDaysInSeconds) {
                $remember_token->update([
                    'remember_token' => null, // Clear the remember token
                ]);
                return Response::error('Authentication token has expired.', 401);
            }
        }

        if (! $token && !$remember_token) {
            return Response::error('Invalid authentication token.', 401);
        }

        if ($token && $token->isExpired() ) {
            return Response::error('Authentication token has expired.', 401);
        }

        Auth::setUser($token->user??$remember_token);
        $request->setUserResolver(fn() => $token->user??$remember_token);
        $request->attributes->set('current_token', $plainTextToken);
       

        return $next($request);
    }
}
