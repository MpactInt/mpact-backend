<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkshopRegistrationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workshop_registration', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_employee_id');
            $table->unsignedBigInteger('workshop_id');
            $table->timestamps();
            $table->foreign('company_employee_id')->references('id')->on('company_employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('workshop_id')->references('id')->on('workshops')->cascadeOnDelete()->cascadeOnUpdate();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workshop_registration');
    }
}
