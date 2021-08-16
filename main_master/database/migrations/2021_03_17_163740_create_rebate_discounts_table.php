<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRebateDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('rebate_discounts')) {
            Schema::create('rebate_discounts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('lob_id')->nullable();
                $table->string('agreement_id');
                $table->string('customer_code')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name');
                $table->boolean('rebate')->default(0)->comment('0=value; 1=Percentage');
                $table->boolean('is_promtional_sales')->default(0)->comment('0 means not promotional, 1 means promotional');
                $table->decimal('amount', 18, 2)->default('0.00')->nullable();
                $table->decimal('discount_amount', 18, 2)->default('0.00')->nullable();
                $table->date('from_date');
                $table->date('to_date');

                $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
                $table->foreign('lob_id')->references('id')->on('lobs');
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
        Schema::dropIfExists('rebate_discounts');
    }
}
