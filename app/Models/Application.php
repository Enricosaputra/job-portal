<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;
    protected $fillable = [
        'job_id',
        'user_id',
        'cover_letter',
        'status',
        'completion_notes',
        'completed_at',
        'rating',
        'completed_by'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        // ... other casts
];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
