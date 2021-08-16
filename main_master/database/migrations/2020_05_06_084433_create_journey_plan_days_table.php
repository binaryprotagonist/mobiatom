<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJourneyPlanDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journey_plan_days', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('journey_plan_id');
            $table->unsignedBigInteger('journey_plan_week_id')->nullable()->comment('if plan type 2:week');
            $table->unsignedBigInteger('day_number')->comment('start from 1:monday, 2:tuesday..., 0-7:sunday');
            $table->string('day_name');

            $table->foreign('journey_plan_id')->references('id')->on('journey_plans')->onDelete('cascade');
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
        Schema::dropIfExists('journey_plan_days');
    }
}
