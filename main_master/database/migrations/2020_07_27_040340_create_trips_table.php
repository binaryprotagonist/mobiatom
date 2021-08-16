<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('salesman_id');
            $table->dateTime('trip_start');
			$table->date('trip_start_date');
			$table->time('trip_start_time', 0);
			$table->dateTime('trip_end');
			$table->date('trip_end_date');
			$table->time('trip_end_time', 0);
			$table->unsignedBigInteger('trip_status');
			$table->unsignedBigInteger('trip_from');
			$table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
			$table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
			$table->foreign('salesman_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('trips');
    }
}
