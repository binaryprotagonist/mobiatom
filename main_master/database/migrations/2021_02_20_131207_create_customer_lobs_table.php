<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerLobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('customer_lobs')) {
            Schema::create('customer_lobs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('customer_info_id');
                $table->unsignedBigInteger('region_id');
                $table->unsignedBigInteger('route_id')->nullable();
                $table->unsignedBigInteger('payment_term_id');
                $table->unsignedBigInteger('lob_id');
                $table->unsignedBigInteger('customer_group_id')->nullable();
                $table->unsignedBigInteger('sales_organisation_id');
                $table->unsignedBigInteger('channel_id');
                $table->unsignedBigInteger('customer_category_id');
                $table->integer('customer_type_id')->nullable();
                $table->decimal('amount', 8, 2)->default('0.00')->nullable();
                $table->decimal('balance', 15, 2)->default('0.00')->nullable();
                $table->decimal('credit_limit', 15, 2)->default('0.00')->nullable();
                $table->integer('credit_days')->default(0)->nullable();
                $table->unsignedBigInteger('ship_to_party')->nullable()->comment('Comes from customer_info table');
                $table->unsignedBigInteger('sold_to_party')->nullable()->comment('Comes from customer_info table');
                $table->unsignedBigInteger('payer')->nullable()->comment('Comes from customer_info table');
                $table->unsignedBigInteger('bill_to_payer')->nullable()->comment('Comes from customer_info table');
                $table->unsignedBigInteger('merchandiser_id')->nullable();
                $table->enum('due_on', [1, 2])->default(1)->comment('1 ON Invoice Date, 2 On Customer Statement')->nullable();

                $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
                $table->foreign('payment_term_id')->references('id')->on('payment_terms');
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
        Schema::dropIfExists('customer_lobs');
    }
}
