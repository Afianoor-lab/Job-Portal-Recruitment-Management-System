<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidate_profiles';

    protected $fillable = [
        'user_id',
        'name',
        'summary',
        'location',
        'skills',
        'education',
        'experience',
    ];

    // protected $casts = [
       
    // ];

    /**
     * Get the user that owns this candidate profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}