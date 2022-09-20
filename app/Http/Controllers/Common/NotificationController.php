<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
  
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_admin_notifications()
    {
        
        $res = AdminNotification::orderBy('id','desc')->get();

        $unseen = AdminNotification::where("seen",0)->count();
    
        return response(["status" => "success", 'res' => $res,'unseen'=>$unseen], 200);
    }

       /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function read_admin_notifications()
    {
        $user = Auth::guard('api')->user();
        
        $res = AdminNotification::where('seen',0)->get();
        foreach($res as $r ){
            $r->seen = 1;
            $r->save();
        }
    
        return response(["status" => "success"], 200);
    }
   
}
