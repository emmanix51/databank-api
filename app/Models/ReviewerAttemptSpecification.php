<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerAttemptSpecification extends Model
{
    //
    protected $fillable = [
        'reviewer_attempt_id',
        'topic_ids',
        'subtopic_ids',
        'time_limit',
        'question_amount',
    ];

    protected $casts = [
        'topic_ids' => 'array',
        'subtopic_ids' => 'array',
    ];

    public function reviewerAttempt()
    {
        return $this->belongsTo(ReviewerAttempt::class);
    }

    // In ReviewerAttemptSpecification model

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'reviewer_attempt_specification_topics', 'reviewer_attempt_specification_id', 'topic_id');
    }

    public function subtopics()
    {
        return $this->belongsToMany(Subtopic::class, 'reviewer_attempt_specification_subtopics', 'reviewer_attempt_specification_id', 'subtopic_id');
    }

    // public function topics()
    // {
    //     return $this->belongsToMany(Topic::class, 'reviewer_attempt_specification_topics');
    // }

    // public function subtopics()
    // {
    //     return $this->belongsToMany(Subtopic::class, 'reviewer_attempt_specification_subtopics');
    // }
}
