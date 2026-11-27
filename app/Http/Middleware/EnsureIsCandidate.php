<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnsureIsCandidate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {   
        $user = $request->user();
        if ($user && $user->role ==='candidate') {
            return $next($request);
        }
        else
        {   
            return Response::error('You can not perform this action.', 403); 
        }
        
    }
}
