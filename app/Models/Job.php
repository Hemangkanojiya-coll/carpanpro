<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'skill_required',
        'state', 'city', 'address', 'budget', 'budget_type', 'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];

    public function poster()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function offers()
    {
        return $this->hasMany(JobOffer::class);
    }

    public function earnings()
    {
        return $this->hasMany(Earning::class);
    }
}
