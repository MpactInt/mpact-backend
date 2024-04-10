<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Mail\MarkdownEmail;
use Illuminate\Support\Facades\Mail; 

class PartLearningPlanEmailCrone2 extends Command 
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sendgpl-command'; 

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
        $maildata = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            // Add more data as needed
        ];
        $this->info('Cron job started: Sending general part learning plan.');
        Mail::to('nchouksey@manifestinfotech.com')->send(new MarkdownEmail($maildata));
    
        // $user = User::where('mobile_user', '1')->first(); 
        // $this->info('Number of users to update: ' . $user->count());
        // $user->mobile_user = '2';
        // $user->update();
        // $this->info($user); 
    }
}