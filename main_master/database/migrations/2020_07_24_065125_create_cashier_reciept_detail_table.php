<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashierRecieptDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashier_reciept_detail', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('cashier_reciept_id');
			$table->enum('payemnt_type',[1,2,3])->default(null)->comment('1: Cash, 2:Cheque, 3:NEFT');
			$table->decimal('total_amount', 18,2)->default('0.00');
			$table->decimal('actual_amount', 18,2)->default('0.00');
			$table->decimal('variance', 18,2)->default('0.00');
            $table->foreign('cashier_reciept_id')->references('id')->on('cashier_reciept')->onDelete('cascade');
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
        Schema::dropIfExists('cashier_reciept_detail');
    }
}
