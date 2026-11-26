<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PasswordReset extends Model
{
    use HasFactory;
    //const UPDATED_AT = null;

     protected $table = 'password_reset_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at'
    ];

    protected function casts(): array
    {
        return [
            // 'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

  

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
       
    }
}
