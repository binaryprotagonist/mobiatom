<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetTrackingPostImagessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_tracking_post_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('asset_tracking_id');
            $table->unsignedBigInteger('asset_tracking_post_id');
            $table->text('image_string');

            $table->foreign('asset_tracking_id')->references('id')->on('asset_trackings')->onDelete('cascade');
            $table->foreign('asset_tracking_post_id')->references('id')->on('asset_tracking_posts')->onDelete('cascade');
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
        Schema::dropIfExists('asset_tracking_post_imagess');
    }
}
