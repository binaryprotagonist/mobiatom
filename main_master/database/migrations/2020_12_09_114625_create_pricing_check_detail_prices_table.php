<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricingCheckDetailPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricing_check_detail_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('pricing_check_id');
            $table->unsignedBigInteger('pricing_check_detail_id');
            $table->decimal('srp', 8,2);
            $table->decimal('price', 8,2);
            $table->foreign('pricing_check_id')->references('id')->on('pricing_checks')->onDelete('cascade');
            $table->foreign('pricing_check_detail_id')->references('id')->on('pricing_check_details')->onDelete('cascade');

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
        Schema::dropIfExists('pricing_check_detail_prices');
    }
}
