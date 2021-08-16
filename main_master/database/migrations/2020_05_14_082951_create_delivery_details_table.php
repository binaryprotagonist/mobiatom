<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id');
            $table->unsignedBigInteger('discount_id')->nullable()->comment('if discount apply then add discount id (price_disco_promo_plans table) here.');
            $table->boolean('is_free')->default(0)->comment('1:yes free');
            $table->boolean('is_item_poi')->default(0)->comment('if 1 means this item belongs to promotion offered item');
            $table->unsignedBigInteger('promotion_id')->nullable()->comment('if promotion apply then add promotion id (price_disco_promo_plans table) here.');
            $table->decimal('item_qty', 18,2)->default('0.00');
            $table->decimal('item_price', 18,2)->default('0.00');
            $table->decimal('item_gross', 18,2)->default('0.00')->comment('item_qty * item_price');
            $table->decimal('item_discount_amount', 18,2)->default('0.00');
            $table->decimal('item_net', 18, 2)->default('0.00')->comment('item_gross - item_discount_amount');
            $table->decimal('item_vat', 18, 2)->default('0.00');
            $table->decimal('item_excise', 18, 2)->default('0.00');
            $table->decimal('item_grand_total', 18,2)->default('0.00')->comment('item_net + item_vat + item_excise');
            $table->string('batch_number')->nullable();

            $table->decimal('invoiced_qty', 18, 2)->default('0.00')->comment('invoiced qyt');
            $table->decimal('open_qty', 18, 2)->default('0.00')->comment('deliery qty - invoiced_qty');

            $table->enum('delivery_status', ['Pending', 'Invoiced', 'Partial-Invoiced'])->default('Pending');

            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('cascade');
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
        Schema::dropIfExists('delivery_details');
    }
}
