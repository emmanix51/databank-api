<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewerAttempt extends Model
{
    //
    protected $fillable = [
        'user_id',
        'reviewer_id',
        'status',
        'score',
        'time_remaining',
        'expire_time'
    ];

    protected $casts = [
        'expire_time' => 'datetime',
    ];


    public function questions()
    {
        return $this->hasMany(ReviewerAttemptQuestion::class);
    }

    public function specification()
    {
        return $this->hasOne(ReviewerAttemptSpecification::class);
    }
    public function result()
    {
        return $this->hasOne(Result::class);
    }
}
