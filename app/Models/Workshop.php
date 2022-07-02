<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    public function company(){
        return $this->hasMany(CompanyWorkshop::class,'workshop_id','id');
    }
    public function meetings(){
        return $this->hasMany(ZoomMeeting::class,'workshop_id','id');
    }
}
