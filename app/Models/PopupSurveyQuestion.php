<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupSurveyQuestion extends Model
{
    public function answer(){
        return $this->hasMany(PopupSurveyAnswer::class,'question_id','id');
    }
}
