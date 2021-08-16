<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoadRequestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('load_request_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('load_request_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('requested_item_uom_id');
            $table->unsignedBigInteger('item_uom_id');
            $table->decimal('qty', 18,2);
            $table->decimal('requested_qty', 18,2);

            $table->foreign('load_request_id')->references('id')->on('load_requests')->onDelete('cascade');
            
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
        Schema::dropIfExists('load_request_details');
    }
}
