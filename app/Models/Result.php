<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'reviewer_attempt_id',
        'user_id',
        'marks',
        'total_questions',
        'grade',
        'scope',
        'feedback',
        'finished_at',
    ];

    protected $casts = [
        'scope' => 'array',  // Casts the scope to a JSON array
        'finished_at' => 'datetime',
    ];

    public function reviewerAttempt()
    {
        return $this->belongsTo(ReviewerAttempt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
