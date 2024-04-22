<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\SendGeneralPartLearningPlanEmail;
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
use view;
class PartLearningPlanEmailCrone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:part_learning_plan_email_crone';

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

\Log::info("general plan cron");
      // Set variables for email composition
$to = "nchouksey@manifestinfotech.com";
$subject = "Test Email";
$message = "This is a test email to $to";
$message .= "<b>This is a test email to $to</b>";
$message .= "<i><u>this is next html line fro underline and italic</u></i>";
$headers = "From: noreply@manifestinfotech.com\r\n";
$headers .= "Reply-To: sender@manifestinfotech.com\r\n";
$headers .= "Content-Type: text/html; charset=utf-8\r\n";



$link = env('FRONT_URL') . '/employee/my-learning-plan/';
        $maildata = array('name' => 'test name', 'link' => $link, 'title' => 'title', 'date' => 'date', 'email_subject' => 'email_subject', 'email_body' => 'email_body');
$maildata['maildata'] = $maildata;

$view = View::make('emails.SendGeneralPartLearningPlanEmail');
foreach ($maildata as $key => $value) {
    $view->with($key, $value);
}
$contents = $view->render();





// Send the email using mail() function
if (mail($to, $subject, $contents, $headers)) {
    //echo "Email sent successfully.";
    \Log::info("Email sent successfully to $to");
} else {
    //echo "Email delivery failed.";
    \Log::info("Email delivery failed");
}
        
      
        // $learning_plans_today = MyLearningPlan::select('my_learning_plans.id', 'my_learning_plans.title', 'my_learning_plans.email_subject', 'my_learning_plans.email_body', 'users.email','company_employees.first_name','company_employees.last_name', 'user_learning_plans.learning_plan_enable_date',)
        //     ->join('user_learning_plans', 'my_learning_plans.id', 'user_learning_plans.learning_plan_id')
        //     ->join('users', 'users.id', 'user_learning_plans.user_id')
        //     ->join('company_employees', 'user_learning_plans.user_id', 'company_employees.user_id')
        //     ->where('my_learning_plans.part', 'general')
        //     ->where('user_learning_plans.learning_plan_enable_date', now()->toDateString())
        //     ->get();
           
           
        // foreach ($learning_plans_today as $learning_plan) {
        //     //echo '<pre>';print_r($learning_plan->email);exit;
        //     $link = env('FRONT_URL') . '/employee/my-learning-plan/'.$learning_plan->id;
        //     $maildata = array('name' => $learning_plan->first_name.' '.$learning_plan->last_name, 'link' => $link, 'title' => $learning_plan->title, 'date' => $learning_plan->learning_plan_enable_date, 'email_subject' => $learning_plan->email_subject, 'email_body' => $learning_plan->email_body);
        //     //echo '<pre>';print_r($maildata);//exit;
        //     try {
        //         Mail::to($learning_plan->email)->send(new SendGeneralPartLearningPlanEmail($maildata));
        //     } catch (\Exception $e) {
        //     }
           
        // }
    }
}
