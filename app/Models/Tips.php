<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tips extends Model
{
    public function categories(){
        return $this->hasMany(TipCategories::class,'tip_id','id');
    }
}
