<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDashboardSetion3 extends Model
{
    protected $table="employee_dashboard_section3";
    public function images(){
        return $this->hasMany(EmployeeDashboardSetion3Image::class,'section3_id','id');
    }
}
