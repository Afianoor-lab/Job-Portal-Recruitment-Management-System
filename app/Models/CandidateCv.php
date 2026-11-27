<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateCv extends Model
{
    use HasFactory;

    protected $table = 'candidate_cvs';

    protected $fillable = [
        'candidate_id',
        'file_path',
        'mime_type',
        'size',
        'is_current',
        'uploaded_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_current' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the candidate (user) that owns this CV.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}