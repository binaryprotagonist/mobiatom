<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanTripInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_trip_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trips_id')->nullable();
            $table->unsignedBigInteger('salesman_id');
            $table->tinyInteger('status')->default(0)->nullable()->comment('0 = salesman login, 1 = day begin, 2 = load conform, 3 = on route, 4 = unloaded, 5 = day end');
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
        Schema::dropIfExists('salesman_trip_infos');
    }
}
