<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyToJobRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Services\ApplicationService;
use Illuminate\Http\Request;

class CandidateApplicationController extends Controller
{
    public function apply(ApplyToJobRequest $request, int $job, ApplicationService $applications)
    {
        $jobPost = JobPost::query()->publiclyAvailable()->findOrFail($job);
        $application = $applications->apply(
            $request->user(),
            $jobPost->load(['company.owner']),
            $request->validated('cover_letter'),
        );

        return (new ApplicationResource($application))->response()->setStatusCode(201);
    }

    public function index(Request $request)
    {
        $applications = JobApplication::query()
            ->where('candidate_id', $request->user()->id)
            ->with(['jobPost.company', 'jobPost.category', 'histories.actor'])
            ->latest()
            ->paginate(15);

        return ApplicationResource::collection($applications);
    }

    public function show(Request $request, int $application)
    {
        $record = JobApplication::query()
            ->where('candidate_id', $request->user()->id)
            ->with(['jobPost.company', 'jobPost.category', 'histories.actor'])
            ->findOrFail($application);

        return new ApplicationResource($record);
    }
}
