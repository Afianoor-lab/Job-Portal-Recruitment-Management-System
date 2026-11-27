<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveProfileRequest;
use App\Http\Requests\UploadCVRequest;
use App\Http\Resources\CandidateProfileResource;
use App\Http\Resources\UploadCVResource;
use App\Models\CandidateCv;
use App\Services\CandidateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CandidateProfileController extends Controller
{
    public function __construct(private readonly CandidateService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return  Response::success('Candidate Profile Fetched successfully. ', [
            'candidate' => new CandidateProfileResource($this->profiles->get($request->user())),]);
    }

    // Returns 201 when the profile is created and 200 when it is updated.
    public function save(SaveProfileRequest $request): JsonResponse
    {   return  Response::success('Candidate Profile Added. ', [
            'candidate' => new CandidateProfileResource(
                $this->profiles->save($request->user(), $request->validated())
            ),], 200);
    }

    public function delete(Request $request): JsonResponse
    {   
        $this->profiles->destroy($request->user());
        return  Response::success('Candidate Profile Deleted. ');
    }

    public function upload(UploadCVRequest $request): JsonResponse
    {   
        $cv=$this->profiles->upload($request->user(),$request->file('attachment'));
        return  Response::success('Candidate CV Uploaded . ',
        ['cv'=> new UploadCVResource($cv)],201);
    }

    // public function showcv(Request $request)
    // {
    //     $user = $request->user()->candidateCV;
      

    //     if (!$user->file_path ) {
    //     return response()->json([
    //         'status' => false,
    //         'message' => 'No Profile image found.',
    //     ], 404);
    // }
    //     abort_unless(Storage::disk('public')->exists($user->file_path), 404);
    //     /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    //     $disk = Storage::disk('public');
    //     return  $disk->download($user->file_path);     
    // }





    public function showcv(Request $request,CandidateCv $id)
    {
    //$cv = $request->user()->candidateCVs->where('is_current', true)->first();
    $cv=$id;

    if (!$cv || !$cv->file_path) {
        return response()->json(['status' => false, 'message' => 'CV not found.'], 404);
    }

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    if (!$disk->exists($cv->file_path)) {
        return response()->json(['status' => false, 'message' => 'CV file is missing.'], 404);
    }

    return $disk->download($cv->file_path, $cv->original_name ?: basename($cv->file_path));

    }

     public function cvlist(Request $request)
    {
        $page = max(1, (int) request('page', 1));
        $perPage = max(1, min(100, (int) request('per_page', 19)));
        $cvs = $request->user()
            ->candidateCVs()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return Response::success('Candidate CVS retrieved.', [
            'items' => UploadCVResource::collection($cvs->items()) ,  
            'pagination'=> [
            'current_page' => $cvs->currentPage(),
            'last_page' => $cvs->lastPage(),
            'per_page' => $cvs->perPage(),
            'total' => $cvs->total(),
        ],
        ]);

    }

    public function setCurrentCv(Request $request, CandidateCv $id)
    {
        $cv=$id;  

        if($cv->candidate_id !== $request->user()->id ){
            return  Response::error('This CV does not belongs to you.',403);
        }
        $this->profiles->setCurrent($request->user(), $cv);
        return  Response::success('Current CV selected. ', [
                'data' =>$cv->only(['id', 'file_path', 'is_current'])]);
        }

        public function remove(Request $request, CandidateCv $id)
        {
            $cv = $id;
            if($cv->candidate_id !== $request->user()->id ){
                return  Response::error('This CV does not belongs to you.',403);
            }
            if ($cv->file_path && Storage::disk('public')->exists($cv->file_path)) {
                Storage::disk('public')->delete($cv->file_path);
            }
            $cv->delete();
            return  Response::error('CV Deleted',200);
        }


    
}
