<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyLearningPlanFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_learning_plan_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('my_learning_plan_id');
            $table->string('title');
            $table->text('description');
            $table->string('image');
            $table->foreign('my_learning_plan_id')->references('id')->on('my_learning_plans')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('my_learning_plan_files');
    }
}
