<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Types\Nullable;

class AddLinkToLearningPlanFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_learning_plan_files', function (Blueprint $table) {
            $table->string('link')->nullable();
            // $table->string('image')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_plan_files', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }
}
