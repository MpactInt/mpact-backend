<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyLearningPlan extends Model
{
    public function files(){
        return $this->hasMany(LearningPlanResource::class,'learning_plan_id','id');
    }

    public function profileType(){
        return $this->hasMany(LearningPlanProfileType::class,'learning_plan_id','id');
    }
}
