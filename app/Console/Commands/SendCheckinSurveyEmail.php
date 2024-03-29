<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\CheckinSurveyEmail;

class SendCheckinSurveyEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Weekly checkin survey emails to users';

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
        $users = User::where('role', 'COMPANY')->get();
        foreach ($users as $u) {
            $link = encrypt($u->id);
            $link1 = env('FRONT_URL') . '/submit-checkin-survey/' . $link;
            $maildata = array('link' => $link1, 'text' => 'You can use below link to get participate in check in survey');
            Mail::to($u->email)->send(new CheckinSurveyEmail($maildata));
        }
        $this->info('Weekly Checkin survey email has been sent successfully');

    }
}
