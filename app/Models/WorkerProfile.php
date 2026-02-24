<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerProfile extends Model
{
    protected $fillable = [
        'user_id', 'skills', 'bio', 'experience_years',
        'hourly_rate', 'availability', 'profile_photo',
        'rating', 'total_reviews',
    ];

    protected $casts = [
        'skills'      => 'array',
        'hourly_rate' => 'decimal:2',
        'rating'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
