<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
       protected $fillable = [
        'user_id',
        'login_time',
        'logout_time',
        'log_type', // Add 'log_type' to the fillable array
    ];
}
