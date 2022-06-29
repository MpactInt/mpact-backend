<?php

namespace App\Http\Controllers\Employer;

use App\Exports\CompanyEmployeeExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class TeamController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_employee_registration_link(Request $request)
    {
        $user = Auth::guard('api')->user();
        $employer = Company::where('user_id', $user->id)->first();
        if ($employer) {
            $employer->employee_registration_link = env('FRONT_URL') . '/registration/' . $employer->employee_registration_link;
        }
        return response(["status" => "success", "res" => $employer], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send_link_to_email(Request $request)
    {
        $link = $request->link;
        $email = $request->email;
        $company = $request->company_name;
        $data = ['link' => $link, 'company_name' => $company];
        Mail::send('registration-email-employee', $data, function ($message) use ($email) {
            $message->to($email, 'MPACT INT')
                ->subject('Employee registration link');
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });
        $i = new Invitation();
        $i->company_id = $request->company_id;
        $i->email = $email;
        $i->save();
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_employees_list($id, Request $request)
    {
        $user = Auth::guard('api')->user();
        $auth_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $page = $request->page;
        $name = $request->name;
        $email = $request->email;
        $sort_by = $request->sortBy;
        $res = CompanyEmployee::select('users.last_login', 'users.email', 'company_employees.*','profile_types.profile_type')
            ->join('users', 'company_employees.user_id', 'users.id')
            ->join('profile_types','profile_types.id','company_employees.profile_type_id')
            ->where('company_id', $id)
//            ->where('company_employees.role', '!=', 'COMPANY_ADMIN');
            ->where('company_employees.id', '!=', $auth_id);
        if ($name) {
            $res = $res->where('company_employees.first_name', 'like', "%$name%");
        }
        if ($email) {
            $res = $res->where('email', 'like', "%$email%");
        }
        if ($sort_by) {
            $res = $res->orderby($sort_by, 'asc');
        }
        $res = $res->paginate(10);

        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_employee($id)
    {
        $res = CompanyEmployee::find($id);
        User::find($res->user_id)->delete();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_employee($id)
    {
        $res = CompanyEmployee::find($id);
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_employee(Request $request)
    {
        $e = CompanyEmployee::find($request->id);
        $e->first_name = $request->firstname;
        $e->last_name = $request->lastname;
        $e->role = $request->role;
        $e->profile_type_id = $request->profileType;
        $e->save();
        return response(["status" => "success", "res" => $e], 200);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_invitations_list($id, Request $request)
    {
        $page = $request->page;
        $email = $request->email;
        $sort_by = $request->sortBy;
        $res = Invitation::where('company_id', $id);
        if ($email) {
            $res = $res->where('email', 'like', "%$email%");
        }
        if ($sort_by) {
            $res = $res->orderby($sort_by, 'asc');
        }
        $res = $res->paginate(10);

        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */

    public function export_employees($id, Request $request)
    {
        return Excel::download(new CompanyEmployeeExport($id), 'employees.xlsx');
    }
}