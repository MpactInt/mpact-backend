<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AssementController extends Controller
{
    // public function login(){
    //     $user = Auth::guard('api')->user();
    //     $company = Company::where('user_id',$user->id)->first();
    //     $u = ["id"=>$user->assesment_id,"email"=>$user->email,"password"=>$user->password,"name"=>$company->company_name];
    //     $url = env("ASSESMENT_URL")."api/assesment-login";
    //     // $url = env("ASSESMENT_URL")."login";
    //     $response = Http::post($url,$u);
    //     // dd($response);
    //     // $res = json_decode($response->body());
    //     // $n_u = User::find($user->id);
    //     // $n_u->assesment_id = $res->res->id;
    //     // $n_u->save();
    //     return response(["status" => "success","url"=> env("ASSESMENT_URL").'dashboard', "res" => $response->body()], 200);
    // }

    public function mpact_login(Request $request){
        $u = User::where("email",$request->email)->first();
        $u->assesment_id = $request->id;
        $u->save();
        return response(["status" => "success",'user'=>$u], 200);

    }


    public function mpact_update(Request $request){
        $id = $request->id;
        $email = $request->email;
        $email =
        $u = User::where("email",$email)->first();
        $user_id = $u->id;
        $data = DB::table('company_employees')->where('user_id', $user_id)->update(['profile_type_id' => $id ]);

        if($data){
            return response(["status" => "success",'message'=>'profile type updated suceessfully'], 200);
        }
    }
}
