<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordEmail;
use App\Mail\SendEmployeeRegistrationEmail;
use App\Mail\SendRegistrationEmail;
use App\Mail\sendCompanyRegistrationEmail;
use App\Mail\SendEmployeePart1Email;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\Country;
use App\Models\Invitation;
use App\Models\PlanTier;
use App\Models\User;
use ChargeBee\ChargeBee\Models\Estimate;
use ChargeBee\ChargeBee\Models\ItemPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\ConsultingHours;

class HomeController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create_company(Request $request)
    {
        $companyname = $request->companyname;
        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $email = $request->email;
        $plan = $request->plan;
        $periodUnit = $request->periodUnit;
        $planType = $request->planType;
        $total_employees = $request->employees;
        $domain = $request->domain;
        $password = $request->password;
        $email_password_detail = $request->email_password_detail;
        $duration = $request->duration;
        $learning_plan_start_date = $request->learning_plan_start_date;


        $link = md5(uniqid());
        $hours = 0;

        //return response()->json(["status" => "error", "message" => $error], 400);

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
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
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


            $planTier = PlanTier::where('plan_id', $plan)
                ->whereRaw("$total_employees between starting_unit and ending_unit")
                //->where('starting_unit', '<=', $total_employees)->where('ending_unit', '>=', $total_employees)
                ->first();

            $max_employees = $planTier->ending_unit;
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
            $cu->max_employees = $max_employees;
            $cu->employee_registration_link = $link;
            $cu->duration = $duration;
            $cu->learning_plan_start_date = $learning_plan_start_date;

            $cu->company_logo = 'default.png';
            $cu->save();

            $emp = new CompanyEmployee();
            $emp->user_id = $u->id;
            $emp->company_id = $cu->id;
            $emp->first_name = $first_name;
            $emp->last_name = $last_name;
            $emp->role = "COMPANY_ADMIN";
            $emp->profile_type_id = 1;
            $emp->save();

            $maildata = array('name' => $companyname, 'first_name' => $first_name, 'last_name' => $last_name);
            if ($email_password_detail) {
                $maildata['email'] = $email;
                $maildata['password'] = $password;
                Mail::to($email)->send(new sendCompanyRegistrationEmail($maildata));
            }
            else
            {

                Mail::to($email)->send(new SendRegistrationEmail($maildata));
            }


            return response()->json(['status' => 'success', 'res' => $cu], 200);
        }
    }

    public function add_consulting_hours(Request $request)
    {
        $chs = new ConsultingHours();
        $chs->company_id = $request->company_id;
        $chs->consulting_hour = $request->consulting_hours;
        $chs->save();
        return response(["status" => "success", "res" => $chs], 200);
    }

    public function get_consulting_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy == "" ? "created_at" : $request->sortBy;
        $sort_order = $request->sortOrder == "" ? "DESC" : $request->sortOrder;

        $conh = ConsultingHours::select('consulting_hours.*','companies.company_name')
                ->join('companies','companies.id','consulting_hours.company_id');

        if ($keyword) {
            $conh = $conh->where('companies.company_name', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $conh = $conh->orderby($sort_by, $sort_order);
        }

        $conh = $conh->paginate(10);
        return response(["status" => "success", "res" => $conh], 200);

    }
    public function get_consulting_hours($id)
    {
        $conh = ConsultingHours::select('consulting_hours.*','companies.company_name')
                ->join('companies','companies.id','consulting_hours.company_id')
                ->where('consulting_hours.id',$id)
                ->first();
            return response(["status" => "success", "res" => $conh], 200);
    }
    public function update_consulting_hours(Request $request)
    {
        $conh = ConsultingHours::find($request->id);
        $conh->company_id = $request->company;
        $conh->consulting_hour = $request->consulting_hours;
        $conh->update();
        return response(["status" => "success", "res" => $conh], 200);

    }
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function update_plan(Request $request)
    {
        //return response()->json(["status" => "error", "message" => $request->plan['id']], 400);
        $employee_registration_link = $request->link;
        $c = Company::where('employee_registration_link', $employee_registration_link)->first();
        $validator = Validator::make($request->all(), [
            'plan' => 'required|max:255',
            'employees' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            if ($c) {
                $c->selected_plan_id = $request->plan['id'];
                $c->total_employees = $request->employees;
                $c->save();
            }
            return response(["status" => "success", "res" => $c], 200);
        }
    }

    public function delete_company($id)
    {
        $c = Company::find($id);
        $u = User::find($c->user_id);
        if ($c) {
            $c->forceDelete();
            $u->forceDelete();
           //$u = User::find($c->user_id)->delete();
           //$u = User::find($c->user_id)->delete();
        }
        return response(["status" => "success", 'res' => $c], 200);
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
            // 'profileType' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $company = Company::where('employee_registration_link', $link)->first();
            if ($company) {
                $total_emp = CompanyEmployee::where('company_id', $company->id)->count();
                if ($total_emp < $company->max_employees) {
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
                        $emp->profile_type_id = 1;
                        $emp->save();

                        if (!$request->password) {
                            $link = md5(uniqid());
                            $link1 = env('FRONT_URL') . '/create-password/' . $link;
                            DB::table('password_resets')->insert(['email' => $email, 'token' => $link]);


                            $maildata = array('link' => $link1, 'name' => $firstname, 'text' => 'You can use below link to create your password', 'link_text' => 'Click to create your password');
                            // Mail::to($email)->send(new ForgotPasswordEmail($maildata));

                            //$maildata = ['name' => $firstname];
                            Mail::to($email)->send(new SendEmployeeRegistrationEmail($maildata));
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

        $u = User::join('company_employees', 'users.id', 'company_employees.user_id')->withTrashed()->where('email', $request->email)->first();
        if ($u)
        {
            $c = User::join('companies', 'users.id', 'companies.user_id')->withTrashed()->where('companies.id', $u->company_id)->first();
        }
        if ($validator->fails())
        {
            return response()->json(['status' => 'error', 'message' => $validator->getMessageBag()->first()], 400);
        }
        else
        {
            if (!Auth::attempt($data))
            {
                if ($u)
                {
                    if ($u->role == "COMPANY_ADMIN" && $u->deleted_at)
                    {
                        return response()->json(['status' => 'error', 'message' => 'Access Error. Please contact Admin'], 400);
                    }
                    elseif ($u->role == "COMPANY_EMP" && $c->deleted_at)
                    {
                        return response()->json(['status' => 'error', 'message' => 'Access Error. Please contact Admin'], 400);
                    }
                    else
                    {
                        return response()->json(['status' => 'error', 'message' => 'Invalid Credentials', 'user' => $u], 400);
                    }
                }
                else
                {
                    return response()->json(['status' => 'error', 'message' => 'Invalid Credentials', 'user' => $u], 400);
                }
            }
            else
            {

                if ($u && $u->role == "COMPANY_EMP" && $c->deleted_at)
                {
                    return response()->json(['status' => 'error', 'message' => 'Access Error. Please contact Admin'], 400);
                }
                else
                {
                    $accessToken = Auth::user()->createToken('authToken')->accessToken;
                    $user = User::where('email', $request->email)->first();
                    ActivityLog::create([
                                    'log_type' => "login",
                                    'user_id' => $user->id,
                                    'login_time' => now(),
                                    'lastactivity' => null
                                ]);
                    $c = null;

                    $welcome_note = $user->last_login ? 0 : 1;

                    if ($user->role == "COMPANY")
                    {
                        $c = Company::select('companies.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.role', 'company_employees.profile_type_id', 'company_employees.profile_image')
                            ->join('company_employees', 'companies.id', 'company_employees.company_id')
                            ->where("company_employees.user_id", $user->id)
                            ->first();
                        if ($c)
                        {
                            $c->company_logo = url('/') . '/public/uploads/' . $c->company_logo;
                            $c->profile_image =  url('/') . '/public/profile-images/' . $c->profile_image;
                        }
                    }
                    $user->mobile_user = $request->mobile_user ? 1 : 0;
                    $user->last_login = DB::raw('CURRENT_TIMESTAMP');
                    $user->save();

                    $user->profile_image = url('public/profile-images/' . $user->profile_image);
                    //  addActivity("login",$user->id,[
                    //    "login_at"=>\Carbon\Carbon::now(),
                    //    "ip"=>$request->ip()
                    // ]);

                    if ($welcome_note) {
                        $link = env('FRONT_URL') . '/login';
                        $maildata = array('name' => $c->first_name, 'link' => $link);

                        Mail::to($request->email)->send(new SendEmployeePart1Email($maildata));
                    }

                    //$welcome_note = 0;
                    //return response()->json(['status' => 'error', 'user' => $user, 'company' => $c, 'welcome_note' => $welcome_note], 400);
                    return response(['user' => $user, 'company' => $c, 'welcome_note' => $welcome_note, 'access_token' => $accessToken]);
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
        // if (Auth::check())
        // {

        //     $user = Auth::user();
        //     $currentDate = now()->toDateString();
        //     $lastActivity = ActivityLog::where('user_id', $user->id)
        //                             ->whereDate('created_at', $currentDate)
        //                             ->whereDate('log_type', "login")
        //                             ->orderBy('login_time', 'desc')
        //                             ->first();

        //     $las = ActivityLog::find($lastActivity->id);
        //    // return now()->diffInMinutes($las->lastactivity);
        //     if ($las->lastactivity == null)
        //     {
        //         $las->lastactivity = now();
        //         $las->logout_time = null;
        //         $las->update();
        //         return $las;
        //     }

        //     elseif(now()->diffInMinutes($las->lastactivity) >= 3)
        //     {
        //         $las = ActivityLog::find($las->id);
        //                 $las->lastactivity = null;
        //                 $las->update();
        //        return now()->diffInMinutes($las->lastactivity);
        //     }
        //     elseif($las->lastactivity != null)
        //     {
        //         $las = ActivityLog::find($las->id);
        //         $las->lastactivity = now();
        //         $las->update();
        //         return $las;

        //     }
        // }
         if (Auth::check())
         {
                $user = Auth::user();
                $currentDate = now()->toDateString();

                $lastActivity = ActivityLog::where('user_id', $user->id)
                                        ->whereDate('created_at', $currentDate)
                                        ->whereDate('log_type', "login")
                                        ->orderBy('login_time', 'desc')
                                        ->first();

                if ($lastActivity)
                {
                    $lastActivity->update([
                        'logout_time' => now(),
                        'lastactivity' => null
                    ]);
                }

        }


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
            $maildata = array('link' => $link1, 'text' => 'You can use below link to reset your password, this link will be expired in 10 min', 'link_text' => 'Click to reset your password');
            Mail::to($email)->send(new ForgotPasswordEmail($maildata));

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

        /*$user = User::where('email', $link->email)->first();
        addActivity("reset_password",$user->id,[
               "login_at"=>\Carbon\Carbon::now(),
               "ip"=>$request->ip()
        ]);*/


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

    public function send_email1()
    {
        // Mail::send('registration-email', $data, function ($message){
        //     $message->to("deepika.manifest@gmail.com", 'MPACT INT')
        //         ->subject('Welcome to Mpact International');
        //     $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        // });


        //$link = env('FRONT_URL') . '/login';
        //$maildata = array('name' => 'Maisha', 'link' => $link);
        //Mail::to("maisha@mpact-int.com")->send(new SendEmployeePart1Email($maildata));
        //Mail::to("nchouksey@manifestinfotech.com")->send(new SendEmployeePart1Email($maildata));
        //$maildata['maildata'] = $maildata;
        //return view('emails.sendPart1Email', $maildata);

        //$link = md5(uniqid());
        //$link1 = env('FRONT_URL') . '/create-password/' . $link;
        //$maildata = array('link' => $link1, 'name' => 'first name', 'text' => 'You can use below link to create your password', 'link_text' => 'Click to create your password');
        //Mail::to("maisha@mpact-int.com")->send(new SendEmployeeRegistrationEmail($maildata));
        //Mail::to("nchouksey@manifestinfotech.com")->send(new SendEmployeePart1Email($maildata));
        //$maildata['maildata'] = $maildata;
        //return view('emails.sendEmployeeRegistrationEmail2', $maildata);

        //$maildata = array('name' => 'test company');
        //Mail::to("maisha@mpact-int.com")->send(new SendRegistrationEmail($maildata));
        //Mail::to("nchouksey@manifestinfotech.com")->send(new SendRegistrationEmail($maildata));
        //$maildata['maildata'] = $maildata;
        //return view('emails.sendRegistrationEmail', $maildata);

        $maildata = array('name' => 'test company', 'first_name' => 'first', 'last_name' => 'last', 'email' => 'test@gmail.com', 'password' => '12345678');
        Mail::to("maisha@mpact-int.com")->send(new sendCompanyRegistrationEmail($maildata));
        Mail::to("nchouksey@manifestinfotech.com")->send(new sendCompanyRegistrationEmail($maildata));
        $maildata['maildata'] = $maildata;
        return view('emails.sendCompanyRegistrationEmail', $maildata);

        //$link = md5(uniqid());
        //$link1 = env('FRONT_URL') . '/reset-password/' . $link;
        //$maildata = array('link' => $link1, 'text' => 'You can use below link to reset your password, this link will be expired in 10 min', 'link_text' => 'Click to reset your password');
        //Mail::to("maisha@mpact-int.com")->send(new ForgotPasswordEmail($maildata));
        //Mail::to("nchouksey@manifestinfotech.com")->send(new ForgotPasswordEmail($maildata));
        //Mail::to("pronobmozumder.jan@outlook.com")->send(new ForgotPasswordEmail($maildata));
        //$maildata['maildata'] = $maildata;
        //return view('emails.forgotPasswordEmail', $maildata);

    }
}
