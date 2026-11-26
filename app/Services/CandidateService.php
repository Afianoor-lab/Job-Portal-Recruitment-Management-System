<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\LibraryItem;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Http\JsonResponse;

class CandidateService
{
    public function create(User $user, array $data): CandidateProfile
    {
        
            $data = CandidateProfile::create( [
                'user_id'        => $user->id,
             
            ]);


        return $data;
    }

   
}
