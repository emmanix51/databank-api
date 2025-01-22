<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionChoice extends Model
{
    //
    protected $fillable = ['question_id', 'choice_index', 'choice_content'];

    // A choice belongs to a question
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
