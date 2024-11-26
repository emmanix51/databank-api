<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    //
    public function programs()
{
    return $this->hasMany(Program::class);
}

}
