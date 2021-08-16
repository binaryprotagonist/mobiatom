<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->enum('use_for', ['Customer','Depot'])->default('Customer')->comment('Customer, Depot');
            $table->enum('for_module', ['Order','Delivery', 'Invoice'])->default('Order');
            $table->string('name');
            $table->string('description');
            // $table->string('prefix_code');
            // $table->bigInteger('start_range')->default('1');
            // $table->bigInteger('end_range')->default('100000');
            // $table->string('next_available_code')->nullable()->comment('for use next coming number');
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
        Schema::dropIfExists('order_types');
    }
}
