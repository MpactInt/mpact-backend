<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\Country;
use App\Models\Invitation;
use App\Models\User;
use ChargeBee\ChargeBee\Models\Estimate;
use ChargeBee\ChargeBee\Models\ItemPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create_company(Request $request)
    {
        $companyname = $request->companyname;
        $email = $request->email;
        $plan = $request->plan;
        $periodUnit = $request->periodUnit;
        $planType = $request->planType;
        $total_employees = $request->employees;
        $domain = $request->domain;
        $password = $request->password;
        $link = md5(uniqid());
        $hours = 0;
        if ($planType == 'Package-3-Premier') {
            $hours = 96;
        } elseif ($planType == 'Package-2-Enhanced') {
            $hours = 16;
        } elseif ($planType == 'Package-1-Basic') {
            $hours = 8;
        }

        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';


        $validator = Validator::make($request->all(), [
            'companyname' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|max:255|min:8',
            'domain' => 'required|max:255|regex:' . $regex . '|unique:companies,company_domain',
            'employees' => 'required|max:255',
            'plan' => 'required|max:255',
            //            'addon' => 'required|max:255',
            // 'logo' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {

            // $uploadedFile = $request->file('logo');
            // $filename = time() . '_' . $uploadedFile->getClientOriginalName();

            // $destinationPath = public_path() . '/uploads';
            // $uploadedFile->move($destinationPath, $filename);

            $parsed = parse_url($domain);
            if (empty($parsed['scheme'])) {
                $domain = 'http://' . ltrim($domain, '/');
            }
            $u = new User();
            $u->email = $email;
            $u->password = Hash::make($password);
            $u->role = 'COMPANY';
            $u->save();

            $cu = new Company();
            $cu->user_id = $u->id;
            $cu->company_name = $companyname;
            $cu->company_domain = $domain;
            $cu->chargebee_customer_id = 1;
            $cu->selected_plan_id = $plan;
            $cu->period_unit = $periodUnit;
            $cu->plan_type = $planType;
            $cu->total_hours = $hours;
            $cu->remaining_hours = $hours;
            $cu->total_employees = $total_employees;
            $cu->employee_registration_link = $link;
            $cu->company_logo = 'default.png';
            $cu->save();

            $emp = new CompanyEmployee();
            $emp->user_id = $u->id;
            $emp->company_id = $cu->id;
            $emp->first_name = "Company";
            $emp->last_name = "Admin";
            $emp->role = "COMPANY_ADMIN";
            $emp->profile_type_id = 1;
            $emp->save();

            //            $link1 = env('FRONT_URL') . '/registration/' . $link;
            //            $data = ['link' => $link1, 'name' => $companyname];
            //            Mail::send('registration-email', $data, function ($message) use ($email) {
            //                $message->to($email, 'MPACT INT')
            //                    ->subject('Employee registration link');
            //                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            //            });

            return response()->json(['status' => 'success', 'res' => $cu], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create_company_employee(Request $request)
    {
        $email = $request->email;
        $firstname = $request->firstname;
        $lastname = $request->lastname;
        $password = $request->password;
        $link = $request->link;
        $role = $request->role;
        $pt = $request->profileType;
        if (!$password) {
            $password = uniqid();
        }
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users,email',
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            //            'password' => 'required|max:255|min:8',
            'profileType' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $company = Company::where('employee_registration_link', $link)->first();
            if ($company) {
                $total_emp = CompanyEmployee::where('company_id', $company->id)->count();
                if ($total_emp < $company->total_employees) {
                    $employee_email_domain = explode('@', $email);
                    $employee_email_domain = $employee_email_domain[1];
                    $company_domain = $this->remove_http($company->company_domain);

                    if ($employee_email_domain == $company_domain) {
                        $u = new User();
                        $u->email = $email;
                        $u->password = Hash::make($password);
                        $u->role = "COMPANY";
                        $u->save();

                        $emp = new CompanyEmployee();
                        $emp->user_id = $u->id;
                        $emp->company_id = $company->id;
                        $emp->first_name = $firstname;
                        $emp->last_name = $lastname;
                        $emp->role = $role ?? "COMPANY_EMP";
                        $emp->profile_type_id = $pt;
                        $emp->save();

                        if (!$request->password) {
                            $link = md5(uniqid());
                            $link1 = env('FRONT_URL') . '/create-password/' . $link;
                            DB::table('password_resets')->insert(['email' => $email, 'token' => $link]);
                            $data = array('link' => $link1, 'text' => 'You can use below link to create your password', 'link_text' => 'Click to create your password');
                            Mail::send('forgot-pass-email', $data, function ($message) use ($u) {
                                $message->to($u->email, 'MPACT INT')->subject('Create Password Email');
                                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            });
                        }

                        Invitation::where('email', $email)->delete();

                        return response()->json(['status' => 'success', 'res' => $emp], 200);
                    } else {
                        return response()->json(['status' => 'error', 'message' => 'Employee email is not valid, it does not belongs to company', 'domain' => $company_domain], 400);
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'You can not register, because total number of employees registration limit is exceeded'], 400);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Registration link is not valid'], 400);
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
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];
        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'string'],
            'password' => ['required', 'string']
        ]);
        $u = User::join('company_employees','users.id','company_employees.user_id')->withTrashed()->where('email',$request->email)->first();
        if($u){
            $c = User::join('companies','users.id','companies.user_id')->withTrashed()->where('companies.id',$u->company_id)->first();
        }
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->getMessageBag()->first()], 400);
        } else {
            if (!Auth::attempt($data)) {
                if ($u) {
                    if ($u->role=="COMPANY_ADMIN" && $u->deleted_at) {
                        return response()->json(['status' => 'error', 'message' => 'Access Error. Please contact Admin'], 400);
                    }elseif($u->role == "COMPANY_EMP" && $c->deleted_at){
                        return response()->json(['status' => 'error', 'message' => 'Access Error. Please contact Admin'], 400);
                    } else {
                        return response()->json(['status' => 'error', 'message' => 'Invalid Credentials','user'=>$u], 400);
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Invalid Credentials','user'=>$u], 400);
                }
            }else{
                if($u && $u->role == "COMPANY_EMP" && $c->deleted_at){
                    return response()->json(['status' => 'error', 'message' => 'Access Error. Please contact Admin'], 400);
                } else {
                    $accessToken = Auth::user()->createToken('authToken')->accessToken;
                    $user = User::where('email', $request->email)->first();
                    $c = null;

                    if ($user->role == "COMPANY") {
                        $c = Company::select('companies.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.role', 'company_employees.profile_type_id', 'company_employees.profile_image')
                            ->join('company_employees', 'companies.id', 'company_employees.company_id')
                            ->where("company_employees.user_id", $user->id)
                            ->first();
                        if ($c) {
                            $c->company_logo = url('/') . '/public/uploads/' . $c->company_logo;
                            $c->profile_image =  url('/') . '/public/profile-images/' . $c->profile_image;
                        }
                    }
                    $user->last_login = DB::raw('CURRENT_TIMESTAMP');
                    $user->save();

                    return response(['user' => $user, 'company' => $c, 'access_token' => $accessToken]);
                }
            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $user = Auth::user()->token();
        $user->revoke();
        return response(["status" => "success", "message" => "User logout successfully"], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function send_email(Request $request)
    {
        $email = $request->email;
        $user = User::where("email", $email)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "This email is not registered"], 400);
        } else {
            $link = md5(uniqid());
            $link1 = env('FRONT_URL') . '/reset-password/' . $link;
            DB::table('password_resets')->insert(['email' => $email, 'token' => $link, 'expiry' => strtotime("+10 minutes")]);
            $data = array('link' => $link1, 'text' => 'You can use below link to reset your password, this link will be expired in 10 min', 'link_text' => 'Click to reset your password');
            Mail::send('forgot-pass-email', $data, function ($message) use ($user) {
                $message->to($user->email, 'MPACT INT')->subject('Reset Password Email');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
            return response(["status" => "success", "message" => "Email Sent Successfully"], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function reset_password(Request $request)
    {
        $link = DB::table('password_resets')->where('token', $request->link)->first();

        $validator = Validator::make($request->all(), [
            'password' => 'required|max:255|min:8',
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } elseif (!$link) {
            return response()->json(['status' => 'error', 'message' => "Reset Password link is not valid"], 400);
        } elseif ($link->expiry < time()) {
            return response()->json(['status' => 'error', 'message' => "Reset Password link is expired"], 400);
        } else {
            $user = User::where('email', $link->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();
            DB::table('password_resets')->where("email", $link->email)->delete();
            return response(["status" => "success", "message" => "Password Changed Successfully"], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function create_password(Request $request)
    {
        $link = DB::table('password_resets')->where('token', $request->link)->first();

        $validator = Validator::make($request->all(), [
            'password' => 'required|max:255|min:8',
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } elseif (!$link) {
            return response()->json(['status' => 'error', 'message' => "Create Password link is not valid"], 400);
        } else {
            $user = User::where('email', $link->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();
            DB::table('password_resets')->where("email", $link->email)->delete();
            return response(["status" => "success", "message" => "Password Created Successfully"], 200);
        }
    }

    /**\
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_company_list()
    {
        $res = Company::select('id', 'company_name as name')->get();
        return response(["status" => "success", "res" => $res], 200);
    }
    public function get_countries()
    {
        $res = Country::all();
        return response(["status" => "success", "res" => $res], 200);
    }
}
