<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviewer extends Model
{
    //
    protected $fillable = ['reviewer_name', 'reviewer_description', 'college_id', 'program_id', 'school_year'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function questions(){
        return $this->hasMany(Question::class);
    }
}
