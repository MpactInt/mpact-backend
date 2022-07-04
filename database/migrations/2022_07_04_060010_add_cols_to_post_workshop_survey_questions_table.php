<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsToPostWorkshopSurveyQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('post_workshop_survey_questions', function (Blueprint $table) {
            $table->string('min_desc')->nullable();
            $table->string('max_desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('post_workshop_survey_questions', function (Blueprint $table) {
            $table->dropColumn('min_desc');
            $table->dropColumn('max_desc');
        });
    }
}
