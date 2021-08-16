<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockAdjustmentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_adjustment_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('stock_adjustment_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id')->nullable();
            $table->decimal('available_qty', 18, 2)->default('0.00');
            $table->decimal('new_qty', 18, 2)->default('0.00');
            $table->decimal('adjusted_qty', 18, 2)->default('0.00');
            $table->foreign('stock_adjustment_id')->references('id')->on('stock_adjustments')->onDelete('cascade');
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
        Schema::dropIfExists('stock_adjustment_detail');
    }
}
