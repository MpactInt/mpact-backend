<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\RequestWorkshop;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\AdminNotificationEvent;

class RequestWorkshopController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function request_workshop(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();
        $rw = new RequestWorkshop();
        $rw->company_id = $company->id;
        $rw->name = $request->name;
        $rw->workshop_focus = $request->workshop_focus;
        $rw->desired_date = $request->desired_date;
        $rw->workshop_length = $request->workshop_length;
        $rw->workshop_type = $request->workshop_type;
        $rw->audience = $request->audience;
        $rw->requirements = $request->requirements;
        $rw->expectations = $request->expectations;
        $rw->save();
        return response(["status" => "success", "res" => $rw], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshop_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;

        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $res = RequestWorkshop::where("company_id", $company->company_id);
        } else {
            $res = RequestWorkshop::select('request_workshops.*', 'companies.company_name')
                ->join('companies', 'companies.id', 'request_workshops.company_id');
        }
        if ($keyword) {
            $res = $res->where('name', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $res = $res->orderby($sort_by, $sort_order);
        }else{
            $res = $res->orderby('id','desc');
        }
        $res = $res->paginate(10);
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshop_list_dashboard()
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $res = RequestWorkshop::where("company_id", $company->company_id);
        }
        $res = $res->limit(5)->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_request_workshop($id)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();

        $workshop = RequestWorkshop::find($id);
        $workshop->delete();
        if ($user->role != "ADMIN") {
            $an = new AdminNotification();
            $an->from_company_id = $company->id;
            $an->from_employee_id = $companyEmp->id;
            $an->notification = $company->company_name . " deleted requested workshop " . $workshop->name;
            $an->link = "/admin/request-workshop";
            $an->save();

            $admin = User::where('role', 'ADMIN')->first();
            event(new AdminNotificationEvent($an, $admin->id));
        }
        return response(["status" => "success", 'res' => $workshop], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function accept_request_workshop($id)
    {
        $workshop = RequestWorkshop::find($id);
        $workshop->status = 'ACCEPTED';
        $workshop->save();
        return response(["status" => "success", 'res' => $workshop], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function reject_request_workshop($id)
    {
        $workshop = RequestWorkshop::find($id);
        $workshop->status = 'REJECTED';
        $workshop->save();
        return response(["status" => "success", 'res' => $workshop], 200);
    }
}
