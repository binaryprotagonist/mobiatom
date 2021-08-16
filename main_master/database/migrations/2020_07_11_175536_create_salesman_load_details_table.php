<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanLoadDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_load_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('salesman_load_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('salesman_id')->nullable();
            $table->unsignedBigInteger('depot_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_uom')->nullable();
            $table->date('load_date');
            $table->decimal('load_qty', 8,2);
            $table->decimal('requested_qty', 8,2);

            $table->foreign('salesman_load_id')->references('id')->on('salesman_loads')->onDelete('cascade');
            $table->foreign('route_id')->references('id')->on('routes');
            $table->foreign('depot_id')->references('id')->on('depots');
            $table->foreign('salesman_id')->references('id')->on('users');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('item_uom')->references('id')->on('item_uoms');

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
        Schema::dropIfExists('salesman_load_details');
    }
}
