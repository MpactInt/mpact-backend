<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoPathToMyLearningPlanFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_learning_plan_files', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()  
    {
        Schema::table('my_learning_plan_files', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
    }
}
