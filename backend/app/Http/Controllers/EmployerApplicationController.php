<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeApplicationStatusRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Services\ApplicationStatusService;
use Illuminate\Support\Facades\Storage;

class EmployerApplicationController extends Controller
{
    public function index(JobPost $jobPost)
    {
        $this->authorize('viewApplicants', $jobPost);

        $applications = $jobPost->applications()
            ->with(['candidate.role', 'candidate.candidateProfile', 'cv', 'histories.actor'])
            ->latest()
            ->paginate(25);

        return ApplicationResource::collection($applications);
    }

    public function show(JobApplication $application)
    {
        $this->authorize('manageAsEmployer', $application);

        return new ApplicationResource($application->load([
            'jobPost.company',
            'jobPost.category',
            'candidate.role',
            'candidate.candidateProfile',
            'cv',
            'histories.actor.role',
        ]));
    }

    public function downloadCv(JobApplication $application)
    {
        $this->authorize('manageAsEmployer', $application);
        $cv = $application->cv;

        abort_unless(Storage::disk('local')->exists($cv->file_path), 404, 'CV file not found.');

        return Storage::disk('local')->download($cv->file_path, $cv->original_name);
    }

    public function changeStatus(
        ChangeApplicationStatusRequest $request,
        JobApplication $application,
        ApplicationStatusService $statuses,
    ) {
        $this->authorize('manageAsEmployer', $application);
        $application = $statuses->change($application, $request->user(), $request->validated('status'));

        return new ApplicationResource($application);
    }
}
