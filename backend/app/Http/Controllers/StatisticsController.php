<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\JobReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function employer(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'total_jobs' => 0,
                'open_jobs' => 0,
                'closed_jobs' => 0,
                'total_applications' => 0,
                'applications_per_job' => [],
                'applications_per_status' => [],
            ]);
        }

        $jobs = $company->jobs()->withTrashed();
        $jobIds = (clone $jobs)->pluck('id');

        return response()->json([
            'total_jobs' => (clone $jobs)->count(),
            'open_jobs' => $company->status === 'active'
                ? (clone $jobs)->whereNull('deleted_at')->where('status', 'approved')->where(fn ($query) => $query->whereNull('deadline')->orWhereDate('deadline', '>=', today()))->count()
                : 0,
            'closed_jobs' => (clone $jobs)->where('status', 'closed')->count(),
            'total_applications' => JobApplication::query()->whereIn('job_post_id', $jobIds)->count(),
            'applications_per_job' => JobApplication::query()
                ->whereIn('job_post_id', $jobIds)
                ->select('job_post_id', DB::raw('COUNT(*) as total'))
                ->groupBy('job_post_id')
                ->with(['jobPost' => fn ($query) => $query->withTrashed()->select('id', 'title')])
                ->get()
                ->map(fn (JobApplication $application) => [
                    'job_id' => $application->job_post_id,
                    'title' => $application->jobPost?->title,
                    'applications' => $application->total,
                ]),
            'applications_per_status' => JobApplication::query()
                ->whereIn('job_post_id', $jobIds)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function admin()
    {
        return response()->json([
            'users' => User::withTrashed()->count(),
            'companies' => Company::query()->count(),
            'jobs' => JobPost::withTrashed()->count(),
            'applications' => JobApplication::query()->count(),
            'pending_reports' => JobReport::query()->where('status', 'pending')->count(),
        ]);
    }
}
