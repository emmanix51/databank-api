<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviewer extends Model
{
    //

    protected $fillable = [
        'reviewer_name',
        'reviewer_description',
        'topic_id',
        'subtopic_id',
        'college_id',
        'program_id',
        'school_year'
       
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    public function college()
    {
        return $this->belongsTo(College::class);
    }
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
    public function subtopic()
    {
        return $this->belongsTo(Subtopic::class);
    }
}
