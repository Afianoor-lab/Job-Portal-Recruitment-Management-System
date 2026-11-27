<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\RegistrationResource;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\RegistrationService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;


class AuthController extends Controller
{
    use Notifiable;

    public function __construct(
        private  RegistrationService $registrationService,
        private  TokenService $tokenService,
    ) {
    }

   

    
    //  * * POST /api/register
     

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->registrationService->register($request->validated());


        //$token=InvitationController::sendemail($result['user']);
        
        return  Response::success('Registration successful. ', [
            'user' => new RegistrationResource($result['user']),
            //'account-verify-token'=>$token
            
            ], 201);
    }

    // **POST /api/login
     
    public function login(LoginRequest $request): JsonResponse
    {
        $remember_token=null;
        $token=null;
        $user =User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw new InvalidCredentialsException;
        }
        $user->update(['remember_token'=>null]);

        if ($request->boolean('is_remember')) {
        
        $remember_token = $this->tokenService->issueTokenFor();
        $hash=hash('sha256',$remember_token);
        $user->update(['last_activity'=>now()->timestamp,'remember_token'=>$hash]);
        }
        else{
        $token = $this->tokenService->issueTokenFor();
        $this->tokenService->encryptToken($user,$token);
        }

         return Response::success(
           'Login successful.',[
           'user' => new  RegistrationResource($user),  'token' => $token, 'remember_token' => $remember_token],
            200,
           
        );
    }


    

    /**
     ** POST /api/logout
     
     */

    public function logout(Request $request): JsonResponse
    {
     
        $token = $request->attributes->get('current_token');
        
        if($token && $token!=='null'){
        $this->tokenService->revoke($token);
        }else{ $request->user()->update(['remember_token'=>null]);}

        return Response::success('Logged out successfully.');
    }

    


   

 //  * * POST /forgetpassword/{token}

 public function forgetpassword(Request $request,string $token): JsonResponse
    {
        $validatedData = $request->validate([
                 'password' => ['required', 'string', 
                        Password::min(8)
                            ->mixedCase()      // Requires at least one uppercase and one lowercase letter
                            ->numbers()        // Requires at least one number
                            ->symbols()        // Requires at least one special character
                        ,'confirmed'],
                ],[ 'password.min' => 'The password must be at least 8 characters long.',
                    'password.confirmed'=>'he password and confirm password field does not match.',
                ]);

        $passtoken= PasswordReset::where('token', $token)->first();

        $user = User::findOrFail($passtoken->user_id); 

        if (Hash::check($validatedData['password'], $user->password)) {
        
         return Response::error(
           'The new password cannot be the same as your current password.',
            422,
        );
    }
        
        $user->update([ 
            'password' => Hash::make($validatedData['password'])
        ]);

        $passtoken->update(['expires_at' => now() ]);


        return Response::success(
        'Password Reset successful.',[
        'user' => new  RegistrationResource($user)],
        200,
        );
    }


}
