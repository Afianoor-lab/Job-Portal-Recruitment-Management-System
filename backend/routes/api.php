<?php

use App\Http\Controllers\AdminJobController;
use App\Http\Controllers\AdminJobReportController;
use App\Http\Controllers\CandidateApplicationController;
use App\Http\Controllers\CandidateCvController;
use App\Http\Controllers\EmployerApplicationController;
use App\Http\Controllers\EmployerJobController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SavedJobController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{job}', [JobController::class, 'show'])->whereNumber('job');

Route::middleware(['api.token', 'role:candidate'])->group(function (): void {
    Route::post('/jobs/{job}/apply', [CandidateApplicationController::class, 'apply'])->whereNumber('job');
    Route::post('/jobs/{job}/report', [JobReportController::class, 'store'])->whereNumber('job');
    Route::get('/candidate/applications', [CandidateApplicationController::class, 'index']);
    Route::get('/candidate/applications/{application}', [CandidateApplicationController::class, 'show'])->whereNumber('application');
    Route::get('/candidate/saved-jobs', [SavedJobController::class, 'index']);
    Route::post('/candidate/saved-jobs/{job}', [SavedJobController::class, 'store'])->whereNumber('job');
    Route::delete('/candidate/saved-jobs/{job}', [SavedJobController::class, 'destroy'])->whereNumber('job');
    Route::get('/candidate/cvs/{cv}/download', [CandidateCvController::class, 'download'])->whereNumber('cv');
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});

Route::middleware(['api.token', 'role:employer'])->group(function (): void {
    Route::get('/employer/jobs', [EmployerJobController::class, 'index']);
    Route::post('/employer/jobs', [EmployerJobController::class, 'store']);
    Route::get('/employer/jobs/{job}', [EmployerJobController::class, 'show'])->whereNumber('job');
    Route::put('/employer/jobs/{job}', [EmployerJobController::class, 'update'])->whereNumber('job');
    Route::delete('/employer/jobs/{job}', [EmployerJobController::class, 'destroy'])->whereNumber('job');
    Route::patch('/employer/jobs/{job}/close', [EmployerJobController::class, 'close'])->whereNumber('job');
    Route::get('/employer/jobs/{job}/applications', [EmployerApplicationController::class, 'index'])->whereNumber('job');
    Route::get('/employer/applications/{application}', [EmployerApplicationController::class, 'show'])->whereNumber('application');
    Route::get('/employer/applications/{application}/cv', [EmployerApplicationController::class, 'downloadCv'])->whereNumber('application');
    Route::patch('/employer/applications/{application}/status', [EmployerApplicationController::class, 'changeStatus'])->whereNumber('application');
    Route::get('/employer/statistics', [StatisticsController::class, 'employer']);
});

Route::middleware(['api.token', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/jobs', [AdminJobController::class, 'index']);
    Route::get('/jobs/{job}', [AdminJobController::class, 'show'])->whereNumber('job');
    Route::patch('/jobs/{job}/status', [AdminJobController::class, 'updateStatus'])->whereNumber('job');
    Route::get('/reports', [AdminJobReportController::class, 'index']);
    Route::patch('/reports/{report}', [AdminJobReportController::class, 'update'])->whereNumber('report');
    Route::get('/statistics', [StatisticsController::class, 'admin']);
});
