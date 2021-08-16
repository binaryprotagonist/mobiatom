<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJourneyPlanCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journey_plan_customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('journey_plan_id');
            $table->unsignedBigInteger('journey_plan_day_id');
            $table->unsignedBigInteger('customer_id')->comment('comes from customer_infos table');
            $table->integer('day_customer_sequence')->nullable()->comment('Customers sequence number ');
            $table->string('day_start_time')->nullable();
            $table->string('day_end_time')->nullable();

            $table->foreign('journey_plan_id')->references('id')->on('journey_plans')->onDelete('cascade');
            $table->foreign('journey_plan_day_id')->references('id')->on('journey_plan_days')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customer_infos')->onDelete('cascade');
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
        Schema::dropIfExists('journey_plan_customers');
    }
}
