<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use App\Models\CompanyAnnouncement;
use App\Models\RequestWorkshop;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_announcement_list($id)
    {
        $res = CompanyAnnouncement::where('company_id', $id)->limit(3)->orderby('id', 'desc')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_requested_workshop_list($id)
    {
        $res = RequestWorkshop::where("company_id", $id)->limit(3)->orderby('id', 'desc')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_workshop_list($id)
    {
        $res = Workshop::select('workshops.*')
            ->join('company_workshops', 'company_workshops.workshop_id', 'workshops.id')
            ->where("company_id", $id)
            ->where('date', '>', time())
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_resource_list($id)
    {
        $company = Company::find($id)->first();
        if ($company) {
            if($company->role == "COMPANY_EMP"){
            $resources = Resource::select('resources.*')
                            ->join('company_resources','company_resources.resource_id','resources.id')
                            ->where("company_id", $company->id)
                            ->where("visibility","PUBLIC");
            }else{
                $resources = Resource::select('resources.*')
                    ->join('company_resources','company_resources.resource_id','resources.id')
                    ->where("company_id", $company->id);
                }
        } else {
            $resources = Resource::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_resources.company_id')->pluck('companies.company_name');
            }])->select('resources.*');
        }
        $resources = $resources->limit(3)->orderBy('id', 'desc')->get();
        return response(["status" => "success", "res" => $resources], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_chat_list($id)
    {
        $user = Auth::guard('api')->user();
        $auth = CompanyEmployee::where('company_id', $id)->first();
        $auth_id = $auth->id;
        $id = $auth->company_id;
        $res = CompanyEmployee::with(['new_message' => function ($q) use ($auth_id) {
            $q = $q->where(['seen' => 0, 'rec_id' => $auth_id]);
        }])->select('users.last_login', 'users.email', 'company_employees.*', 'profile_types.profile_type')
            ->join('users', 'company_employees.user_id', 'users.id')
            ->join('profile_types', 'profile_types.id', 'company_employees.profile_type_id')
            ->where('company_id', $id)
            ->where('company_employees.id', '!=', $auth_id);
        $res = $res->orderby('id', 'desc')->limit(3)->get();
        return response(["status" => "success", "res" => $res], 200);
    }
}
