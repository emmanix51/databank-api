<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerAttemptQuestion extends Model
{
    //

    protected $fillable = [
        'reviewer_attempt_id',
        'question_id',
        'isFlagged',
        'status',

    ];
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->hasOne(ReviewerAttemptAnswer::class, 'reviewer_attempt_question_id');
    }
}
