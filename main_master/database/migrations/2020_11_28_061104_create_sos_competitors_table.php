<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSosCompetitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('s_o_s_competitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sos_id');
            $table->unsignedBigInteger('competitor_brand_id');
            $table->decimal('competitor_catured_block', 8,2);
            $table->decimal('competitor_catured_shelves', 8,2);
            $table->decimal('competitor_brand_share', 8,2);

            $table->foreign('sos_id')->references('id')->on('s_o_s')->onDelete('cascade');
            $table->foreign('competitor_brand_id')->references('id')->on('competitor_infos');
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
        Schema::dropIfExists('sos_competitors');
    }
}
