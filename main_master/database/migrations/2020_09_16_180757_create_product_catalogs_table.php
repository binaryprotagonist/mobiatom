<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCatalogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_catalogs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('item_id');
            $table->string('barcode')->nullable();
            $table->decimal('net_weight', 18,2)->nullable();
            $table->string('flawer')->nullable();
            $table->string('shelf_file')->nullable();
            $table->string('ingredients')->nullable();
            $table->string('energy')->nullable();
            $table->string('fat')->nullable();
            $table->string('protein')->nullable();
            $table->string('carbohydrate')->nullable();
            $table->string('calcium')->nullable();
            $table->string('sodium')->nullable();
            $table->string('potassium')->nullable();
            $table->string('crude_fibre')->nullable();
            $table->string('vitamin')->nullable();
            $table->string('image_string')->nullable();
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');

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
        Schema::dropIfExists('product_catalogs');
    }
}
