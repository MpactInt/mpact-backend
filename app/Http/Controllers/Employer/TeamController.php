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
use Illuminate\Support\Facades\Validator;

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
        $data = $request->all();
       $validator = Validator::make($data, [
                'email' => ['required', 'email', 'string'],
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->getMessageBag()->first()], 400);
            } else {
                $link = $request->link;
                $email = $request->email;
                $employee_email_domain = explode('@', $email);
                $employee_email_domain = $employee_email_domain[1];

                $user = Auth::guard('api')->user();
                $employer = Company::where('user_id', $user->id)->first();

                $company_domain = $this->remove_http($employer->company_domain);


                // $company_domain = preg_replace( "#^[^:/.]*[:/]+#i", "", preg_replace( "{/$}", "", urldecode( $employer->company_domain ) ) );

                if ($employee_email_domain == $company_domain) {
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
                }else{
                       return response()->json(['status' => 'error', 'message' => 'Employee email is not valid, it does not belongs to company','emp_do'=>$employee_email_domain,'comp_do'=>$company_domain], 400);

                }
        }
    }

    public function remove_http($url)
    {
        $url = preg_replace("#^[^:/.]*[:/]+#i", "", preg_replace("{/$}", "", urldecode($url)));

        $disallowed = array('www.');
        foreach ($disallowed as $d) {
            if (strpos($url, $d) === 0) {
                return str_replace($d, '', $url);
            }
        }
        return $url;
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_employees_list($id, Request $request)
    {
        $user = Auth::guard('api')->user();
        $auth = CompanyEmployee::where('user_id', $user->id)->first();
        $auth_id = $auth->id;
        $id = $auth->company_id;
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
