<?php

namespace App\Http\Controllers;

use App\Models\CompanyEmployee;
use App\Models\CompanyWorkshop;
use App\Models\WorkshopProfileType;
use App\Models\Workshop;
use App\Models\Company;
use App\Models\User;
use App\Models\WorkshopRegistration;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Events\AdminNotificationEvent;

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

            if ($request->profile_type) {
                $profile_type = json_decode($request->profile_type);
                foreach ($profile_type as $value) {
                    $pw = new WorkshopProfileType();
                    $pw->profile_type_id = $value->id;
                    $pw->workshop_id = $workshop->id;
                    $pw->save();
                }
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
            'company' => 'required',
            'profile_type' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            $workshop = Workshop::find($request->id);
            if ($request->hasFile('image')) {
                $destinationPath = public_path() . '/workshops';
                if(file_exists($destinationPath . '/' . $workshop->image)){
                    unlink($destinationPath . '/' . $workshop->image);
                }
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

            if ($request->profile_type) {
                WorkshopProfileType::where('workshop_id', $request->id)->delete();
                $profile_type = json_decode($request->profile_type);
                foreach ($profile_type as $value) {
                    $pw = new WorkshopProfileType();
                    $pw->profile_type_id = $value->id;
                    $pw->workshop_id = $workshop->id;
                    $pw->save();
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
        $sort_by = $request->sortBy == "" ? "created_at" : $request->sortBy;
        $sort_order = $request->sortOrder == "" ? "DESC" : $request->sortOrder;
        //return response(["status" => "success", 'res' => $sort_by], 200);

        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) {
            $workshops = Workshop::select('workshops.*')
                                ->join('company_workshops','company_workshops.workshop_id','workshops.id')
                                ->join('workshop_profile_types','workshop_profile_types.workshop_id', 'workshops.id')
                                ->where('company_workshops.company_id',$company->company_id)
                                ->where('workshop_profile_types.profile_type_id',$company->profile_type_id)
                                ->where('workshops.date','>',time())
                                ->where('workshops.created_at', '!=', null)
                                ->orderby('workshops.date');
        } else {
            $workshops = Workshop::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_workshops.company_id')->pluck('companies.company_name');
            }])
            ->where('date','>',time())
            ->where('created_at', '!=', null)
            ->orderby('date');
        }
        if ($keyword) {
            $workshops = $workshops->where('title', 'like', "%$keyword%")
                ->orwhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $workshops = $workshops->orderby($sort_by, $sort_order);
        }

        $workshops = $workshops->paginate(10);
        if($company){
            foreach($workshops as $c){
                $c->registered = WorkshopRegistration::where(['workshop_id' => $c->id, 'company_employee_id' => $company->id])->first();
            }
        }

        foreach($workshops as $k=>$c){
            $workshops[$k]->date = date("Y-m-d H:i:s", $c->date);//$c->date;
        }
        $path = url('/public/workshops/');
        return response(["status" => "success", 'res' => $workshops, 'path' => $path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_upcoming_workshop(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        if ($company) { 
            $workshop = Workshop::select('workshops.*')
                                ->join('company_workshops','company_workshops.workshop_id','workshops.id')
                                ->join('workshop_profile_types','workshop_profile_types.workshop_id', 'workshops.id')
                                ->where('company_workshops.company_id',$company->company_id)
                                ->where('workshop_profile_types.profile_type_id',$company->profile_type_id)
                                ->where('workshops.date','>',time())
                                ->where('workshops.created_at', '!=', null)
                                ->orderby('workshops.date')
                                ->first();
        } else { 
            $workshop = Workshop::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_workshops.company_id')->pluck('companies.company_name');
            }])
            ->where('date','>',time())
            ->where('created_at', '!=', null)
            ->orderby('date')
            ->first();
        }
        
        if($workshop){
            if($company){
                    $workshop->registered = WorkshopRegistration::where(['workshop_id' => $workshop->id, 'company_employee_id' => $company->id])->first();
            }
            $workshop->date = date("Y-m-d H:i:s", $workshop->date);//$c->date;
        }
        
        $path = url('/public/workshops/');
        return response(["status" => "success", 'res' => ['data'=>[$workshop]], 'path' => $path], 200);
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
            $workshops->profile_type = WorkshopProfileType::join('profile_types', 'profile_types.id', 'workshop_profile_types.profile_type_id')
                ->select('profile_types.id', 'profile_types.profile_type as name')
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
        $workshops->date = date("Y-m-d H:i:s", $workshops->date);

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
        $company = Company::where('user_id', $user->id)->first();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        $res = new WorkshopRegistration();
        $res->workshop_id = $id;
        $res->company_employee_id = $companyEmp->id;
        $res->Save();

        $workshop = Workshop::find($id);

        $an = new AdminNotification();
        $an->from_company_id = $companyEmp->company_id;
        $an->from_employee_id = $companyEmp->id;
        $an->notification = $companyEmp->first_name." ".$companyEmp->last_name." registered for workshop ".$workshop->name;
        $an->link = "/admin/view-workshop/".$id;
        $an->save();

        $admin = User::where('role','ADMIN')->first();
        event(new AdminNotificationEvent($an, $admin->id));

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
                ->where('date','>',time())
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
