<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOneToOneMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('one_to_one_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('rec_id');
            $table->enum('message_type',['TEXT','FILE']);
            $table->text('content');
            $table->foreign('sender_id')->references('id')->on('company_employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('rec_id')->references('id')->on('company_employees')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('one_to_one_messages');
    }
}
