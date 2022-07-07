<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyLearningPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_learning_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_type_id');
            $table->string('title');
            $table->text('description');
            $table->string('image');
            $table->foreign('profile_type_id')->references('id')->on('profile_types')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('my_learning_plans');
    }
}
