<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    public function toolkit(){
        return $this->hasMany(StepToolkit::class);
    }
}
