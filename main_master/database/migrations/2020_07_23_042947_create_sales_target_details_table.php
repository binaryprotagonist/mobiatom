<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTargetDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_target_details', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('sales_target_id');
			$table->unsignedBigInteger('item_table_id')->default(0);
			$table->enum('Applyon', [1,2])->default(null)->comment('1=item,2=header');
			$table->unsignedBigInteger('fixed_qty')->default(null);
			$table->double('fixed_value', 10, 2);
			$table->unsignedBigInteger('from_qty')->default(null);
			$table->unsignedBigInteger('to_qty')->default(null);
			$table->double('from_value', 10, 2)->default(null);
			$table->double('to_value', 10, 2)->default(null);
			$table->double('commission', 10, 2)->default(null);
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
        Schema::dropIfExists('sales_target_details');
    }
}
