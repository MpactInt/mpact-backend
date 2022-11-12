<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Tinker\TinkerCaster;

class AddFreshdeskTicketIdToCompanyQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_questions', function (Blueprint $table) {
            $table->string('freshdesk_ticket_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_questions', function (Blueprint $table) {
            $table->dropColumn('freshdesk_ticket_id');
        });
    }
}
