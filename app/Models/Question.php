<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    //
    // use HasFactory;
    protected $fillable = [
        'college_id', 
        'program_id', 
        'topic_id', 
        'subtopic_id', 
        'question_content'
    ];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    /**
     * Get the program that owns the question.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the topic that owns the question.
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the subtopic that owns the question.
     */
    public function subtopic()
    {
        return $this->belongsTo(Subtopic::class);
    }
}
