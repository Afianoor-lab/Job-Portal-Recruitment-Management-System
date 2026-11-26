<?php

namespace App\Services;

use App\Models\ApplicationStatusHistory;
use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Facades\DB;

class ApplicationStatusService
{
    public function change(JobApplication $application, User $employer, string $newStatus): JobApplication
    {
        $changed = DB::transaction(function () use ($application, $employer, $newStatus): bool {
            $locked = JobApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($locked->status === $newStatus) {
                return false;
            }

            $previousStatus = $locked->status;
            $locked->status = $newStatus;
            $locked->save();

            ApplicationStatusHistory::query()->create([
                'job_application_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => $employer->id,
                'created_at' => now(),
            ]);

            DB::afterCommit(function () use ($locked, $previousStatus, $newStatus): void {
                $locked->candidate->notify(new ApplicationStatusChanged(
                    $locked->load('jobPost'),
                    $previousStatus,
                    $newStatus,
                ));
            });

            return true;
        });

        return $application->refresh()->load(['jobPost.company', 'jobPost.category', 'histories.actor']);
    }
}
