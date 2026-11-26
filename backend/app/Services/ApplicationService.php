<?php

namespace App\Services;

use App\Models\CandidateCv;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function apply(User $candidate, JobPost $job, ?string $coverLetter): JobApplication
    {
        $cv = CandidateCv::query()
            ->where('candidate_id', $candidate->id)
            ->where('is_current', true)
            ->latest('uploaded_at')
            ->first();

        if (! $cv) {
            throw ValidationException::withMessages([
                'cv' => 'Upload a current CV before applying.',
            ]);
        }

        return DB::transaction(function () use ($candidate, $job, $cv, $coverLetter): JobApplication {
            $lockedJob = JobPost::query()
                ->with(['company.owner'])
                ->lockForUpdate()
                ->findOrFail($job->id);

            if (! $lockedJob->isOpenForApplications()) {
                throw ValidationException::withMessages([
                    'job' => 'This job is no longer accepting applications.',
                ]);
            }

            if (JobApplication::query()
                ->where('job_post_id', $lockedJob->id)
                ->where('candidate_id', $candidate->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'job' => 'You have already applied for this job.',
                ]);
            }

            $application = JobApplication::query()->create([
                'job_post_id' => $lockedJob->id,
                'candidate_id' => $candidate->id,
                'candidate_cv_id' => $cv->id,
                'cover_letter' => $coverLetter,
                'status' => 'applied',
            ]);

            $application->histories()->create([
                'previous_status' => null,
                'new_status' => 'applied',
                'changed_by' => $candidate->id,
                'created_at' => now(),
            ]);

            return $application->load(['jobPost.company', 'jobPost.category', 'cv', 'histories.actor']);
        });
    }
}
