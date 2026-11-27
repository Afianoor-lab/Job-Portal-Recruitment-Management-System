<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCv extends Model
{
    protected $fillable = [
        'candidate_id', 'file_path', 'original_name', 'size', 'is_current', 'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
