<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('job_categories')->restrictOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->longText('requirements')->nullable();
            $table->string('job_type');
            $table->string('location');
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'approved_at']);
            $table->index(['category_id', 'job_type']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('candidate_cv_id')->constrained('candidate_cvs')->restrictOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('status')->default('applied');
            $table->timestamps();
            $table->unique(['job_post_id', 'candidate_id']);
            $table->index(['candidate_id', 'status']);
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['job_application_id', 'created_at']);
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['candidate_id', 'job_post_id']);
        });

        Schema::create('job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('job_reports');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posts');
    }
};
