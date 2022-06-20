<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDashboardSection3ImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_dashboard_section3_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('section3_id');
            $table->string('image');
            $table->timestamps();
            $table->foreign('section3_id')->references('id')->on('employee_dashboard_section3')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_dashboard_section3_images');
    }
}
