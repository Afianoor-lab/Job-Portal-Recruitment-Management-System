<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAsCandidate(User $user, JobApplication $application): bool
    {
        return $user->role?->slug === 'candidate'
            && $application->candidate_id === $user->id;
    }

    public function manageAsEmployer(User $user, JobApplication $application): bool
    {
        return $user->role?->slug === 'employer'
            && $user->company?->id === $application->jobPost?->company_id;
    }
}
