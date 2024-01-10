<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultingHours extends Model
{
    protected $fillable = [
        'user_id',
        'consulting_hour'
    ];
}
