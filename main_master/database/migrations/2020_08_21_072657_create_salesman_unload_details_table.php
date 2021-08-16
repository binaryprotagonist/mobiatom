<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanUnloadDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_unload_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('salesman_unload_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom');
            $table->decimal('unload_qty', 18, 2)->default('0.00');
            $table->unsignedBigInteger('unload_type')->comment('1: fresh, 2:damage, 3:expired');
            $table->date('unload_date');
            $table->string('reason', 50);
            $table->boolean('status')->default(1);

            $table->foreign('salesman_unload_id')->references('id')->on('salesman_unloads')->onDelete('cascade');
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
        Schema::dropIfExists('salesman_unload_details');
    }
}
