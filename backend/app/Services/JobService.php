<?php

namespace App\Services;

use App\Models\JobCategory;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class JobService
{
    public function create(User $employer, array $data): JobPost
    {
        $company = $employer->company;

        if (! $company || $company->status !== 'active') {
            throw ValidationException::withMessages([
                'company' => 'An active company profile is required before posting a job.',
            ]);
        }

        $this->ensureActiveCategory((int) $data['category_id']);

        return $company->jobs()->create($data);
    }

    public function update(JobPost $job, array $data): JobPost
    {
        if ($job->status === 'removed') {
            throw ValidationException::withMessages([
                'job' => 'A removed job cannot be restored or edited.',
            ]);
        }

        $minimum = array_key_exists('salary_min', $data) ? $data['salary_min'] : $job->salary_min;
        $maximum = array_key_exists('salary_max', $data) ? $data['salary_max'] : $job->salary_max;

        if ($minimum !== null && $maximum !== null && $maximum < $minimum) {
            throw ValidationException::withMessages([
                'salary_max' => 'The maximum salary must be at least the minimum salary.',
            ]);
        }

        if (array_key_exists('category_id', $data)) {
            $this->ensureActiveCategory((int) $data['category_id']);
        }

        $wasApprovedOrRejected = in_array($job->status, ['approved', 'rejected'], true);
        $job->fill($data);

        if ($wasApprovedOrRejected && $job->isDirty()) {
            $job->status = 'pending';
        }

        $job->save();

        return $job->refresh();
    }

    public function publicQuery(): Builder
    {
        return JobPost::query()
            ->publiclyAvailable()
            ->with(['company:id,name,slug,logo,location', 'category:id,name,slug']);
    }

    private function ensureActiveCategory(int $categoryId): void
    {
        if (! JobCategory::query()->whereKey($categoryId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'category_id' => 'Select an active job category.',
            ]);
        }
    }
}
