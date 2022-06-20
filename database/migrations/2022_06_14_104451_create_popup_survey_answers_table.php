<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePopupSurveyAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('popup_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_employee_id');
            $table->unsignedBigInteger('question_id');
            $table->string('answer');
            $table->timestamps();
            $table->foreign('company_employee_id')->references('id')->on('company_employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('question_id')->references('id')->on('popup_survey_questions')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('popup_survey_answers');
    }
}
