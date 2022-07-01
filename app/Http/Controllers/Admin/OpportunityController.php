<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmployee;
use App\Models\CompanyOpportunity;
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
        $o = new Opportunity();
        $o->content = $request->description;
        $o->save();
        foreach ($request->company as $c) {
            $ca = new CompanyOpportunity();
            $ca->company_id = $c['id'];
            $ca->opportunity_id = $o->id;
            $ca->save();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_opportunity(Request $request){
        $o = Opportunity::find($request->id);
        $o->content = $request->description;
        $o->save();
        if($request->company){
            CompanyOpportunity::where('opportunity_id',$request->id)->delete();
            foreach ($request->company as $c) {
                $ca = new CompanyOpportunity();
                $ca->company_id = $c['id'];
                $ca->opportunity_id = $o->id;
                $ca->save();
            }
        }
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_opportunity($id){
        $ca = Opportunity::select('id','content as description')
            ->where('id',$id)->first();
        $ca->company = CompanyOpportunity::join('companies','companies.id','company_opportunities.company_id')
                        ->select('companies.id','companies.company_name as name')
                        ->where('opportunity_id',$id)
                        ->get();
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
            $ca = Opportunity::select('opportunities.*')
                ->join('company_opportunities', 'company_opportunities.opportunity_id', 'opportunities.id')
                ->where("company_id", $company->company_id)
                ->paginate(10);
        } else {
            $ca = Opportunity::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_opportunities.company_id')->pluck('companies.company_name');
            }])->paginate(10);
        }
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_opportunity($id){
        $ca = Opportunity::find($id)->delete();
        CompanyOpportunity::where('company_id',$id)->delete();
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
            $ca = Opportunity::select('opportunities.*')
                            ->join('company_opportunities','company_opportunities.opportunity_id','opportunities.id')
                            ->where("company_id", $company->company_id)
                            ->orderBy('id','desc')
                            ->first();
        }
        return response(["status" => "success", "res" => $ca], 200);
    }
}
