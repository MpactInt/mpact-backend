<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    public function company(){
        return $this->hasMany(CompanyResource::class,'resource_id','id');
    }
}
