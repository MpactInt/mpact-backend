<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoomMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workshop_id');
            $table->string('meeting_id');
            $table->string('topic');
            $table->text('agenda');
            $table->tinyInteger('type')->comment('MEETING_TYPE_INSTANT = 1,MEETING_TYPE_SCHEDULE = 2,MEETING_TYPE_RECURRING = 3,MEETING_TYPE_FIXED_RECURRING_FIXED = 8');
            $table->text('start_url');
            $table->string('join_url');
            $table->string('start_time');
            $table->string('status');
            $table->string('duration');
            $table->string('passcode');
            $table->timestamps();
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
        Schema::dropIfExists('zoom_meetings');
    }
}
