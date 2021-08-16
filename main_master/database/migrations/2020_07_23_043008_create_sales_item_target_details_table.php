<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesItemTargetDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_item_target_details', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('sales_target_id');
			$table->unsignedBigInteger('item_id');
			$table->unsignedBigInteger('item_uom_id');
			$table->enum('ApplyOn',[1,2])->default(null)->comment('1=item,2=header');
			$table->tinyInteger('status')->default(1);
			$table->foreign('sales_target_id')->references('id')->on('sales_targets')->onDelete('cascade');
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
        Schema::dropIfExists('sales_item_target_details');
    }
}
