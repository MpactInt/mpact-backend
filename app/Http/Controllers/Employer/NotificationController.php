<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\CompanyEmployeeWelcomeNote;
use App\Models\CompanyWelcomeNote;
use App\Models\WelcomeNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WelcomeNoteController extends Controller
{
  
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_admin_notifications()
    {
        $user = Auth::guard('api')->user();
        
        $res = AdminNotification::all();
    
        return response(["status" => "success", 'res' => $res], 200);
    }
   
}
