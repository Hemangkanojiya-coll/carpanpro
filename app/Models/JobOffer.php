<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    protected $fillable = [
        'job_id', 'worker_id', 'employer_id', 'status', 'message',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }
}
