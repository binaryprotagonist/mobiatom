<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estimation', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
			$table->unsignedBigInteger('customer_id');
			$table->string('reference');
			$table->string('estimate_code');
			$table->date('estimate_date');
			$table->date('expairy_date');
			$table->unsignedBigInteger('salesperson_id');
			$table->string('subject');
			$table->text('customer_note')->nullable();
			$table->decimal('gross_total', 18,2)->default('0.00');
			$table->decimal('vat', 18,2)->default('0.00');
			$table->decimal('exise', 18,2)->default('0.00');
			$table->decimal('net_total', 18,2)->default('0.00');
			$table->decimal('discount', 18,2)->default('0.00');
            $table->decimal('total', 18,2)->default('0.00');
            
            $table->boolean('status')->default(1);
            
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
			$table->foreign('customer_id')->references('id')->on('customer_infos')->onDelete('cascade');
			$table->foreign('salesperson_id')->references('id')->on('sales_person')->onDelete('cascade');
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
        Schema::dropIfExists('estimation');
    }
}
