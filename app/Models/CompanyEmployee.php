<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyEmployee extends Model
{
    public function new_message(){
        return $this->hasMany(OneToOneMessage::class,'sender_id','id');
    }
}
