<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Models\JobPost;
use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SavedJobController extends Controller
{
    public function index(Request $request)
    {
        $saved = SavedJob::query()
            ->where('candidate_id', $request->user()->id)
            ->whereHas('job', fn ($query) => $query->publiclyAvailable())
            ->with('job.company', 'job.category')
            ->latest()
            ->paginate(15);

        return JobResource::collection($saved->through(fn (SavedJob $record) => $record->job));
    }

    public function store(Request $request, int $job)
    {
        $jobPost = JobPost::query()->publiclyAvailable()->findOrFail($job);
        $exists = SavedJob::query()
            ->where('candidate_id', $request->user()->id)
            ->where('job_post_id', $jobPost->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['job' => 'This job is already saved.']);
        }

        SavedJob::query()->create([
            'candidate_id' => $request->user()->id,
            'job_post_id' => $jobPost->id,
        ]);

        return response()->json(['message' => 'Job saved.'], 201);
    }

    public function destroy(Request $request, int $job)
    {
        $deleted = SavedJob::query()
            ->where('candidate_id', $request->user()->id)
            ->where('job_post_id', $job)
            ->delete();

        abort_if($deleted === 0, 404);

        return response()->json(['message' => 'Saved job removed.']);
    }
}
