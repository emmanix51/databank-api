<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    //
    protected $fillable = ['topic_name', 'topic_description', 'program_id', 'reviewer_id'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Reviewer::class);
    }

    public function subtopics()
    {
        return $this->hasMany(Subtopic::class);
    }

    public function reviewers()
    {
        return $this->belongsToMany(Reviewer::class, 'reviewer_topic')->withTimestamps();
    }
    
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
