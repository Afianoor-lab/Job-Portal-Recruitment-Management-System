<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\JobPost;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployerJobController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return JobResource::collection(JobPost::query()->whereRaw('1 = 0')->paginate(15));
        }

        return JobResource::collection(
            $company->jobs()->with(['company', 'category'])->latest()->paginate(15),
        );
    }

    public function store(StoreJobRequest $request, JobService $jobs)
    {
        $job = $jobs->create($request->user(), $request->validated())
            ->load(['company', 'category']);

        return (new JobResource($job))->response()->setStatusCode(201);
    }

    public function show(JobPost $job)
    {
        $this->authorize('manage', $job);

        return new JobResource($job->load(['company', 'category']));
    }

    public function update(UpdateJobRequest $request, JobPost $job, JobService $jobs)
    {
        $this->authorize('manage', $job);
        $job = $jobs->update($job, $request->validated())->load(['company', 'category']);

        return new JobResource($job);
    }

    public function destroy(JobPost $job)
    {
        $this->authorize('manage', $job);
        $job->delete();

        return response()->json(['message' => 'Job deleted.']);
    }

    public function close(JobPost $job)
    {
        $this->authorize('manage', $job);

        if ($job->status === 'removed') {
            throw ValidationException::withMessages(['job' => 'A removed job cannot be closed or restored.']);
        }

        if ($job->status !== 'closed') {
            $job->forceFill(['status' => 'closed', 'closed_at' => now()])->save();
        }

        return new JobResource($job->load(['company', 'category']));
    }
}
