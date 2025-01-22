<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    //
    protected $fillable = [
        'method',
        'url',
        'ip_address',
        'user_id',
        'payload',
    ];
}
