<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportJobRequest;
use App\Models\JobPost;
use App\Models\JobReport;

class JobReportController extends Controller
{
    public function store(ReportJobRequest $request, int $job)
    {
        $jobPost = JobPost::query()->publiclyAvailable()->findOrFail($job);

        $report = JobReport::query()->create([
            'job_post_id' => $jobPost->id,
            'reported_by' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Job report submitted.',
            'data' => ['id' => $report->id, 'status' => $report->status],
        ], 201);
    }
}
