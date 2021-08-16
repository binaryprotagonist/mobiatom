<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignInventoryPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assign_inventory_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('assign_inventory_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('trip_id')->default(0)->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id');
            $table->double('qty', 18,2);
            $table->bigInteger('capacity')->default(0);
            $table->double('refill', 18,2);
            $table->double('fill', 18,2);
            $table->double('reorder', 18,2);
            $table->boolean('out_of_stock')->default(0);
            $table->boolean('status')->default(1);
            
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('assign_inventory_id')->references('id')->on('assign_inventories')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('item_uom_id')->references('id')->on('item_uoms')->onDelete('cascade');

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
        Schema::dropIfExists('assign_inventory_posts');
    }
}
