<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignInventoryPostExpiriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assign_inventory_post_expiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('assign_inventory_post_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id');
            $table->decimal('qty', 8, 2);
            $table->date('expiry_date');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('assign_inventory_post_id')->references('id')->on('assign_inventory_posts')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('item_uom_id')->references('id')->on('item_uoms');

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
        Schema::dropIfExists('assign_inventory_post_expiries');
    }
}
