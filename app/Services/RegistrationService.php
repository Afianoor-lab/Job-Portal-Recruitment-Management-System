<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\User;


class RegistrationService
{
    public function __construct( CandidateService $candidateService,CompanyService $companyService
      
    ) {}

    /**
     * Register a new user and provision their default company, team,
     * and channel, all inside a single database transaction, then
     * issue an authentication token.
     *
     * 
     */
    public function register(array $data, string $token = ''): array
    {
        $user = $this->createUser($data);

        if( $data['role'] =='candidate'){
        $data = CandidateProfile::create( [
                'user_id'        => $user->id,
                'name'        =>  $data['name']
            ]);
        }
        if( $data['role'] =='employer'){
        $data = Company::create( [
                'user_id'        => $user->id,
                'name'        =>  $data['name'],
            ]);
        }


        return ['user' => $user];

    }

    private function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => null,
            'password' => $data['password'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'role' => $data['role'],
            'created_at' => now(),
        ]);


    }

}

    
