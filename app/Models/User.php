<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,SoftDeletes;
   public const STATUS_MODES = ['online', 'away', 'offline', 'dnd'];
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
      
        'name',
        'email',
        'password',
        'email_verified_at',
        'ip_address',
        'user_agent',
        'role',
        'password_changed_at',
        'remember_token',
        'last_activity',
        
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

   

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
        ];
    }


    




public function tokens(): HasMany
{
    return $this->hasMany(UserToken::class);
}
public function candidateProfile(): HasOne
{
    return $this->hasOne(CandidateProfile::class);
}

public function candidateCVs(): HasMany
{
    return $this->hasMany(CandidateCv::class,'candidate_id');
}


}
