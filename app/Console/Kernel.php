<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use Illuminate\Console\Command;
use App\Mail\SendGeneralPartLearningPlanEmail;
use App\Models\CompanyEmployee;
use App\Models\MyLearningPlan;
use App\Models\Company;
use App\Models\LearningPlanCompany;
use App\Models\LearningPlanProfileType;
use App\Models\MyLearningPlanFile;
use App\Models\LearningPlanResource;
use App\Models\UserPart;
use App\Models\LearningPlanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendCheckinSurveyEmail::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('weekly:email')->everyMinute();
        $schedule->command('command:part_learning_plan_cron')
                    ->dailyAt('00:30')
                    ->timezone('America/New_York'); 
        $schedule->command('command:part_learning_plan_email_crone')
                    ->dailyAt('01:00')
                    ->timezone('America/New_York'); 

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
