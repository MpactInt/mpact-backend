<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WelcomeNote extends Model
{
//    public function company(){
//        return $this->hasMany(CompanyWelcomeNote::class,'welcome_note_id','id')
//                ->join('companies','companies.id','company_welcome_notes.company_id')
//                ->select('company_name')
//                ->get();
//    }

    public function company(){
        return $this->hasMany(CompanyWelcomeNote::class,'welcome_note_id','id');
    }
}
