<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPost extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'rejected', 'closed', 'removed'];

    protected $fillable = [
        'company_id', 'category_id', 'title', 'description', 'requirements',
        'job_type', 'location', 'salary_min', 'salary_max', 'deadline',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(JobReport::class);
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where(function (Builder $query): void {
                $query->whereNull('deadline')->orWhereDate('deadline', '>=', today());
            })
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('company', function (Builder $query): void {
                $query->where('status', 'active')
                    ->whereHas('owner', fn (Builder $owner) => $owner->where('status', 'active'));
            });
    }

    public function isOpenForApplications(): bool
    {
        return $this->status === 'approved'
            && (! $this->deadline || $this->deadline->isToday() || $this->deadline->isFuture())
            && $this->company?->status === 'active'
            && $this->company?->owner?->status === 'active';
    }
}
