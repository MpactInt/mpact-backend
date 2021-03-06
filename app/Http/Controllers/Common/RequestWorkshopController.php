<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\RequestWorkshop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if ($sort_by) {
            $res = $res->orderby($sort_by, "asc");
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
        $workshop = RequestWorkshop::find($id);
        $workshop->delete();
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
