<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyAnnouncement;
use App\Models\CompanyEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_announcement(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();
        $ca = new CompanyAnnouncement();
        $ca->company_id = $company->id;
        $ca->title = $request->title;
        $ca->description = $request->description;
        $ca->date = strtotime($request->date);
        $ca->save();
        return response(["status" => "success", "res" => $ca], 200);

    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_announcement($id)
    {
        $res = CompanyAnnouncement::find($id)->delete();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_announcement($id)
    {
        $res = CompanyAnnouncement::find($id);
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_announcement(Request $request)
    {
        $ca = CompanyAnnouncement::find($request->id);
        $ca->title = $request->title;
        $ca->description = $request->description;
        $ca->date = strtotime($request->date);
        $ca->save();
        return response(["status" => "success", "res" => $ca], 200);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_announcements_list($id, Request $request)
    {
        $res = CompanyAnnouncement::where('company_id', $id)->limit(3)->orderby('id', 'desc')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_all_announcements_list($id, Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;

        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        $company_id = $company->company_id;

        if($company->role != 'COMPANY_EMP')
        {
            $res = CompanyAnnouncement::withTrashed()->where('company_id', $company_id);
        }else{
            $res = CompanyAnnouncement::where('company_id', $company_id);
        }

         if ($keyword) {
         $res = $res->where(function($query)use($keyword) {
             return $query
                    ->where('title', 'LIKE', "%$keyword%")
                    ->orWhere('description','like',"%$keyword%");
            });
     }
        if ($sort_by) {
            $res = $res->orderby($sort_by, 'asc');
        }

        if($company->role == 'COMPANY_EMP'){
            $res = $res->limit(5);
        }

        $res = $res->orderBy('id','desc')->paginate(10);

        return response(["status" => "success", "res" => $res], 200);
    }
}
