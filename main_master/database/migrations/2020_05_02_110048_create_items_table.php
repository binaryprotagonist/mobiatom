<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('item_major_category_id');
            $table->unsignedBigInteger('item_group_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->boolean('is_product_catalog')->default(0);
            $table->boolean('is_promotional')->default(0);

            $table->string('item_code');
            $table->string('erp_code', 50);
            $table->string('item_name');
            $table->string('item_description')->nullable();

            $table->string('item_barcode')->nullable();
            $table->decimal('item_weight', 18, 2)->default(0.00)->nullable();
            $table->string('item_shelf_life')->nullable();
            $table->decimal('volume', 18,2)->default(0.000)->nullable();

            $table->integer('lower_unit_item_upc');
            $table->unsignedBigInteger('lower_unit_uom_id')->comment('which UOM is lower unit.');
            $table->decimal('lower_unit_item_price', 18, 2);
            $table->decimal('lower_unit_purchase_order_price', 18, 2);

            $table->boolean('is_tax_apply')->default(1);
            $table->decimal('item_vat_percentage', 5, 2)->default('0.00')->nullable();
            $table->decimal('item_excise', 5, 2)->default('0.00')->nullable();

            $table->boolean('new_lunch')->default(0);
            $table->date('start_date');
            $table->date('end_date');

            $table->enum('current_stage', ['Pending','Approved','Rejected'])->default('Pending');
            $table->text('current_stage_comment')->nullable();
            $table->string('item_image', 300)->nullable();


            $table->boolean('stock_keeping_unit')->default(0);

            $table->boolean('status')->default(1);

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('item_major_category_id')->references('id')->on('item_major_categories')->onDelete('cascade');
            $table->foreign('item_group_id')->references('id')->on('item_groups');
            
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
        Schema::dropIfExists('items');
    }
}
