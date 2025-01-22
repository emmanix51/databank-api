<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerAttemptAnswer extends Model
{
    //

    protected $table = 'reviewer_attempt_answer';
    protected $fillable = [
        'reviewer_attempt_question_id',
        'answer',
    ];

    public function attemptQuestion()
    {
        return $this->belongsTo(ReviewerAttemptQuestion::class, 'reviewer_attempt_question_id');
    }
}
