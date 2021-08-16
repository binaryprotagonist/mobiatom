<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListingFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('listing_fees')) {
            Schema::create('listing_fees', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('lob_id')->nullable()->comment('Customer Lob Id');
                $table->string('agreement_id');
                $table->string('customer_code')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->comment('customer_info of user_id');
                $table->string('name');
                $table->decimal('amount', 18, 2)->default('0.00')->nullable();
                $table->decimal('listing_fee_amount', 18, 2)->default('0.00')->nullable();
                $table->foreign('lob_id')->references('id')->on('lobs')->onDelete('cascade');
                $table->date('from_date');
                $table->date('to_date');
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
        Schema::dropIfExists('listing_fees');
    }
}
