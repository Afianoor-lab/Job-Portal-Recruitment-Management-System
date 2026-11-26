<?php

namespace App\Providers;

use App\Models\JobApplication;
use App\Models\JobPost;
use App\Policies\ApplicationPolicy;
use App\Policies\JobPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(JobPost::class, JobPolicy::class);
        Gate::policy(JobApplication::class, ApplicationPolicy::class);
    }
}
