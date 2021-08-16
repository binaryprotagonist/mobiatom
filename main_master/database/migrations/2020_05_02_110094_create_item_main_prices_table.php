<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemMainPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_main_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('item_id');
            $table->string('item_upc',20);
            $table->unsignedBigInteger('item_uom_id');
            $table->boolean('stock_keeping_unit')->default(0);
            $table->decimal('item_price', 18,2)->default('0.00')->comment('default price');
            $table->decimal('purchase_order_price', 18,2)->default('0.00');

            $table->boolean('status')->default(1);
            
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
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
        Schema::dropIfExists('item_main_prices');
    }
}
