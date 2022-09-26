<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyChatGroup extends Model
{
    public function new_message(){
        return $this->hasMany(GroupMessageRead::class,'group_id','id');
    }
}
