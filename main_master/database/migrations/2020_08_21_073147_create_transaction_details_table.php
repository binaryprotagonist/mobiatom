<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('transaction_id');
            $table->double('item_id', 18,2)->default(0.00);
            $table->double('load_qty', 18,2)->default(0.00);
            $table->double('transfer_in_qty', 18,2)->default(0.00);
            $table->double('transfer_out_qty', 18,2)->default(0.00);
            $table->double('request_qty', 18,2)->default(0.00);
            $table->double('unload_qty', 18,2)->default(0.00);
            $table->double('sales_qty', 18,2)->default(0.00);
            $table->double('return_qty', 18,2)->default(0.00);
            $table->double('bad_retun_qty', 18,2)->default(0.00);
            $table->double('free_qty', 18,2)->default(0.00);
            $table->double('opening_qty', 18,2)->default(0.00);
            $table->double('closing_qty', 18,2)->default(0.00);
            $table->boolean('status')->default(1);

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');

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
        Schema::dropIfExists('transaction_details');
    }
}
