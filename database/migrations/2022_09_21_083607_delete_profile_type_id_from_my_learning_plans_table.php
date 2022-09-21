<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteProfileTypeIdFromMyLearningPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_learning_plans', function (Blueprint $table) {
            $table->dropForeign(['profile_type_id']);
            $table->dropColumn('profile_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('my_learning_plans', function (Blueprint $table) {
            //
        });
    }
}
