<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanTiersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('plan_tiers')->truncate();
        DB::table('plan_tiers')->insert([
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'1','ending_unit'=>'1','price'=>79],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'2','ending_unit'=>'25','price'=>29],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'26','ending_unit'=>'50','price'=>25],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'51','ending_unit'=>'100','price'=>24],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'101','ending_unit'=>'200','price'=>22],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'201','ending_unit'=>'400','price'=>21],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'401','ending_unit'=>'500','price'=>20],
            ['plan_id'=>'One-Time-Users-Plan-USD-Monthly','starting_unit'=>'501','ending_unit'=>'1000000','price'=>20],
            
            ['plan_id'=>'Package-3-Premier-USD-Yearly','starting_unit'=>'1','ending_unit'=>'50','price'=>54000],
            ['plan_id'=>'Package-3-Premier-USD-Yearly','starting_unit'=>'51','ending_unit'=>'200','price'=>57000],
            ['plan_id'=>'Package-3-Premier-USD-Yearly','starting_unit'=>'201','ending_unit'=>'500','price'=>59400],
            ['plan_id'=>'Package-3-Premier-USD-Yearly','starting_unit'=>'501','ending_unit'=>'1000','price'=>61200],
            ['plan_id'=>'Package-3-Premier-USD-Yearly','starting_unit'=>'1001','ending_unit'=>'2500','price'=>72000],
            ['plan_id'=>'Package-3-Premier-USD-Yearly','starting_unit'=>'2501','ending_unit'=>'1000000','price'=>84000],
   
            ['plan_id'=>'Package-3-Premier-USD-Every-3-months','starting_unit'=>'1','ending_unit'=>'50','price'=>14850],
            ['plan_id'=>'Package-3-Premier-USD-Every-3-months','starting_unit'=>'51','ending_unit'=>'200','price'=>16388],
            ['plan_id'=>'Package-3-Premier-USD-Every-3-months','starting_unit'=>'201','ending_unit'=>'500','price'=>17078],
            ['plan_id'=>'Package-3-Premier-USD-Every-3-months','starting_unit'=>'501','ending_unit'=>'1000','price'=>17595],
            ['plan_id'=>'Package-3-Premier-USD-Every-3-months','starting_unit'=>'1001','ending_unit'=>'2500','price'=>20700],
            ['plan_id'=>'Package-3-Premier-USD-Every-3-months','starting_unit'=>'2501','ending_unit'=>'1000000','price'=>24150],

            ['plan_id'=>'Package-2-Enhanced-USD-Yearly','starting_unit'=>'1','ending_unit'=>'50','price'=>12000],
            ['plan_id'=>'Package-2-Enhanced-USD-Yearly','starting_unit'=>'51','ending_unit'=>'200','price'=>15000],
            ['plan_id'=>'Package-2-Enhanced-USD-Yearly','starting_unit'=>'201','ending_unit'=>'500','price'=>17400],
            ['plan_id'=>'Package-2-Enhanced-USD-Yearly','starting_unit'=>'501','ending_unit'=>'1000','price'=>19200],
            ['plan_id'=>'Package-2-Enhanced-USD-Yearly','starting_unit'=>'1001','ending_unit'=>'2500','price'=>30000],
            ['plan_id'=>'Package-2-Enhanced-USD-Yearly','starting_unit'=>'2501','ending_unit'=>'1000000','price'=>42000],
   
            ['plan_id'=>'Package-2-Enhanced-USD-Every-3-months','starting_unit'=>'1','ending_unit'=>'50','price'=>3300],
            ['plan_id'=>'Package-2-Enhanced-USD-Every-3-months','starting_unit'=>'51','ending_unit'=>'200','price'=>4312],
            ['plan_id'=>'Package-2-Enhanced-USD-Every-3-months','starting_unit'=>'201','ending_unit'=>'500','price'=>5002],
            ['plan_id'=>'Package-2-Enhanced-USD-Every-3-months','starting_unit'=>'501','ending_unit'=>'1000','price'=>5520],
            ['plan_id'=>'Package-2-Enhanced-USD-Every-3-months','starting_unit'=>'1001','ending_unit'=>'2500','price'=>8652],
            ['plan_id'=>'Package-2-Enhanced-USD-Every-3-months','starting_unit'=>'2501','ending_unit'=>'1000000','price'=>12075],

            ['plan_id'=>'Package-1-Basic-USD-Yearly','starting_unit'=>'1','ending_unit'=>'50','price'=>6000],
            ['plan_id'=>'Package-1-Basic-USD-Yearly','starting_unit'=>'51','ending_unit'=>'200','price'=>9000],
            ['plan_id'=>'Package-1-Basic-USD-Yearly','starting_unit'=>'201','ending_unit'=>'500','price'=>11400],
            ['plan_id'=>'Package-1-Basic-USD-Yearly','starting_unit'=>'501','ending_unit'=>'1000','price'=>13200],
            ['plan_id'=>'Package-1-Basic-USD-Yearly','starting_unit'=>'1001','ending_unit'=>'2500','price'=>24000],
            ['plan_id'=>'Package-1-Basic-USD-Yearly','starting_unit'=>'2501','ending_unit'=>'1000000','price'=>36000],
   
            ['plan_id'=>'Package-3-Basic-USD-Every-3-months','starting_unit'=>'1','ending_unit'=>'50','price'=>1650],
            ['plan_id'=>'Package-3-Basic-USD-Every-3-months','starting_unit'=>'51','ending_unit'=>'200','price'=>2588],
            ['plan_id'=>'Package-3-Basic-USD-Every-3-months','starting_unit'=>'201','ending_unit'=>'500','price'=>3278],
            ['plan_id'=>'Package-3-Basic-USD-Every-3-months','starting_unit'=>'501','ending_unit'=>'1000','price'=>3795],
            ['plan_id'=>'Package-3-Basic-USD-Every-3-months','starting_unit'=>'1001','ending_unit'=>'2500','price'=>6900],
            ['plan_id'=>'Package-3-Basic-USD-Every-3-months','starting_unit'=>'2501','ending_unit'=>'1000000','price'=>10350],


   
        ]);

    }
}
