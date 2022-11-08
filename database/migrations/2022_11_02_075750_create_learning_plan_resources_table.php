<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningPlanResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_plan_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_plan_id');
            $table->unsignedBigInteger('resource_id');
            $table->foreign('learning_plan_id')->references('id')->on('my_learning_plans')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('resource_id')->references('id')->on('my_learning_plan_files')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('learning_plan_resources');
    }
}
