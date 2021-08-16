<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_visits', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('route_id')->nullable();
			$table->unsignedBigInteger('customer_id');
			$table->unsignedBigInteger('salesman_id');
			$table->unsignedBigInteger('journey_plan_id');
			$table->unsignedBigInteger('trip_id')->nullable();
			$table->string('latitude');
			$table->string('longitude');
            $table->string('shop_status');
            $table->string('visit_total_time');
            $table->string('reason')->nullable();
            $table->string('comment')->nullable();
			$table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
			$table->enum('is_sequnece', [1,0])->default(0);
            $table->date('date')->nullable();
            $table->dateTime('added_on');

            $table->integer('total_task');
            $table->integer('completed_task');

            $table->boolean('status')->default(1);

			$table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
			$table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
			$table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
			$table->foreign('salesman_id')->references('id')->on('users');
			$table->foreign('journey_plan_id')->references('id')->on('journey_plans');

            $table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_visits');
    }
}
