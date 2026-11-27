<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewJobReportRequest;
use App\Models\JobPost;
use App\Models\JobReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminJobReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'dismissed', 'removed', 'all'])],
        ]);
        $status = $request->query('status', 'pending');

        $reports = JobReport::query()
            ->with(['job.company', 'reporter', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25);

        return response()->json($reports);
    }

    public function update(ReviewJobReportRequest $request, JobReport $report)
    {
        $status = $request->validated('status');

        DB::transaction(function () use ($request, $report, $status): void {
            $report->forceFill([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            if ($status === 'removed') {
                $job = JobPost::query()->find($report->job_post_id);

                if ($job) {
                    $job->forceFill(['status' => 'removed'])->save();
                    $job->delete();
                }
            }
        });

        return response()->json([
            'message' => 'Report reviewed.',
            'data' => $report->fresh(['job', 'reporter', 'reviewer']),
        ]);
    }
}
