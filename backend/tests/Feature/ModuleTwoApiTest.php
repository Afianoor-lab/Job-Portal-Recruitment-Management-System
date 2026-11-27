<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CandidateCv;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\JobPost;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModuleTwoApiTest extends TestCase
{
    use DatabaseMigrations;

    private array $roles;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Employer', 'slug' => 'employer'],
            ['name' => 'Candidate', 'slug' => 'candidate'],
        ] as $role) {
            $this->roles[$role['slug']] = Role::query()->create($role);
        }
    }

    public function test_public_search_only_returns_eligible_jobs_and_matches_overlapping_salary_ranges(): void
    {
        [$employer, $company] = $this->employer('active');
        $category = $this->category();

        $overlap = $this->job($company, $category, [
            'title' => 'Backend Engineer',
            'salary_min' => 50000,
            'salary_max' => 80000,
            'approved_at' => now()->subDay(),
        ]);
        $higher = $this->job($company, $category, [
            'title' => 'Senior Backend Engineer',
            'salary_min' => 85000,
            'salary_max' => 110000,
            'approved_at' => now(),
        ]);
        $this->job($company, $category, [
            'status' => 'pending',
            'title' => 'Unapproved',
        ]);
        $this->job($company, $category, [
            'status' => 'approved',
            'deadline' => today()->subDay(),
            'title' => 'Expired',
        ]);

        $response = $this->getJson('/api/jobs?keyword=backend&salary_min=70000&salary_max=90000&sort=salary');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $higher->id)
            ->assertJsonPath('data.1.id', $overlap->id)
            ->assertJsonCount(2, 'data');
    }

    public function test_employer_cannot_manage_another_employers_job_and_roles_are_enforced(): void
    {
        $this->getJson('/api/candidate/applications')->assertUnauthorized();

        [, $ownerCompany] = $this->employer('active');
        $job = $this->job($ownerCompany, $this->category());
        [$otherEmployer] = $this->employer('active');

        $this->withToken($this->token($otherEmployer))
            ->getJson('/api/employer/jobs/'.$job->id)
            ->assertForbidden();

        $candidate = $this->user('candidate');
        $this->withToken($this->token($candidate))
            ->getJson('/api/employer/jobs')
            ->assertForbidden();
    }

    public function test_candidate_application_uses_current_cv_and_rejects_duplicates(): void
    {
        [$employer, $company] = $this->employer('active');
        $job = $this->job($company, $this->category());
        $candidate = $this->user('candidate');
        $cv = CandidateCv::query()->create([
            'candidate_id' => $candidate->id,
            'file_path' => 'cvs/current.pdf',
            'original_name' => 'resume.pdf',
            'size' => 2048,
            'is_current' => true,
            'uploaded_at' => now(),
        ]);
        $token = $this->token($candidate);

        $this->withToken($token)
            ->postJson('/api/jobs/'.$job->id.'/apply', ['cover_letter' => 'I am interested.'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'applied')
            ->assertJsonPath('data.cv.id', $cv->id);

        $application = JobApplication::query()->firstOrFail();
        $this->assertSame('applied', $application->status);
        $this->assertDatabaseHas('application_status_histories', [
            'job_application_id' => $application->id,
            'previous_status' => null,
            'new_status' => 'applied',
            'changed_by' => $candidate->id,
        ]);

        $this->withToken($token)
            ->postJson('/api/jobs/'.$job->id.'/apply')
            ->assertUnprocessable();
    }

    public function test_candidate_must_have_a_current_cv_to_apply(): void
    {
        [, $company] = $this->employer('active');
        $job = $this->job($company, $this->category());
        $candidate = $this->user('candidate');

        $this->withToken($this->token($candidate))
            ->postJson('/api/jobs/'.$job->id.'/apply')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cv');
    }

    public function test_employer_content_edits_send_approved_jobs_back_to_moderation(): void
    {
        [$employer, $company] = $this->employer('active');
        $job = $this->job($company, $this->category());
        $token = $this->token($employer);

        $this->withToken($token)
            ->putJson('/api/employer/jobs/'.$job->id, ['salary_max' => 40000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('salary_max');

        $this->withToken($token)
            ->putJson('/api/employer/jobs/'.$job->id, ['title' => 'Updated Engineer'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_candidate_cv_download_is_private_and_owner_scoped(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cvs/private.pdf', 'private cv contents');
        $candidate = $this->user('candidate');
        $cv = CandidateCv::query()->create([
            'candidate_id' => $candidate->id,
            'file_path' => 'cvs/private.pdf',
            'original_name' => 'resume.pdf',
            'size' => 19,
            'is_current' => true,
            'uploaded_at' => now(),
        ]);

        $this->withToken($this->token($candidate))
            ->get('/api/candidate/cvs/'.$cv->id.'/download')
            ->assertOk()
            ->assertDownload('resume.pdf');

        $otherCandidate = $this->user('candidate');
        $this->withToken($this->token($otherCandidate))
            ->getJson('/api/candidate/cvs/'.$cv->id.'/download')
            ->assertNotFound();
    }

    public function test_employer_cv_download_is_limited_to_the_job_owner(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('cvs/applicant.pdf', 'private applicant cv');
        [$owner, $company] = $this->employer('active');
        $job = $this->job($company, $this->category());
        $candidate = $this->user('candidate');
        $cv = CandidateCv::query()->create([
            'candidate_id' => $candidate->id,
            'file_path' => 'cvs/applicant.pdf',
            'original_name' => 'applicant.pdf',
            'size' => 20,
            'is_current' => true,
            'uploaded_at' => now(),
        ]);
        $application = JobApplication::query()->create([
            'job_post_id' => $job->id,
            'candidate_id' => $candidate->id,
            'candidate_cv_id' => $cv->id,
            'status' => 'applied',
        ]);
        [$otherEmployer] = $this->employer('active');

        $this->withToken($this->token($owner))
            ->get('/api/employer/applications/'.$application->id.'/cv')
            ->assertOk()
            ->assertDownload('applicant.pdf');

        $this->withToken($this->token($otherEmployer))
            ->getJson('/api/employer/applications/'.$application->id.'/cv')
            ->assertForbidden();
    }

    public function test_candidate_can_report_a_job_and_admin_can_remove_it(): void
    {
        [, $company] = $this->employer('active');
        $job = $this->job($company, $this->category());
        $candidate = $this->user('candidate');

        $response = $this->withToken($this->token($candidate))
            ->postJson('/api/jobs/'.$job->id.'/report', ['reason' => 'The listing appears fraudulent.'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $reportId = $response->json('data.id');
        $admin = $this->user('admin');

        $this->withToken($this->token($admin))
            ->patchJson('/api/admin/reports/'.$reportId, ['status' => 'removed'])
            ->assertOk();

        $this->assertDatabaseHas('job_reports', [
            'id' => $reportId,
            'status' => 'removed',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertSoftDeleted('job_posts', ['id' => $job->id]);
    }

    public function test_employer_needs_an_active_company_to_post_a_job(): void
    {
        $employer = $this->user('employer');
        $category = $this->category();

        $this->withToken($this->token($employer))
            ->postJson('/api/employer/jobs', [
                'title' => 'Software Engineer',
                'description' => 'Build software.',
                'category_id' => $category->id,
                'job_type' => 'full_time',
                'location' => 'Remote',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('company');
    }

    public function test_status_changes_record_history_and_same_status_is_a_noop(): void
    {
        Notification::fake();
        [$employer, $company] = $this->employer('active');
        $job = $this->job($company, $this->category());
        $candidate = $this->user('candidate');
        $cv = CandidateCv::query()->create([
            'candidate_id' => $candidate->id,
            'file_path' => 'cvs/current.pdf',
            'original_name' => 'resume.pdf',
            'size' => 1024,
            'is_current' => true,
            'uploaded_at' => now(),
        ]);
        $application = JobApplication::query()->create([
            'job_post_id' => $job->id,
            'candidate_id' => $candidate->id,
            'candidate_cv_id' => $cv->id,
            'status' => 'applied',
        ]);
        $application->histories()->create([
            'previous_status' => null,
            'new_status' => 'applied',
            'changed_by' => $candidate->id,
            'created_at' => now(),
        ]);
        $token = $this->token($employer);

        $this->withToken($token)
            ->patchJson('/api/employer/applications/'.$application->id.'/status', ['status' => 'shortlisted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'shortlisted');

        $this->withToken($token)
            ->patchJson('/api/employer/applications/'.$application->id.'/status', ['status' => 'shortlisted'])
            ->assertOk();

        $this->assertDatabaseCount('application_status_histories', 2);
        Notification::assertSentTo($candidate, ApplicationStatusChanged::class);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => $this->roles[$role]->id]);
    }

    private function employer(string $status): array
    {
        $user = $this->user('employer');
        $company = Company::query()->create([
            'user_id' => $user->id,
            'name' => 'Example Company '.$user->id,
            'slug' => 'example-company-'.$user->id,
            'status' => $status,
        ]);

        return [$user, $company];
    }

    private function category(): JobCategory
    {
        return JobCategory::query()->create([
            'name' => 'Engineering',
            'slug' => 'engineering-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function job(Company $company, JobCategory $category, array $attributes = []): JobPost
    {
        $job = JobPost::query()->create(array_merge([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Software Engineer',
            'description' => 'Build software.',
            'requirements' => 'PHP',
            'job_type' => 'full_time',
            'location' => 'Remote',
            'salary_min' => 50000,
            'salary_max' => 100000,
            'deadline' => today()->addWeek(),
        ], $attributes));
        $job->forceFill([
            'status' => $attributes['status'] ?? 'approved',
            'approved_at' => $attributes['approved_at'] ?? now(),
        ])->save();

        return $job;
    }

    private function token(User $user): string
    {
        return ApiToken::issue($user);
    }
}
