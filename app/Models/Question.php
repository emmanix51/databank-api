<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    //
    // use HasFactory;
    protected $fillable = ['question_content', 'correct_answer', 'question_point', 'reviewer_id', 'topic_id', 'subtopic_id', 'status'];

    // A question belongs to a subtopic
    public function reviewer()
    {
        return $this->belongsTo(Reviewer::class);
    }
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
    public function subtopic()
    {
        return $this->belongsTo(Subtopic::class);
    }

    // A question has many choices
    public function choices()
    {
        return $this->hasMany(QuestionChoice::class);
    }
}
