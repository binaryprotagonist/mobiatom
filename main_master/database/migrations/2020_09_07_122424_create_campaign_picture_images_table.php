<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignPictureImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_picture_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_campaign_picture');
            $table->string('image_string');
            $table->timestamps();
            
            $table->foreign('id_campaign_picture')->references('id')->on('campaign_pictures')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_picture_images');
    }
}
