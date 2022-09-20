<?php

namespace App\Http\Controllers;

use App\Models\CompanyEmployee;
use App\Models\CompanyWorkshop;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkshopController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function add_workshop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => 'required',
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'required|image',
            'total_hours' => 'required',
            'date' => 'required',
            'instructor' => 'required',
            'meeting_type' => 'required',
            'additional_info' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/workshops';
                $uploadedFile->move($destinationPath, $filename);
            }
            $workshop = new Workshop();
            $workshop->title = $request->title;
            $workshop->description = $request->description;
            $workshop->image = $filename;
            $workshop->total_hours = $request->total_hours;
            $workshop->date = strtotime($request->date);
            $workshop->instructor = $request->instructor;
            $workshop->additional_info = $request->additional_info;
            $workshop->meeting_type = $request->meeting_type;
            $workshop->save();

            $company = json_decode($request->company);
            foreach ($company as $value) {
                $cw = new CompanyWorkshop();
                $cw->company_id = $value->id;
                $cw->workshop_id = $workshop->id;
                $cw->save();
            }
            return response(["status" => "success", 'res' => $workshop], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update_workshop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'image' => 'nullable|image',
            'total_hours' => 'required',
            'date' => 'required',
            'instructor' => 'required',
            'meeting_type' => 'required',
            'additional_info' => 'required',
            'company' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $workshop = Workshop::find($request->id);
            if ($request->hasFile('image')) {
                $destinationPath = public_path() . '/workshops';
                unlink($destinationPath . '/' . $workshop->image);
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move($destinationPath, $filename);
                $workshop->image = $filename;
            }
            $workshop->title = $request->title;
            $workshop->description = $request->description;
            $workshop->total_hours = $request->total_hours;
            $workshop->date = strtotime($request->date);
            $workshop->instructor = $request->instructor;
            $workshop->additional_info = $request->additional_info;
            $workshop->meeting_type = $request->meeting_type;
            $workshop->save();
            if ($request->company) {
                CompanyWorkshop::where('workshop_id', $request->id)->delete();
                $company = json_decode($request->company);
                foreach ($company as $value) {
                    $cw = new CompanyWorkshop();
                    $cw->company_id = $value->id;
                    $cw->workshop_id = $workshop->id;
                    $cw->save();
                }
            }
            return response(["status" => "success", 'res' => $workshop], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshops_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;

        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $workshops = Workshop::select('workshops.*')
                                ->join('company_workshops','company_workshops.workshop_id','workshops.id')
                                ->where('company_workshops.company_id',$company->company_id)
                                ->where('workshops.created_at', '!=', null);
        } else {
            $workshops = Workshop::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_workshops.company_id')->pluck('companies.company_name');
            }])->where('created_at', '!=', null);
        }
        if ($keyword) {
            $workshops = $workshops->where('title', 'like', "%$keyword%")
                ->orwhere('description', 'like', "%$keyword%");
        }
        if ($sort_by) {
            $workshops = $workshops->orderby($sort_by, "desc");
        }

        $workshops = $workshops->paginate(10);
        if($company){
            foreach($workshops as $c){
                $c->registered = WorkshopRegistration::where(['workshop_id' => $c->id, 'company_employee_id' => $company->id])->first();
            }
        }
        $path = url('/public/workshops/');
        return response(["status" => "success", 'res' => $workshops, 'path' => $path], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshop($id)
    {
        $registered = "";
        $user = Auth::guard('api')->user();
        if ($user->role == "ADMIN") {
            $workshops = Workshop::find($id);
            $workshops->users = WorkshopRegistration::select('workshop_registration.*', 'company_employees.first_name', 'company_employees.last_name', 'companies.company_name')
                ->join('company_employees', 'company_employees.id', 'workshop_registration.company_Employee_id')
                ->join('companies', 'company_employees.company_id', 'companies.id')
                ->where('workshop_id', $id)->get();
            $workshops->company = CompanyWorkshop::join('companies', 'companies.id', 'company_workshops.company_id')
                ->select('companies.id', 'companies.company_name as name')
                ->where('workshop_id', $id)
                ->get();
        } else {
            $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
            $workshops = Workshop::with('meetings')->where("id",$id)->first();
            $workshops->users = WorkshopRegistration::select('workshop_registration.*', 'company_employees.first_name', 'company_employees.last_name', 'companies.company_name')
                ->join('company_employees', 'company_employees.id', 'workshop_registration.company_Employee_id')
                ->join('companies', 'company_employees.company_id', 'companies.id')
                ->where('workshop_id', $id)->get();
            $registered = WorkshopRegistration::where(['workshop_id' => $id, 'company_employee_id' => $companyEmp->id])->first();
        }

        $path = url('/public/workshops/');
        return response(["status" => "success", 'res' => $workshops, 'path' => $path, 'registered' => $registered], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_workshop($id)
    {
        $workshop = Workshop::find($id);
        $destinationPath = public_path() . '/workshops';
        unlink($destinationPath . '/' . $workshop->image);
        $workshop->delete();
        return response(["status" => "success", 'res' => $workshop], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function register_for_workshop($id)
    {
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        $res = new WorkshopRegistration();
        $res->workshop_id = $id;
        $res->company_employee_id = $companyEmp->id;
        $res->Save();
        return response(["status" => "success", 'res' => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshops_list_for_select()
    {
        $res = Workshop::all();
        return response(["status" => "success", 'res' => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_workshop_list_dashboard()
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $ca = Workshop::select('workshops.*')
                ->join('company_workshops', 'company_workshops.workshop_id', 'workshops.id')
                ->where("company_id", $company->company_id)
                ->orderBy('id', 'desc')
                ->limit(4)
                ->get();
            foreach($ca as $c){
                $c->registered = WorkshopRegistration::where(['workshop_id' => $c->id, 'company_employee_id' => $company->id])->first();
                $c->image =   $path = url('/public/workshops/').'/'.$c->image;
            }
        }
        return response(["status" => "success", "res" => $ca], 200);
    }
}
