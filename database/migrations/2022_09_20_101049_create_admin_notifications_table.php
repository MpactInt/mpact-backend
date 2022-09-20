<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_company_id');
            $table->unsignedBigInteger('from_employee_id');
            $table->string('notification');
            $table->string('link')->nullable();
            $table->tinyInteger('seen')->default(0);
            $table->foreign('from_company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('from_employee_id')->references('id')->on('company_employees')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('admin_notifications');
    }
}
