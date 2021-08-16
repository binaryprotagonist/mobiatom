<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepotDamageExpiryDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depot_damage_expiry_detail', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('depotdamageexpiry_id');
			$table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id')->nullable();
			$table->decimal('qty', 18, 2)->default('0.00');
			$table->unsignedBigInteger('reason_id');
			$table->foreign('depotdamageexpiry_id')->references('id')->on('depot_damage_expiry')->onDelete('cascade');
			$table->foreign('reason_id')->references('id')->on('reasons');
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
        Schema::dropIfExists('depot_damage_expiry_detail');
    }
}
