<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimationDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estimation_detail', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('estimation_id');
			$table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id')->nullable();
			$table->decimal('item_qty', 18,2)->default('0.00');
            $table->decimal('item_price', 18,2)->default('0.00');
			$table->decimal('item_discount_amount', 18,2)->default('0.00');
			$table->decimal('item_vat', 18, 2)->default('0.00');
            $table->decimal('item_excise', 18, 2)->default('0.00');
			$table->decimal('item_grand_total', 18,2)->default('0.00');
			$table->decimal('item_net', 18, 2)->default('0.00');
			$table->foreign('estimation_id')->references('id')->on('estimation')->onDelete('cascade');
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
        Schema::dropIfExists('estimation_detail');
    }
}
