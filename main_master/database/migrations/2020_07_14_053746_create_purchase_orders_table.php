<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
			$table->unsignedBigInteger('vendor_id');
			$table->string('reference');
			$table->string('purchase_order');
			$table->date('purchase_order_date');
			$table->date('expected_delivery_date');
			$table->text('customer_note')->nullable();
			$table->decimal('gross_total', 18,2)->default('0.00');
			$table->decimal('vat_total', 18,2)->default('0.00');
			$table->decimal('excise_total', 18,2)->default('0.00');
			$table->decimal('net_total', 18,2)->default('0.00');
			$table->decimal('discount_total', 18,2)->default('0.00');
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
			$table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
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
        Schema::dropIfExists('purchase_orders');
    }
}
