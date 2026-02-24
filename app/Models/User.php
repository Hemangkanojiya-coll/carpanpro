<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'state',
        'city',
        'role',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── JWT Interface Methods ───────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class, 'worker_id');
    }

    public function conversations()
    {
        return Conversation::where('user_one', $this->id)
                           ->orWhere('user_two', $this->id);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function earnings()
    {
        return $this->hasMany(Earning::class, 'worker_id');
    }
}
