<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToRequestWorkshopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_workshops', function (Blueprint $table) {
            $table->enum('status',['NEW','ACCEPTED','REJECTED'])->default('NEW');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_workshops', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
