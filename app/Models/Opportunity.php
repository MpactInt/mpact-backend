<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    public function company(){
        return $this->hasMany(CompanyOpportunity::class,'opportunity_id','id');
    }
}
