<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    public function company(){
        return $this->hasMany(CompanyWorkshop::class,'workshop_id','id');
    }
}
