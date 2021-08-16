<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_infos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('payment_term_id');
            $table->unsignedBigInteger('customer_group_id')->nullable();
            $table->unsignedBigInteger('sales_organisation_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->unsignedBigInteger('customer_category_id')->nullable();
            $table->unsignedBigInteger('merchandiser_id')->comment('merchandiser id is salesman id comes form users table')->nullable();
            $table->string('customer_code', 25);
            $table->string('erp_code', 50);
            $table->integer('customer_type_id')->nullable();
            $table->string('customer_address_1');
            $table->string('customer_address_2')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_zipcode')->nullable();
            $table->string('customer_phone')->nullable();

            $table->string('customer_address_1_lat')->nullable();
            $table->string('customer_address_1_lang')->nullable();
            $table->string('customer_address_2_lat')->nullable();
            $table->string('customer_address_2_lang')->nullable();

            $table->decimal('balance', 15, 2)->default('0.00')->nullable();
            $table->decimal('credit_limit', 15, 2)->default('0.00')->nullable();
            $table->integer('credit_days')->default(0)->nullable();
            $table->unsignedBigInteger('ship_to_party')->nullable()->comment('Comes from same table');
            $table->unsignedBigInteger('sold_to_party')->nullable()->comment('Comes from same table');
            $table->unsignedBigInteger('payer')->nullable()->comment('Comes from same table');
            $table->unsignedBigInteger('bill_to_payer')->nullable()->comment('Comes from same table');

            $table->string('profile_image');
            $table->date('expired_date')->nullable();
            $table->enum('due_on', [1, 2])
                ->default(1)
                ->comment('1 ON Invoice Date, 2 On Customer Statement')
                ->nullable();

            $table->integer('source')->comment('customer placed from. like 1:Mobile, 2:Backend, 3:Frontend');

            $table->enum('current_stage', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->text('current_stage_comment')->nullable();

            $table->boolean('status')->default(1);
            $table->boolean('is_lob')->default(0)->comment('1=customer LOB, 0=Central');
            $table->unsignedBigInteger('amount')->nullable();

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('payment_term_id')->references('id')->on('payment_terms');
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
        Schema::dropIfExists('customer_infos');
    }
}
