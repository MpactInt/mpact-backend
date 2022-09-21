<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningPlanProfileTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_plan_profile_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_type_id');
            $table->unsignedBigInteger('learning_plan_id');
            $table->foreign('profile_type_id')->references('id')->on('profile_types')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('learning_plan_id')->references('id')->on('my_learning_plans')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_plan_profile_types');
    }
}
