<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('sale_prices')) {
            Schema::create('sale_prices', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->string('agreement_id');
                $table->string('customer_code')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name');
                $table->decimal('shelf_rent_amount', 18, 2)->default('0.00')->nullable();
                $table->date('from_date');
                $table->date('to_date');
                $table->unsignedBigInteger('lob_id');
                $table->foreign('lob_id')->references('id')->on('lobs')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_prices');
    }
}
