<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SendGeneralPartLearningPlanEmail;
use App\Mail\sendPartActivationEmail;
use App\Models\CompanyEmployee;
use App\Models\MyLearningPlan;
use App\Models\Company;
use App\Models\LearningPlanCompany;
use App\Models\LearningPlanProfileType;
use App\Models\MyLearningPlanFile;
use App\Models\LearningPlanResource;
use App\Models\UserPart;
use App\Models\UserLearningPlan;
use App\Models\LearningPlanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use DB;

class LearningPlanController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function add_learning_plan(Request $request)
    {
        //return response(["status" => "success", "res" => json_decode($request->company)], 400);
        $validator = Validator::make($request->all(), [
            'image' => 'required|image'
        ]);
        if ($validator->fails()) {
            $error = $validator->getMessageBag()->first();
            return response()->json(["status" => "error", "message" => $error], 400);
        } else {
            $filename = '';
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName(); 
                $destinationPath = public_path() . '/learning-plan-files';
                $uploadedFile->move($destinationPath, $filename);
            }

            $t = new MyLearningPlan();
            $t->title = $request->title;
            $t->description = $request->description;
            $t->part = $request->part;
            $t->email_subject = $request->email_subject;
            $t->email_body = $request->email_body;
            $t->image = $filename;
            $t->save();

            if ($request->company) {
                $company = json_decode($request->company);
                foreach ($company as $c) {
                    $lpc = new LearningPlanCompany();
                    $lpc->company_id = $c->id;
                    $lpc->learning_plan_id = $t->id;
                    $lpc->save();
                }
            }

            if ($request->profile_type) {
                $pt = json_decode($request->profile_type);
                foreach ($pt as $p) {
                    $lppt = new LearningPlanProfileType();
                    $lppt->profile_type_id = $p->id;
                    $lppt->learning_plan_id = $t->id;
                    $lppt->order = $p->order;

                    $lppt->save();
                }
            }
            if ($request->resources) {
                $pt = json_decode($request->resources);
                foreach ($pt as $p) {
                    $lppt = new LearningPlanResource();
                    $lppt->resource_id = $p->id;
                    $lppt->learning_plan_id = $t->id;

                    $lppt->save();
                }
            }
            return response(["status" => "success", "res" => $t], 200);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_learning_plan(Request $request)
    {
        $t = MyLearningPlan::find($request->id);
        $filename = '';
        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image'
            ]);
            if ($validator->fails()) {
                $error = $validator->getMessageBag()->first();
                return response()->json(["status" => "error", "message" => $error], 400);
            } else {
                $uploadedFile = $request->file('image');
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $destinationPath = public_path() . '/learning-plan-files';
                $uploadedFile->move($destinationPath, $filename);
                if ($t->image) {
                    if (file_exists($destinationPath . '/' . $t->image)) {
                        unlink($destinationPath . '/' . $t->image);
                    }
                }
                $t->image = $filename;
            }
        }
        $t->title = $request->title;
        $t->description = $request->description;
        $t->part = $request->part;
        $t->email_subject = $request->email_subject;
        $t->email_body = $request->email_body;
        $t->save();

        if ($request->company) {
            $company = json_decode($request->company);
            LearningPlanCompany::where('learning_plan_id', $t->id)->delete();
            foreach ($company as $c) {
                $lpc = new LearningPlanCompany();
                $lpc->company_id = $c->id;
                $lpc->learning_plan_id = $t->id;
                $lpc->save();
            }
        }

        if ($request->profile_type) {
            $pt = json_decode($request->profile_type);
            LearningPlanProfileType::where('learning_plan_id', $t->id)->delete();
            foreach ($pt as $p) {
                $lppt = new LearningPlanProfileType();
                $lppt->profile_type_id = $p->id;
                $lppt->learning_plan_id = $t->id;
                $lppt->order = $p->order;

                $lppt->save();
            }
        }

        if ($request->resources) {
            $pt = json_decode($request->resources);
            LearningPlanResource::where('learning_plan_id', $t->id)->delete();
            foreach ($pt as $p) {
                $lppt = new LearningPlanResource();
                $lppt->resource_id = $p->id;
                $lppt->learning_plan_id = $t->id;
                $lppt->save();
            }
        }
        return response(["status" => "success", "res" => $t], 200);
    }

    public function get_company_list_multiselect_update()
    {
        $res = Company::select('id', 'company_name as name')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan($id)
    {
        $ca = MyLearningPlan::where('my_learning_plans.id', $id)->first();

        $ca->company = LearningPlanCompany::join('companies', 'companies.id', 'learning_plan_companies.company_id')
            ->select('companies.id', 'companies.company_name as name')
            ->where('learning_plan_id', $id)
            ->get();

        $ca->profile_type = LearningPlanProfileType::join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')
            ->select('profile_types.id', 'profile_types.profile_type as name','order')
            ->where('learning_plan_id', $id)
            ->get();

        // $ca->files = LearningPlanResource::join('my_learning_plan_files', 'my_learning_plan_files.id', 'learning_plan_resources.resource_id')
        //     ->select('my_learning_plan_files.*', 'my_learning_plan_files.title as name')
        //     ->where('learning_plan_id', $id)
        //     ->get();

            $ca->files = LearningPlanResource::join('my_learning_plan_files', 'my_learning_plan_files.id', 'learning_plan_resources.resource_id')
                        ->join('my_learning_plans', 'my_learning_plans.id', 'learning_plan_resources.learning_plan_id')
                        ->select('my_learning_plan_files.*', 'my_learning_plan_files.title as name')
                        ->where('learning_plan_id', $id)
                        ->where('my_learning_plans.part', $ca->part)
                        ->get();

        $path = url('/public/learning-plan-files/');
        $ca->image = $path . '/' . $ca->image;
        $vdo_path = url('/public/videos/');
        return response(["status" => "success", "res" => $ca, 'path' => $path,'vdo_path' => $vdo_path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_learning_plan_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $path = url('/public/learning-plan-files/');
        $user = Auth::guard('api')->user();
        $company = CompanyEmployee::where('user_id', $user->id)->first();
        $ca = MyLearningPlan::with(['profileType' => function ($q) {
            $q->join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')->pluck('profile_types.profile_type');
        }])->select('*');
        // $ca = $ca->join("profile_types", 'profile_types.id', 'my_learning_plans.profile_type_id')
        //     ->select('my_learning_plans.*', 'profile_types.profile_type');
        // if($company){
        //     $ca = $ca->where('profile_types.id',$company->profile_type_id);
        // }

        if ($keyword) {
            $ca = $ca->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $ca = $ca->orderby($sort_by, $sort_order);
        }


        $ca = $ca->paginate(10);
        return response(["status" => "success", "res" => $ca, "path" => $path], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_emp_learning_plan_list(Request $request)
    {
        $keyword = $request->keyword;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
        $path = url('/public/learning-plan-files/');
        $user = Auth::guard('api')->user();
        $user_detail = CompanyEmployee::where('user_id', $user->id)->first();

        // $ca = MyLearningPlan::with(['profileType' => function ($q) {
        //     $q->join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')->pluck('profile_types.profile_type');
        // }])->select('*');



            $user = Auth::guard('api')->user();
          $user_detail = CompanyEmployee::where('user_id', $user->id)->first();

            $parts = ['',"part1","part2","part3",'part4'];
            $get_part = ["part1",];
            $i = 1;

            foreach ($parts as $key => $part) {
                    if ($part == "") {
                        continue;
                    }
                   $i++;
                    $total_learning_plan_id = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
                        ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
                        ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
                        ->where('learning_plan_companies.company_id', $user_detail->company_id)
                        ->where('my_learning_plans.part', $part)
                        ->select('my_learning_plans.*')
                        ->pluck("my_learning_plans.id");

                    $resource_id =   LearningPlanResource::whereIn("learning_plan_id", $total_learning_plan_id)->pluck("resource_id")->toArray();
                    $total_plan_file =   MyLearningPlanFile::whereIn("id", $resource_id)->count();

                    $total_learning_view_count = LearningPlanLog::where('profile_type_id', $user_detail->profile_type_id)
                                                ->where('company_id', $user_detail->company_id)
                                                ->where('profile_id', $user_detail->id)
                                                ->select('learning_plan_logs.*')
                                                 ->where('learning_plan_logs.part', $part)
                                                ->count();

                            $sixtyPercent = 0.6 * $total_plan_file;
                            if ($total_learning_view_count >= $sixtyPercent) {
                                if ($i == 5) {
                                    // code...
                                  array_push($get_part , "general");
                                }else{
                                  array_push($get_part , "part".$i);
                                }
                            }
            }





        $ca = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
            ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
            ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
            ->where('learning_plan_companies.company_id', $user_detail->company_id)
            ->whereIn('part', $get_part)
            ->select('my_learning_plans.*');

        // $ca = $ca->join("profile_types", 'profile_types.id', 'my_learning_plans.profile_type_id')
        //     ->select('my_learning_plans.*', 'profile_types.profile_type');
        // if($company){
        //     $ca = $ca->where('profile_types.id',$company->profile_type_id);
        // }

        if ($keyword) {
            $ca = $ca->where('title', 'like', "%$keyword%")
                ->orWhere('description', 'like', "%$keyword%");
        }
        if ($sort_by && $sort_order) {
            $ca = $ca->orderby($sort_by, $sort_order);
        }


        $ca = $ca->paginate(10);
        return response(["status" => "success", "res" => $ca, "path" => $path,'get_part'=>$get_part], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    // public function get_learning_plan_list_dashboard(Request $request)
    // {
    //     $path = url('/public/learning-plan-files/');
    //     $user = Auth::guard('api')->user();
    //     $user_detail = CompanyEmployee::where('user_id', $user->id)->first();

    //     // $ca = MyLearningPlan::with('files');
    //     // $ca = $ca->join("profile_types", 'profile_types.id', 'my_learning_plans.profile_type_id')
    //     //     ->select('my_learning_plans.*', 'profile_types.profile_type')
    //     //     ->where('profile_types.id',$company->profile_type_id)
    //     //     ->limit(6)
    //     //     ->get();

    //     $ca = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
    //         ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
    //         ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
    //         ->where('learning_plan_companies.company_id', $user_detail->company_id)
    //         ->where('my_learning_plans.part', $request->part)
    //         ->select('my_learning_plans.*')
    //         ->limit(6)->get();


    //     /*$ca = MyLearningPlan::with(['files', 'profileType' => function ($q) use ($company) {
    //         $q->join('profile_types', 'profile_types.id', 'learning_plan_profile_types.profile_type_id')
    //             ->where('profile_types.id', $company->profile_type_id)
    //             ->pluck('profile_types.profile_type');
    //     }])->select('*')->limit(6)->get();*/
    //     return response(["status" => "success", "res" => $ca, "path" => $path], 200);
    // }



 /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
public function get_learning_plan_list_dashboard(Request $request)
{
    $path = url('/public/learning-plan-files/');
    $user = Auth::guard('api')->user();
    $user_detail = CompanyEmployee::where('user_id', $user->id)->first();

    /*if ($request->part == 'general') {
        $learning_plans = MyLearningPlan::select('my_learning_plans.*')
            ->join('user_learning_plans', 'my_learning_plans.id', 'user_learning_plans.learning_plan_id')
            ->where('user_learning_plans.user_id', $user->id)
            ->where('user_learning_plans.learning_plan_enable_date', '<=', now()->toDateString())
            ->get();
        
    }else
    {*/
        $learning_plans = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
            ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
            ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
            ->where('learning_plan_companies.company_id', $user_detail->company_id)
            ->where('my_learning_plans.part', $request->part)
            ->select('my_learning_plans.*')
            ->limit(6)
            ->get();
    /*}*/
    

    // $partCounts = [];
    // $partNames = ["part1", "part2", "part3", "part4", "general"]; // Add more parts if needed

    // foreach ($partNames as $part) {

    //     $totalPartCount = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
    //         ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
    //         ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
    //         ->where('learning_plan_companies.company_id', $user_detail->company_id)
    //         ->where('my_learning_plans.part', $part)
    //         ->select('my_learning_plans.*')
    //         ->count();


    //    $totalPartCount = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
    //         ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
    //         ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
    //         ->where('learning_plan_companies.company_id', $user_detail->company_id)
    //         ->where('my_learning_plans.part', $part)
    //         ->select('my_learning_plans.*')
    //         ->whereHas(["isView"=>function($q){
    //                 ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
    //                 ->where('learning_plan_companies.company_id', $user_detail->company_id);
    //         }])
    //         ->count();
    //     $partCounts["total_$part"] = $totalPartCount;
    //     $partCounts["total_viewed_$part"] = $totalPartCount;
    // }

    return response(["status" => "success", "res" => $learning_plans, "path" => $path], 200);
}

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function general_part_learning_plan_crone(Request $request)
    {
        $currentDate = date("Y-m-d");
        $users = Company::select('company_employees.user_id', 'companies.id as company_id', 'company_employees.profile_type_id', 'companies.duration', 'companies.learning_plan_start_date')
                    ->join('company_employees', 'company_employees.company_id', '=', 'companies.id')
                    ->get()
                    ->toArray();
        //echo '<pre>';print_r($users);
        foreach($users as $user)
        {
            $existing_learning_plans = DB::table('user_learning_plans')
                                        ->select('learning_plan_id')
                                        ->where('user_id', $user['user_id']);
                                        //->get()
                                        //->toArray();
            //echo '<pre>';print_r($existing_learning_plans);exit;

            $new_learning_plans = LearningPlanProfileType::where('profile_type_id', $user['profile_type_id'])
                ->whereNotIn('learning_plan_id', $existing_learning_plans)
                ->get()
                ->toArray();
            //echo '<pre>';print_r($new_learning_plans);exit;
            foreach ($new_learning_plans as $new_learning_plan) {
                $temp_duration = $user['duration'] * ($new_learning_plan['order'] - 1);
                $learning_plan_enable_date = date('Y-m-d', strtotime($user['learning_plan_start_date']. "+ $temp_duration days"));
              
                $data = [
                    'learning_plan_id' => $new_learning_plan['learning_plan_id'],
                    'user_id' => $user['user_id'],
                    'learning_plan_enable_date' => $learning_plan_enable_date
                ];

                DB::table('user_learning_plans')->insert($data);
            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function general_part_learning_plan_email_crone(Request $request)
    {
            $t = UserLearningPlan::find(1);
            $t->email_sent = 1;
            $t->save();

        $learning_plans_today = MyLearningPlan::select('my_learning_plans.id', 'my_learning_plans.title', 'my_learning_plans.email_subject', 'my_learning_plans.email_body', 'users.email','company_employees.first_name','company_employees.last_name', 'user_learning_plans.learning_plan_enable_date', 'user_learning_plans.email_sent')
            ->join('user_learning_plans', 'my_learning_plans.id', 'user_learning_plans.learning_plan_id')
            ->join('users', 'users.id', 'user_learning_plans.user_id')
            ->join('company_employees', 'user_learning_plans.user_id', 'company_employees.user_id')
            ->where('my_learning_plans.part', 'general')
            ->where('user_learning_plans.learning_plan_enable_date', '<=', now()->toDateString())
            ->where('user_learning_plans.learning_plan_enable_date', '>', '2024-01-01')
            ->where('user_learning_plans.email_sent', '0')
            ->limit(20)
            ->get()
            ->toArray();
            echo '<pre>';print_r($learning_plans_today);exit;

        //$link = env('FRONT_URL') . '/employee/my-learning-plan/24';
        //$maildata = array('name' => 'Neel Chouksey', 'link' => $link, 'title' => 'Speaking Up: Part 1', 'date' => '12-28-2023', 'email_subject' => 'This is email subject from Neel', 'email_body' => 'This is email body by Maisha');
        //Mail::to("maisha@mpact-int.com")->send(new SendGeneralPartLearningPlanEmail($maildata));
        //Mail::to("nchouksey@manifestinfotech.com")->send(new SendGeneralPartLearningPlanEmail($maildata));
        //$maildata['maildata'] = $maildata;
        //return view('emails.SendGeneralPartLearningPlanEmail', $maildata);
           
        foreach ($learning_plans_today as $learning_plan) {
            //echo '<pre>';print_r($learning_plan->email);exit;
            $link = env('FRONT_URL') . '/employee/my-learning-plan/'.$learning_plan->id;
            $maildata = array('name' => $learning_plan->first_name.' '.$learning_plan->last_name, 'link' => $link, 'title' => $learning_plan->title, 'date' => $learning_plan->learning_plan_enable_date, 'email_subject' => $learning_plan->email_subject, 'email_body' => $learning_plan->email_body);
            //echo '<pre>';print_r($maildata);//exit;
            try {
                Mail::to($learning_plan->email)->send(new SendGeneralPartLearningPlanEmail($maildata));
            } catch (\Exception $e) {
            }
            //$maildata['maildata'] = $maildata;
            //return view('emails.SendGeneralPartLearningPlanEmail', $maildata);exit;
        }
    }

    public function shouldGoNextTab(Request $request)
    {

          $user = Auth::guard('api')->user();
          $user_detail = CompanyEmployee::where('user_id', $user->id)->first();


        $total_learning_plan_id = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
            ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
            ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
            ->where('learning_plan_companies.company_id', $user_detail->company_id)
            ->where('my_learning_plans.part', $request->part)
            ->select('my_learning_plans.*')
            ->pluck("my_learning_plans.id");

        $resource_id =   LearningPlanResource::whereIn("learning_plan_id", $total_learning_plan_id)->pluck("resource_id")->toArray();
        $total_plan_file =   MyLearningPlanFile::whereIn("id", $resource_id)->count();


        $total_learning_view_count = LearningPlanLog::where('profile_type_id', $user_detail->profile_type_id)
                                            ->where('company_id', $user_detail->company_id)
                                            ->where('profile_id', $user_detail->id)
                                            ->select('learning_plan_logs.*')
                                             ->where('learning_plan_logs.part', $request->part)
                                            ->count();

            $sixtyPercent = 0.6 * $total_plan_file;

            $viewPercentage = 0; // Default value

            if ($total_plan_file > 0) {
                $viewPercentage = ($total_learning_view_count / $total_plan_file) * 100;
            }

            if ($total_learning_view_count >= $sixtyPercent) {
                // echo "More than 60% of the videos have been viewed.";
                return [
                    "result"=>true,
                    "totalLearningCount"=>$total_plan_file,
                    "totalLearningViewCount"=>$total_learning_view_count,
                    "sixtyPercent"=>$sixtyPercent,
                    "viewPercentage"=>round($viewPercentage),
                ];
            } else {
                return [
                    "result"=>false,
                    "totalLearningCount"=>$total_plan_file,
                    "totalLearningViewCount"=>$total_learning_view_count,
                    "sixtyPercent"=>$sixtyPercent,
                    "viewPercentage"=>round($viewPercentage),
                ];
            }
    }
    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete_learning_plan($id)
    {
        $ca = MyLearningPlan::find($id);
        $destinationPath = public_path() . '/learning-plan-files';
        if ($ca->image) {
            unlink($destinationPath . '/' . $ca->image);
        }
        $ca->delete();

        LearningPlanResource::where('learning_plan_id', $id)->delete();
        //$files = MyLearningPlanFile::where('my_learning_plan_id', $id);
        //foreach ($files as $f) {
            //unlink($destinationPath . '/' . $f->file);
        //}
        //$files->delete();
        return response(["status" => "success", "res" => $ca], 200);
    }

    public function update_learning_plan_view(Request $request){

        //return $lastChar = substr($request->part, -1);

        $data = $request->only(['company_id', 'part', 'type', 'profile_id','plan_id']);
        $user = Auth::guard('api')->user();
        $user_detail = CompanyEmployee::where('user_id', $user->id)->first();
        $part_no = substr($request->part, -1);

        $data = [
                'plan_id' => $data['plan_id'],
                'company_id' => $user_detail->company_id,
                'type' => $data['type'],
                'profile_id' => $user_detail->id,
                'profile_type_id' => $user_detail->profile_type_id,
                'part' => $data['part'] ?? '',
            ];

        // Check if the combination of company_id, part, type, and profile_id already exists
        $existingRecord = DB::table('learning_plan_logs')
            ->where($data)->first();

        $part_detail = array();
        if (!$existingRecord) {

            DB::table('learning_plan_logs')->insert($data);

            if ($request->part != 'general') {
                $part_detail = $this->shouldGoNextTab($request);

                $user_part = UserPart::where('user_id', $user->id)
                    ->where('part', $request->part)
                    ->first();
                $user_part->percent = $part_detail['viewPercentage'];

                if ($user_part->email_sent == 0 && $part_detail['viewPercentage'] >= 60) {
                    // sending welcome email for next part
                    $link = env('FRONT_URL') . '/login';
                    $maildata = array('name' => $user_detail->first_name.' '.$user_detail->last_name, 'link' => $link, 'part' => $part_no+1);
                    try {
                        Mail::to($user->email)->send(new sendPartActivationEmail($maildata));
                        $user_part->email_sent = 1;
                    } catch (\Exception $e) {
                    }
                }

                $user_part->save();
            }
        }

        return response(["status" => "success", "result" => $part_detail], 200);
    }




}
