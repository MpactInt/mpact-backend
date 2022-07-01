<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    public function company(){
        return $this->hasMany(CompanyTodo::class,'todo_id','id');
    }
}
