<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunityController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_opportunity(Request $request){
        foreach ($request->company as $c) {
            $ca = new Opportunity();
            $ca->company_id = $c['id'];
            $ca->content = $request->description;
            $ca->save();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_opportunity(Request $request){
        $ca = Opportunity::find($request->id);
        $ca->company_id = $request->company;
        $ca->content = $request->description;
        $ca->save();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_opportunity($id){
        $ca = Opportunity::select('id','company_id','content as description')->where('id',$id)->first();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_opportunity_list(Request $request){
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $ca = Opportunity::where("company_id", $company->company_id)->paginate(10);
        } else {
            $ca = Opportunity::select('opportunities.*', 'companies.company_name')->join('companies', 'companies.id', 'opportunities.company_id')->paginate(10);
        }
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_opportunity($id){
        $ca = Opportunity::find($id)->delete();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_opportunity_list_dashboard(Request $request){
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $ca = Opportunity::where("company_id", $company->company_id)->orderBy('id','desc')->first();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }
}
