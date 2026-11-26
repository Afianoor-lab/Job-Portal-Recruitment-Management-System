<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicJobSearchRequest;
use App\Http\Resources\JobResource;
use App\Services\JobService;
use Illuminate\Database\Eloquent\Builder;

class JobController extends Controller
{
    public function index(PublicJobSearchRequest $request, JobService $jobs)
    {
        $filters = $request->validated();
        $query = $jobs->publicQuery()
            ->when($filters['title'] ?? null, fn (Builder $q, string $value) => $q->where('title', 'like', '%'.$value.'%'))
            ->when($filters['keyword'] ?? null, function (Builder $q, string $value): void {
                $q->where(function (Builder $search) use ($value): void {
                    $search->where('title', 'like', '%'.$value.'%')
                        ->orWhere('description', 'like', '%'.$value.'%')
                        ->orWhere('requirements', 'like', '%'.$value.'%');
                });
            })
            ->when($filters['location'] ?? null, fn (Builder $q, string $value) => $q->where('location', 'like', '%'.$value.'%'))
            ->when($filters['category_id'] ?? null, fn (Builder $q, int $value) => $q->where('category_id', $value))
            ->when($filters['job_type'] ?? null, fn (Builder $q, string $value) => $q->where('job_type', $value))
            ->when(isset($filters['salary_min']) || isset($filters['salary_max']), function (Builder $q) use ($filters): void {
                $minimum = $filters['salary_min'] ?? null;
                $maximum = $filters['salary_max'] ?? null;

                if ($maximum !== null) {
                    $q->where(fn (Builder $overlap) => $overlap->whereNull('salary_min')->orWhere('salary_min', '<=', $maximum));
                }

                if ($minimum !== null) {
                    $q->where(fn (Builder $overlap) => $overlap->whereNull('salary_max')->orWhere('salary_max', '>=', $minimum));
                }
            })
            ->when($filters['posted_after'] ?? null, fn (Builder $q, string $date) => $q->whereDate('approved_at', '>=', $date))
            ->when($filters['posted_before'] ?? null, fn (Builder $q, string $date) => $q->whereDate('approved_at', '<=', $date));

        if (($filters['sort'] ?? 'newest') === 'salary') {
            $query->orderByRaw('salary_max IS NULL ASC')->orderByDesc('salary_max');
        } else {
            $query->orderByDesc('approved_at');
        }

        return JobResource::collection($query->paginate($filters['per_page'] ?? 15));
    }

    public function show(int $job, JobService $jobs)
    {
        $jobPost = $jobs->publicQuery()->findOrFail($job);

        return new JobResource($jobPost);
    }
}
