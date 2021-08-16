<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareOfDisplayCompetitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_of_display_competitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('share_of_display_id');
            $table->unsignedBigInteger('competitor_brand_id');
            $table->decimal('competitor_catured_gandola', 8,2);
            $table->decimal('competitor_catured_stand', 8,2);
            $table->decimal('competitor_brand_share', 8,2);

            $table->foreign('share_of_display_id')->references('id')->on('share_of_displays')->onDelete('cascade');
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
        Schema::dropIfExists('share_of_display_competitors');
    }
}
