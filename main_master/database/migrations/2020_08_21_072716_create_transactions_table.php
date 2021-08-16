<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('salesman_id');
            $table->unsignedBigInteger('route_id');
            $table->enum('transaction_type', ['1','2','3','4','5','6','7'])->comment('1-load,2-transfer,3-Request,4-unload,5-sales,6-Return,7-Free');
            $table->date('transaction_date');
            $table->dateTime('transaction_time');
            $table->integer('source')->comment("1-mobile, 2-frontend")->default(2);
            $table->string('reference', 50)->nullable();
            $table->boolean('status')->default(1);

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');

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
        Schema::dropIfExists('transactions');
    }
}
