<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyEmployee;
use App\Models\MyLearningPlan;
use App\Models\Company;
use App\Models\LearningPlanCompany;
use App\Models\LearningPlanProfileType;
use App\Models\MyLearningPlanFile;
use App\Models\LearningPlanResource;
use App\Models\LearningPlanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use DB;

class PartLearningPlanCrone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:part_learning_plan_crone';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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
}
