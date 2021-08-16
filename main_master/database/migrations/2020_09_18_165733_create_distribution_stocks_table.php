<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distribution_stocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('distribution_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('salesman_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_uom_id');
            $table->decimal('stock', 18,2);
            $table->decimal('capacity', 8);
            $table->boolean('is_out_of_stock')->default(0)->comment('1 = out of stock, 0 = not out of stock');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('distribution_id')->references('id')->on('distributions')->onDelete('cascade');
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
        Schema::dropIfExists('distribution_stocks');
    }
}
