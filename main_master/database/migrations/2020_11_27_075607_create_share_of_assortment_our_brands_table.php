<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareOfAssortmentOurBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_of_assortment_our_brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('share_of_assortment_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('item_major_category_id');
            $table->decimal('captured_sku', 8,2);
            $table->decimal('brand_share', 8,2);
            $table->foreign('share_of_assortment_id')->references('id')->on('share_of_assortments')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands');
            $table->foreign('item_major_category_id')->references('id')->on('item_major_categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('share_of_assortment_our_brands');
    }
}
