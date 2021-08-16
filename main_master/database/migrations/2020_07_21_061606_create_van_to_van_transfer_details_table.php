<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVanToVanTransferDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('van_to_van_transfer_details', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('vantovantransfer_id');
			$table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id')->nullable();
			$table->decimal('quantity', 18, 2)->default('0.00');
			$table->foreign('vantovantransfer_id')->references('id')->on('van_to_van_transfer')->onDelete('cascade');
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
        Schema::dropIfExists('van_to_van_transfer_details');
    }
}
