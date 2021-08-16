<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareOfAssortmentCompetitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_of_assortment_competitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('share_of_assortment_id');
            $table->unsignedBigInteger('competitor_info_id');
            $table->decimal('competitor_sku', 8,2);
            $table->decimal('brand_share', 8,2);

            $table->foreign('share_of_assortment_id')->references('id')->on('share_of_assortments')->onDelete('cascade');
            $table->foreign('competitor_info_id')->references('id')->on('competitor_infos');
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
        Schema::dropIfExists('share_of_assortment_competitors');
    }
}
