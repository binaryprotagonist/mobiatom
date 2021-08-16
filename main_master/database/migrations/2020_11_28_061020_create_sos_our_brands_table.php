<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSosOurBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('s_o_s_our_brands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('sos_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('item_major_category_id');
        
            $table->decimal('catured_block', 8,2);
            $table->decimal('catured_shelves', 8,2);
            $table->decimal('brand_share', 8,2);

            $table->foreign('sos_id')->references('id')->on('s_o_s')->onDelete('cascade');
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
        Schema::dropIfExists('sos_our_brands');
    }
}
