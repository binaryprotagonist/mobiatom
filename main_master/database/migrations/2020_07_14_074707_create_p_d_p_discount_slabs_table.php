<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePDPDiscountSlabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('p_d_p_discount_slabs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('price_disco_promo_plan_id');
            $table->string('min_slab');
            $table->string('max_slab')->nullable();
            $table->string('value')->nullable();
            $table->string('percentage')->nullable();

            $table->foreign('price_disco_promo_plan_id')->references('id')->on('price_disco_promo_plans')->onDelete('cascade');
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
        Schema::dropIfExists('p_d_p_discount_slabs');
    }
}
