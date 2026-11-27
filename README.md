# Job-Portal-Recruitment-Management-System

# Job Portal & Recruitment Management System

The Laravel REST API lives in [`backend/`](./backend). The repository was initially empty; this branch adds the backend foundation and Module 2 (Jobs and Recruitment). Frontend pages and Module 1 authentication/profile endpoints are not included.

## Requirements

- PHP 8.3 or newer with `pdo_mysql`, `mbstring`, `openssl`, and `fileinfo`
- Composer
- MySQL 8+

## Setup

1. Create a MySQL database named `job_portal`.
2. In `backend`, copy `.env.example` to `.env` and set your database and mail credentials.
3. Run:

   ```powershell
   cd backend
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve
   ```

4. In a second terminal, start the queue worker so application status emails and in-app notifications are delivered:

   ```powershell
   cd backend
   php artisan queue:work
   ```

The API base URL is `http://127.0.0.1:8000/api`. The test suite uses an in-memory SQLite database:

```powershell
cd backend
php artisan test
```

## Authentication integration (manual bearer tokens)

Sanctum is not installed or used. `App\Models\ApiToken::issue($user, 'web')` creates a random token (30-day expiry by default) and stores only its SHA-256 hash. The Module 1 login endpoint should return the plain token once; send it on protected requests as `Authorization: Bearer <token>`. The authenticated token model is available as `$request->attributes->get('api_token')`; logout should delete that token.

The `roles`, `companies`, `job_categories`, `candidate_profiles`, and `candidate_cvs` tables here provide the Module 1 data contract required by Module 2. Module 1 controllers and auth pages/API endpoints are still to be added by the other contributor; coordinate their migration changes before merging to avoid duplicate schema migrations.

Candidate CVs must be stored on Laravel's private `local` disk (`storage/app/private`), never served through a public URL. The Module 1 CV upload flow should set `candidate_cvs.is_current` and retain prior CV records referenced by applications.

## Module 2 API

Public routes need no token. Protected routes require a valid, unexpired manual bearer token and the role shown below. Validation errors use Laravel's JSON `422` response shape.

| Method | Endpoint | Access | Purpose |
|---|---|---|---|
| GET | `/jobs` | Public | Search/filter/sort/paginate approved open jobs |
| GET | `/jobs/{id}` | Public | Job details |
| GET, POST | `/employer/jobs` | Employer | List/create own jobs |
| GET, PUT, DELETE | `/employer/jobs/{id}` | Employer | View/update/soft-delete own job |
| PATCH | `/employer/jobs/{id}/close` | Employer | Close own job |
| POST | `/jobs/{id}/apply` | Candidate | Apply with current CV; optional cover letter |
| GET | `/candidate/applications` | Candidate | List own applications and history |
| GET | `/candidate/applications/{id}` | Candidate | View own application and history |
| GET | `/candidate/saved-jobs` | Candidate | List saved jobs |
| POST, DELETE | `/candidate/saved-jobs/{jobId}` | Candidate | Save/remove a job |
| POST | `/jobs/{id}/report` | Candidate | Report a public job |
| GET | `/employer/jobs/{id}/applications` | Employer | Applicant list/Kanban data for own job |
| GET | `/employer/applications/{id}` | Employer | Applicant details and history |
| GET | `/employer/applications/{id}/cv` | Employer | Authorized private CV download |
| PATCH | `/employer/applications/{id}/status` | Employer | Move an application between stages |
| GET | `/candidate/cvs/{id}/download` | Candidate | Download own CV |
| GET | `/notifications` | Candidate | Notifications and unread count |
| PATCH | `/notifications/{id}/read` | Candidate | Mark own notification read |
| GET | `/employer/statistics` | Employer | Job/application statistics |
| GET, PATCH | `/admin/jobs`, `/admin/jobs/{id}/status` | Admin | List and moderate jobs |
| GET, PATCH | `/admin/reports`, `/admin/reports/{id}` | Admin | Review reports (`dismissed` or `removed`); GET accepts `status=all` |
| GET | `/admin/statistics` | Admin | Platform counts |

Job types are `full_time`, `part_time`, `contract`, `temporary`, and `internship`. Job statuses are `pending`, `approved`, `rejected`, `closed`, and `removed`. Application statuses are `applied`, `under_review`, `shortlisted`, `interview`, `offered`, `hired`, and `rejected`.

Public job search accepts `title`, `keyword`, `location`, `category_id`, `job_type`, `salary_min`, `salary_max`, `posted_after`, `posted_before`, `sort` (`newest` or `salary`), and `per_page`. Salary filters match overlapping salary ranges; salary sorting is maximum salary descending. Public posting-date filters use the first `approved_at` timestamp. Agree on a single monthly salary currency before production.

## Database and behavior notes

- `job_posts` uses that table name to avoid collision with Laravel's queue `jobs` table.
- Module 2 tables are `job_posts`, `job_applications`, `application_status_histories`, `saved_jobs`, and `job_reports`; Laravel's `notifications` table stores in-app notices. `api_tokens` stores only hashed manual tokens.
- `job_posts` references `companies` and `job_categories`. Applications reference their job, candidate, and the exact `candidate_cvs` record used. Status history records the actor and old/new status.
- Public listings exclude pending/rejected/closed/removed jobs, expired deadlines, inactive companies/owners, and inactive categories.
- Employer job changes are owner-scoped. Editing an approved/rejected job sends it back to pending; a removed job cannot be restored by an employer.
- Applications require a current CV and have a unique candidate/job pair. The CV record used is pinned to the application.
- Application status and history are written in one transaction. Repeating the current status is a no-op; after commit, a queued email and database notification are sent.
- CV files are downloaded only through ownership-checked endpoints. Admins do not get CV-download access.
- Employer deletion and admin removal soft-delete jobs so application history remains.
