<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDashboardSection2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_dashboard_section2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_type_id');
            $table->string('title');
            $table->text('description');
            $table->timestamps();
            $table->foreign('profile_type_id')->references('id')->on('profile_types')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_dashboard_section2');
    }
}
