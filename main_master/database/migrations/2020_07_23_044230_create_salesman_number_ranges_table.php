<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanNumberRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_number_ranges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('salesman_id');
            
            $table->string('customer_from', 20)->nullable();
            $table->string('customer_to', 20)->nullable();

            $table->string('order_from', 20)->nullable();
            $table->string('order_to', 20)->nullable();
            
            $table->string('invoice_from', 20)->nullable();
            $table->string('invoice_to', 20)->nullable();
            
            $table->string('collection_from', 20)->nullable();
            $table->string('collection_to', 20)->nullable();
            
            $table->string('credit_note_from', 20)->nullable();
            $table->string('credit_note_to', 20)->nullable();
            
            $table->string('unload_from', 20)->nullable();
            $table->string('unload_to', 20)->nullable();
            
            $table->string('exchange_from', 20)->nullable();
            $table->string('exchange_to', 20)->nullable();

            $table->foreign('salesman_id')->references('id')->on('salesman_infos')->onDelete('cascade');
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
        Schema::dropIfExists('salesman_number_ranges');
    }
}
