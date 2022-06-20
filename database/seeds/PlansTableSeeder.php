<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('plans')->insert([
            ['plan_id'=>'One-Time-Subscription-Model','plan_name'=>'One Time Subscription Model'],
           ['plan_id'=>'Recurring-Subscription-Model','plan_name'=>'Recurring Subscription Model']
        ]);
    }
}
