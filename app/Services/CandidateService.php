<?php

namespace App\Services;

use App\Models\CandidateCv;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Http\JsonResponse;

class CandidateService
{
    // public function create(User $user, array $data): CandidateProfile
    // {
        
    //         $data = CandidateProfile::create( [
    //             'user_id'        => $user->id,
             
    //         ]);


    //     return $data;
    // }

    public function get(User $user): CandidateProfile
    {
        return $user->candidateProfile()->firstOrFail();
    }

    /** Creates the profile, or updates it if the user already has one. */
    public function save(User $user, array $data): CandidateProfile
    {
        return $user->candidateProfile()->updateOrCreate([], $data);
    }

    public function destroy(User $user): void
    {
        $user->candidateProfile()->delete();
    }

    public function upload(User $user, UploadedFile $file): CandidateCv|JsonResponse
    {   
        $extension = strtolower($file->guessExtension() ?: $file->extension());
        $name = $user->id.'_'.Str::uuid()->toString() . ($extension ? '.' . $extension : '');

        $path = Storage::disk('public')->putFileAs(
            'CandidateCV',
            $file,
            $name
        );

        if ($path === false) {
            return ResponseFacade::error('The file could not be stored.', 500);
        }

        // if ($user->candidateCV->file_path && Storage::disk('public')->exists($user->candidateCV->file_path)) {
        //     Storage::disk('public')->delete($user->candidateCV->file_path);
        // }

        $cv = CandidateCv::create([
            "candidate_id" => $user->id,
            'file_path' => 'CandidateCV/'.$name,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_at' => now(),
        ]);

        return $cv;
    }

    public function setCurrent(User $user, CandidateCV $cv): void
    {
        $user->candidateCVs()->whereKeyNot($cv->id)->update(['is_current' => false]);
        $cv->update(['is_current' => true]);
    }
}
