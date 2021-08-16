<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanLoadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_loads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->string('load_number');
            $table->unsignedBigInteger('depot_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('salesman_id');
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->date('load_date');
            $table->unsignedBigInteger('load_type')->nullable()->comment('1: Delivery Load 2: Van Load');
            $table->boolean('load_confirm')->default(1)->comment('Default load_comfirm 0, 0 means Pending 1 Confirm');
            $table->boolean('status')->default(1);

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('depot_id')->references('id')->on('depots');
            $table->foreign('route_id')->references('id')->on('routes');
            $table->foreign('salesman_id')->references('id')->on('users');
            
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
        Schema::dropIfExists('salesman_loads');
    }
}
