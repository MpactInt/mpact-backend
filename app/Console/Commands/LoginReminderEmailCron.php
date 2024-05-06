<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Mail\LoginReminderEmail;
use Illuminate\Support\Facades\Mail; 

class LoginReminderEmailCron extends Command 
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:login_reminder_email_cron'; 

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
        \Log::info("login reminder email cron");

        $sevenDaysAgo = Carbon::now()->subDays(7);

        $users = User::select('users.email','company_employees.first_name', 'company_employees.last_name')
            ->join('company_employees', 'users.id', 'company_employees.user_id')
            ->where('users.last_login','<',$sevenDaysAgo)
            ->get();
            //->toArray();

        foreach ($users as $user_data) {
            $link = env('FRONT_URL') . '/login';
            $maildata = array('name' => $user_data->first_name.' '.$user_data->last_name, 'link' => $link);
            //Mail::to($user_data->email)->send(new LoginReminderEmail($maildata));
            Mail::to("nchouksey@manifestinfotech.com")->send(new LoginReminderEmail($maildata));
            sleep(3);
        }
    }
}