<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtopic extends Model
{
    //

    public function reviewers()
    {
        return $this->hasMany(Reviewer::class);
    }
}
