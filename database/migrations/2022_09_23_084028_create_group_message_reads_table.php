<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupMessageReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rec_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('message_id');
            $table->tinyInteger('seen')->default(0)->comment("0-not seen,1-seen");
            $table->foreign('rec_id')->references('id')->on('company_employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('group_id')->references('id')->on('company_chat_groups')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('message_id')->references('id')->on('group_chat_messages')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('group_message_reads');
    }
}
