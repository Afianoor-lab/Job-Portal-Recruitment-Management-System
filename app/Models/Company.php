<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'companies';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_SUSPENDED,
    ];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'mime_type',
        'size',
        'description',
        'website',
        'location',
        'industry',
        'status',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Automatically generate a unique slug from name if not explicitly provided.
     */
    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->slug) && ! empty($company->name)) {
                $company->slug = Str::slug($company->name) . '-' . Str::random(5);
            }
        });
    }

    /**
     * Get the recruiter (user) that owns this company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }
}