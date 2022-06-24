<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;

use App\Models\Company;
use App\Models\CompanyAnnouncement;
use App\Models\CompanyEmployee;
use App\Models\CompanyFeedback;
use App\Models\CompanyQuestion;
use App\Models\CompanyResource;
use App\Models\Invitation;
use App\Models\User;
use App\Models\CompanyWelcomeNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmployerController extends Controller
{
    /**
     * @param $link
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_details($link)
    {
        $res = Company::select('users.email', 'companies.*')
            ->join('users', 'users.id', 'companies.user_id')
            ->where('employee_registration_link', $link)->first();
        $res->company_logo = url('public/uploads/' . $res->company_logo);
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function upload_logo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $user = Auth::guard('api')->user();
            $company = Company::where('user_id', $user->id)->first();

            $uploadedFile = $request->file('logo');
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();

            $destinationPath = public_path() . '/uploads';

            unlink($destinationPath . '/' . $company->company_logo);

            $uploadedFile->move($destinationPath, $filename);

            $company->company_logo = $filename;
            $company->save();

            $company->company_logo = url('public/uploads/' . $company->company_logo);
            return response(["status" => "success", 'res' => $company], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function ask_question(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        $company_id = $company->id;
        $aq = new CompanyQuestion();
        $aq->company_id = $company_id;
        $aq->description = $request->description;
        $aq->save();
        return response(["status" => "success", 'res' => $aq], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_question_list()
    {
        $ql = CompanyQuestion::select('company_questions.*','companies.company_name','company_employees.first_name','company_employees.last_name')
            ->join('company_employees','company_employees.id','company_questions.company_id')
            ->join('companies','companies.id','company_employees.company_id')
            ->paginate(10);
        return response(["status" => "success", 'res' => $ql], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_list()
    {
        $ql = Company::paginate(10);
        return response(["status" => "success", 'res' => $ql], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_hours(Request $request){
        $c = Company::find($request->companyId);
        $c->remaining_hours = $request->remainingHours;
        $c->save();
        return response(["status" => "success", 'res' => $c], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function submit_company_feedback(Request $request){
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();
        if($request->anonymous){
            $anonymous = 1;
        }else{
            $anonymous = 0;
        }
        $cf = new CompanyFeedback();
        $cf->company_id = $companyEmp->company_id;
        $cf->company_employee_id = $companyEmp->id;
        $cf->description = $request->description;
        $cf->anonymous = $anonymous;
        $cf->save();
        return response(["status" => "success", 'res' => $cf], 200);
    }

    public function get_company_feedback_list(){
        $user = Auth::guard('api')->user();
        $companyEmp = CompanyEmployee::where('user_id', $user->id)->first();

        $res = CompanyFeedback::select('company_feedbacks.*','company_employees.first_name','company_employees.last_name')
                              ->join('company_employees','company_employees.id','company_feedbacks.company_employee_id')
                              ->where('company_feedbacks.company_id',$companyEmp->company_id)
                              ->paginate(10);
        return response(["status" => "success", 'res' => $res], 200);

    }
}
