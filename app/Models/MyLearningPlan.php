<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyLearningPlan extends Model
{
    public function files(){
        return $this->hasMany(MyLearningPlanFile::class,'my_learning_plan_id','id');
    }
}
