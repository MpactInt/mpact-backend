<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToMyLearningPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_learning_plans', function (Blueprint $table) {
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
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
            $table->dropColumn('email_subject');
            $table->dropColumn('email_body');
        });
    }
}
