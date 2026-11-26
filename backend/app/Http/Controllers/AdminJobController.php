<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModerateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\JobPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminJobController extends Controller
{
    public function index()
    {
        $jobs = JobPost::withTrashed()
            ->with(['company', 'category'])
            ->latest()
            ->paginate(25);

        return JobResource::collection($jobs);
    }

    public function show(int $job)
    {
        return new JobResource(
            JobPost::withTrashed()->with(['company', 'category'])->findOrFail($job),
        );
    }

    public function updateStatus(ModerateJobRequest $request, int $job)
    {
        $record = JobPost::withTrashed()->findOrFail($job);

        if ($record->status === 'removed') {
            throw ValidationException::withMessages(['status' => 'Removed jobs cannot be restored.']);
        }

        $status = $request->validated('status');
        DB::transaction(function () use ($record, $status): void {
            $record->status = $status;

            if ($status === 'approved' && ! $record->approved_at) {
                $record->approved_at = now();
            }

            if ($status === 'removed') {
                $record->delete();
            } else {
                $record->save();
            }
        });

        return new JobResource($record->load(['company', 'category']));
    }
}
