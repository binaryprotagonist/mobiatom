<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricingCheckDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricing_check_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('pricing_check_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_major_category_id');
            $table->date('date');
            $table->foreign('pricing_check_id')->references('id')->on('pricing_checks')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
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
        Schema::dropIfExists('pricing_check_details');
    }
}
