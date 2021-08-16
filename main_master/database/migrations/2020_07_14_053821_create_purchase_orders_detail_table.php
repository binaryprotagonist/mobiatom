<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders_detail', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('purchase_order_id');
			$table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id')->nullable();
			$table->decimal('qty', 18, 2)->default('0.00');
			$table->decimal('price', 18, 2)->default('0.00');
			$table->decimal('discount', 18, 2)->default('0.00');
			$table->decimal('vat', 18, 2)->default('0.00');
			$table->decimal('net', 18, 2)->default('0.00');
			$table->decimal('excise', 18,2)->default('0.00');
			$table->decimal('total', 18,2)->default('0.00');
			$table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
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
        Schema::dropIfExists('purchase_orders_detail');
    }
}
