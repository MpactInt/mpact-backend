<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyAnnouncement;
use App\Models\CompanyEmployee;
use App\Models\LearningPlanLog;
use App\Models\LearningPlanResource;
use App\Models\MyLearningPlan;
use App\Models\MyLearningPlanFile;
use App\Models\RequestWorkshop;
use App\Models\Resource;
use App\Models\Settings;
use App\Models\Workshop;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Consultinghours;

class DashboardController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_announcement_list($id)
    {
        $res = CompanyAnnouncement::where('company_id', $id)->limit(3)->orderby('id', 'desc')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_requested_workshop_list($id)
    {
        $res = RequestWorkshop::where("company_id", $id)->limit(3)->orderby('id', 'desc')->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_workshop_list($id)
    {
        $res = Workshop::select('workshops.*')
            ->join('company_workshops', 'company_workshops.workshop_id', 'workshops.id')
            ->where("company_id", $id)
            ->where('date', '>', time())
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_resource_list($id)
    {
        $company = Company::find($id)->first();
        if ($company) {
            if($company->role == "COMPANY_EMP"){
            $resources = Resource::select('resources.*')
                            ->join('company_resources','company_resources.resource_id','resources.id')
                            ->where("company_id", $company->id)
                            ->where("visibility","PUBLIC");
            }else{
                $resources = Resource::select('resources.*')
                    ->join('company_resources','company_resources.resource_id','resources.id')
                    ->where("company_id", $company->id);
                }
        } else {
            $resources = Resource::with(['company' => function ($q) {
                $q->join('companies', 'companies.id', 'company_resources.company_id')->pluck('companies.company_name');
            }])->select('resources.*');
        }
        $resources = $resources->limit(3)->orderBy('id', 'desc')->get();
        return response(["status" => "success", "res" => $resources], 200);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_recent_chat_list($id)
    {
        $user = Auth::guard('api')->user();
        $auth = CompanyEmployee::where('company_id', $id)->first();
        $auth_id = $auth->id;
        $id = $auth->company_id;
        $res = CompanyEmployee::with(['new_message' => function ($q) use ($auth_id) {
            $q = $q->where(['seen' => 0, 'rec_id' => $auth_id]);
        }])->select('users.last_login', 'users.email', 'company_employees.*', 'profile_types.profile_type')
            ->join('users', 'company_employees.user_id', 'users.id')
            ->join('profile_types', 'profile_types.id', 'company_employees.profile_type_id')
            ->where('company_id', $id)
            ->where('company_employees.id', '!=', $auth_id);
        $res = $res->orderby('id', 'desc')->limit(3)->get();
        return response(["status" => "success", "res" => $res], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_setting($key)
    {
        $setting = Settings::select('id', 'key', 'value')->where('key', $key)->first();
        return response(["status" => "success", "res" => $setting], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function get_settings_list(Request $request)
    {
        $sortBy = $request->sortBy;
        $keyword = $request->keyword;
        $sort_order = $request->sortOrder;
        $user = Auth::guard('api')->user();
        $settings = Settings::select('id', 'key', 'value');
        
        if ($sortBy && $sort_order) {
            $settings = $settings->orderby($sortBy, $sort_order);
        }
        $settings = $settings->paginate(10);
        return response(["status" => "success", "res" => $settings], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update_setting(Request $request)
    {
        $user = Auth::guard('api')->user();
        $setting = Settings::find($request->id);
        $setting->value = $request->value;
        $setting->save();
      
        return response(["status" => "success", "res" => $setting], 200);
    }

    public function get_mobile_users_list(Request $request)
    {
        $user = Auth::guard('api')->user();
       
        $page = $request->page;
        $name = $request->name;
        $email = $request->email;
        $sort_by = $request->sortBy;
        $sort_order = $request->sortOrder;
       
        $res = CompanyEmployee::select('users.last_login', 'users.email', 'company_employees.*','profile_types.profile_type')
            ->join('users', 'company_employees.user_id', 'users.id')
            ->join('profile_types','profile_types.id','company_employees.profile_type_id')
            ->where('users.mobile_user', 1)
            //->where('company_employees.role', '!=', 'COMPANY_ADMIN');
            //->where('company_employees.id', '!=', $auth_id)
            ;
        if ($name) {
            $res = $res->where('company_employees.first_name', 'like', "%$name%");
        }
        if ($email) {
            $res = $res->where('email', 'like', "%$email%");
        }
        if ($sort_by && $sort_order) {
            $res = $res->orderby($sort_by, $sort_order);
        }
        $res = $res->paginate(10);

        return response(["status" => "success", "res" => $res], 200);
    }

    //   public function daily_visit(Request $request)
    // {
    //     $user = Auth::guard('api')->user();
       
      
       
    //      $daily_visit =  getActivity("login", $user->id);

    //     return response(["status" => "success", "res" => $res], 200);
    // }
 
     public function getDailyVisitData(Request $request)
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id',$user->id)->first();

        $formattedData['daily_visitors_user'] = 0;
        $formattedData['categories'] = [];
        $formattedData['data'] = [];
        $tick_position = 0;

        $sql = "SELECT count(DISTINCT(activity_logs.user_id)) user_count, LEFT(DAYNAME(login_time), 3) visit_day FROM activity_logs JOIN company_employees ON activity_logs.user_id = company_employees.user_id WHERE company_employees.company_id = ".$company->id." and DATE(login_time) >= CURDATE() - INTERVAL 7 DAY group by visit_day ORDER BY activity_logs.login_time";
        $daily_visit_results = DB::select($sql);

        foreach ($daily_visit_results as $daily_visit_data) {
            $formattedData['categories'][] = $daily_visit_data->visit_day;
            $formattedData['data'][] = $daily_visit_data->user_count;
            $formattedData['daily_visitors_user'] = $daily_visit_data->user_count;

            if($tick_position < $daily_visit_data->user_count)
            {
                $tick_position = $daily_visit_data->user_count;
            }
        }
        $tick_positions = ceil($tick_position / 5);
        $formattedData['tick_positions'] = [0, $tick_positions, $tick_positions*2, $tick_positions*3, $tick_positions*4, $tick_positions*5];
        
        return response()->json($formattedData);
    }

   // public function getTotalVisitingHours(Request $request)
   //  {
   //      $activityLogs = ActivityLog::whereNotNull('login_time')->whereNotNull('logout_time')->get();

   //      $totalVisitingHours = $activityLogs->sum(function ($log) {
   //          $loginTime = \Carbon\Carbon::parse($log->login_time);
   //          $logoutTime = \Carbon\Carbon::parse($log->logout_time);
   //          return $logoutTime->diffInSeconds($loginTime);
   //      });

   //      // Format the total visiting hours
   //      $formattedHours = CarbonInterval::seconds($totalVisitingHours)->cascade()->forHumans();

   //      return response()->json([
   //          'total_visiting_hours' => $formattedHours,
   //      ]);
   //  }


    // public function getTotalVisitingHours(Request $request)
    // {
    //     $timePeriod = $request->input('time_period', 'monthly');

    //     $activityLogsQuery = ActivityLog::whereNotNull('login_time')->whereNotNull('logout_time');

    //     if ($timePeriod === 'monthly') {
    //         $activityLogsQuery->whereMonth('login_time', Carbon::now()->month);
    //     } elseif ($timePeriod === 'yearly') {
    //         $activityLogsQuery->whereYear('login_time', Carbon::now()->year);
    //     }
        
    //     $activityLogs = $activityLogsQuery->get();

    //     $totalVisitingSeconds = $activityLogs->sum(function ($log) {
    //         $loginTime = Carbon::parse($log->login_time);
    //         $logoutTime = Carbon::parse($log->logout_time);
    //         return $logoutTime->diffInSeconds($loginTime);
    //     });

    //     // Use CarbonInterval directly without additional imports
    //     $formattedHours = CarbonInterval::seconds($totalVisitingSeconds)->cascade()->forHumans();

    //     return response()->json([
    //         'total_visiting_hours' => $formattedHours,
    //     ]);
    // }

     public function getTotalVisitingHours(Request $request)
    {
       
        $timePeriod = $request->input('time_period', 'monthly');
        $allmonth = ['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sep','Oct','Nov','Dec'];
       

        // $activityLogsQuery = ActivityLog::whereNotNull('login_time')->whereNotNull('logout_time');

        if ($timePeriod === 'yearly') 
        {
            $activityLogsQuery = ActivityLog::whereYear('login_time', Carbon::now()->year);
        }
        elseif (in_array($timePeriod,$allmonth)) 
        {
            $activityLogsQuery = ActivityLog::where(DB::raw('MONTH(login_time)'), '=', array_search($timePeriod,$allmonth) + 1);
        } 
       
        $activityLogs = $activityLogsQuery->get();
        foreach($activityLogs as $activityUser)
        {
            $loginTime = Carbon::parse($activityUser->login_time);
            $logoutTime = Carbon::parse($activityUser->logout_time);
        
            // Calculate the difference between login and logout in seconds
            $differenceInHours = $logoutTime->diffInHours($loginTime);
        }
     

        $totalVisitingSeconds = $activityLogs->sum(function ($log) 
        {
            $loginTime = Carbon::parse($log->login_time);
            $logoutTime = Carbon::parse($log->logout_time);
            return $logoutTime->diffInSeconds($loginTime);
        });

        // // Calculate total visiting hours
        // $totalVisitingHours = $differenceInHours;
        

        // Calculate total visiting hours for the previous week
        $previousWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $previousWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        $previousWeekLogs = ActivityLog::where('login_time', '>=', $previousWeekStart)
            ->where('logout_time', '<=', $previousWeekEnd)
            ->get();

        $previousWeekVisitingSeconds = $previousWeekLogs->sum(function ($log) 
        {
            $loginTime = Carbon::parse($log->login_time);
            $logoutTime = Carbon::parse($log->logout_time);
            return $logoutTime->diffInSeconds($loginTime);
        });

        // Calculate total visiting hours for the previous week
        $previousWeekVisitingHours = CarbonInterval::seconds($previousWeekVisitingSeconds)->cascade()->forHumans();

        // Calculate the increase percentage compared to the previous week
        $increasePercentage = 0; // Default value if previous week's hours are zero
        if ($previousWeekVisitingSeconds > 0) 
        {
            $increasePercentage = (($totalVisitingSeconds - $previousWeekVisitingSeconds) / $previousWeekVisitingSeconds) * 100;
        }

        return response()->json([
            'total_visiting_hours' => $differenceInHours,
            'previous_week_visiting_hours' => $previousWeekVisitingHours,
            'increase_percentage' => $increasePercentage,
        ]);
    }
    
    public function getPartPercentage(Request $request)
    {
            $user = Auth::guard('api')->user();
            $user_detail = CompanyEmployee::where('user_id', $user->id)->first();

            $total_learning_plan_id = MyLearningPlan::join('learning_plan_profile_types', 'my_learning_plans.id', 'learning_plan_profile_types.learning_plan_id')
                ->join('learning_plan_companies', 'my_learning_plans.id', '=', 'learning_plan_companies.learning_plan_id')
                ->where('learning_plan_profile_types.profile_type_id', $user_detail->profile_type_id)
                ->where('learning_plan_companies.company_id', $user_detail->company_id)
                ->where('my_learning_plans.part', $request->part)
                ->select('my_learning_plans.*')
                ->pluck('my_learning_plans.id');

            $resource_id = LearningPlanResource::whereIn('learning_plan_id', $total_learning_plan_id)->pluck('resource_id')->toArray();
            $total_plan_file = MyLearningPlanFile::whereIn('id', $resource_id)->count();

            $total_learning_view_count = LearningPlanLog::where('profile_type_id', $user_detail->profile_type_id)
                ->where('company_id', $user_detail->company_id)
                ->where('profile_id', $user_detail->id)
                ->where('part', $request->part)
                ->count();

            $viewPercentage = 0; // Default value

            if ($total_plan_file > 0) {
                $viewPercentage = ($total_learning_view_count / $total_plan_file) * 100;
            }


           // Calculate total percent
            $totalPercent = LearningPlanLog::where('profile_type_id', $user_detail->profile_type_id)
                ->where('company_id', $user_detail->company_id)
                ->where('part', $request->part)
                ->count(); // Assuming 'completed' signifies completion
            
            $totalUsers = LearningPlanLog::where('profile_type_id', $user_detail->profile_type_id)
                ->where('company_id', $user_detail->company_id)
                ->where('part', $request->part)
                ->distinct()->count('profile_id');

                if ($totalPercent) {
                    // code...
                  $totalPercent = ($totalPercent / $totalUsers) * 100;
                }else{
                  $totalPercent = 0;

                }

            // Calculate average percent
            if ($totalPercent) {
               $averagePercent = $totalPercent / $totalUsers;
            }else{
               $averagePercent = 0;
            }


            // Calculate personal percent for user 4
            $personalPercent = LearningPlanLog::where('profile_type_id', $user_detail->profile_type_id)
                ->where('company_id', $user_detail->company_id)
                ->where('profile_id', $user_detail->id)
                ->where('part', $request->part)
                ->count(); 
                // Change 'user_id' and 'completed' to match your columns
           if ($personalPercent) {
            $personalPercent = ($personalPercent / $totalUsers) * 100;
           }else{
            $personalPercent = 0;
           }
               if ($personalPercent) {
                    // Calculate overall percent for user 4
                    $overallPercentUser = ($personalPercent * 100) / $averagePercent;
               }else{
                $overallPercentUser=0;
               }


            return response()->json([
                'part_percentage' => $viewPercentage,
                'overallPercentUser' => $overallPercentUser,
            ]);
        }

        public function getAdminPartPercentage($id)
        {
            $user = Auth::guard('api')->user();
            $user_detail = CompanyEmployee::where('user_id', $user->id)->first();
             // Calculate total percent
             $totalPercent = LearningPlanLog::where('part', $id)
             ->count(); // Assuming 'completed' signifies completion
         
            $totalUsers = LearningPlanLog::where('part', $id)
                ->distinct()->count('profile_id');

                if ($totalPercent) 
                {
                    $totalPercent = ($totalPercent / $totalUsers) * 100;
                }
                else
                {
                    $totalPercent = 0;
                }
                 // Calculate average percent
                if ($totalPercent) 
                {
                    $averagePercent = $totalPercent / ($totalUsers * 100);
                }
                else
                {
                    $averagePercent = 0;
                }
                return response()->json([
                    'admin_part_percentage' => $averagePercent,
                ]);
        }

      public function getAdminConsultingHours($month)
     {
        $user = Auth::guard('api')->user();
        $c = Company::where('user_id', $user->id)->first();
        $allmonth = ['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sep','Oct','Nov','Dec'];
        $totalHours = 0;
        if (in_array($month,$allmonth)) 
        {
            $activityLogsQuery = Consultinghours::where(DB::raw('MONTH(created_at)'), '=', array_search($month,$allmonth) + 1)
                ->where('company_id', $c->id)
                ->get();
            foreach($activityLogsQuery as $ch)
            {
                // Add the hours to the total
                $totalHours += $ch->consulting_hour;
                
            }
        } 
    //    // $results = Consultinghours::where('user_id',Auth::user()->id)->where('consulting_hour',)
    //     $results = User::withTrashed()
    //     ->join('companies', 'companies.user_id', 'users.id')
    //     ->join('company_employees', 'companies.id', 'company_employees.company_id')
    //     ->where('company_employees.role', 'COMPANY_ADMIN')
    //     ->select(
    //         DB::raw('SUM(companies.total_hours) as total_hours_sum'),
    //         DB::raw('SUM(companies.remaining_hours) as remaining_hours_sum'))
    //     ->first();

    //     // Extract the total_hours_sum and remaining_hours_sum from the result
    //     $totalHoursSum = $results->total_hours_sum;
    //     $remainingHoursSum = $results->remaining_hours_sum;

    //     // Calculate the difference
    //     $th = $totalHoursSum - $remainingHoursSum;

    //     //     if($id== 'Jan-Mar')
    //     //     {
    //     //         $chartData =[16, 96, 96, 30];
               
    //     //     }
    //     //     if($id == 'Apr-Jun')
    //     //     {
    //     //         $chartData =[96, 16, 96, 30];
    //     //     }
    //     //     if($id == 'Jul-Sep')
    //     //     {
    //     //         $chartData =[26, 56, 16, 30];
    //     //     }
    //     //     if($id == 'Oct-Dec')
    //     //     {
    //     //         $chartData =[76, 16, 76, 30];
    //     //     }
        
        return response()->json([
            'admin_consulting_hours' => $totalHours,
            // 'admin_consulting_data' => $chartData
        ]);
     }


}
