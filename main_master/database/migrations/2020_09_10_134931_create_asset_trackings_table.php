<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_trackings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('model_name');
            $table->string('barcode');
            $table->string('category');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location');
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->string('area');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('wroker')->nullable();
            $table->string('additional_wroker')->nullable();
            $table->string('team')->nullable();
            $table->string('vendors')->nullable();
            $table->date('purchase_date');
            $table->date('placed_in_service');
            $table->decimal('purchase_price', 18,2);
            $table->date('warranty_expiration');
            $table->date('residual_price');
            $table->text('additional_information')->nullable();
            $table->date('useful_life');
            $table->text('image')->nullable();

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('asset_trackings');
    }
}
