<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendCheckinSurveyEmail::class,
        Commands\PartLearningPlanEmailCrone2::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:sendgpl-command')->everyMinute();

        // $schedule->command('weekly:email')->everyMinute();
        $schedule->command('command:part_learning_plan_cron')
                    ->dailyAt('00:30')
                    ->timezone('America/New_York'); 
        $schedule->command('command:part_learning_plan_email_crone')->everyMinute();
                    //->dailyAt('01:00')
                    //->timezone('America/New_York'); 

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
