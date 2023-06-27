<?php

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('settings')->insert(['key'=>'admin_home_sub_title','value'=>'This section will be the opening to the learning journey test'],['key'=>'past_tip_days','value'=>'10'],['key'=>'old_tip_days','value'=>'14']);
    }
}
