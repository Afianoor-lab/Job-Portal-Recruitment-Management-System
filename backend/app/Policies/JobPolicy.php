<?php

namespace App\Policies;

use App\Models\JobPost;
use App\Models\User;

class JobPolicy
{
    public function manage(User $user, JobPost $job): bool
    {
        return $user->role?->slug === 'employer'
            && $user->company?->id === $job->company_id;
    }

    public function viewApplicants(User $user, JobPost $job): bool
    {
        return $this->manage($user, $job);
    }
}
