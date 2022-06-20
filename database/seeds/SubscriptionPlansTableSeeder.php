<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subscription_plans')->insert([
            ['plan_id'=>'2','subscription_plan_id'=>'25-User-License','subscription_plan_name'=>'25 User Monthly License']
        ]);
    }
}
