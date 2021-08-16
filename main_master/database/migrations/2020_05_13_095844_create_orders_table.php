<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('from users table');
            $table->unsignedBigInteger('depot_id')->nullable();
            $table->unsignedBigInteger('order_type_id');
            $table->unsignedBigInteger('salesman_id')->nullable()->comment('comes from user table');
            $table->string('order_number');
            $table->date('order_date');
            $table->date('due_date');
            $table->date('delivery_date')->nullable();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->decimal('total_qty', 18, 2)->default('0.00');
            $table->decimal('total_gross', 18, 2)->default('0.00');
            $table->decimal('total_discount_amount', 18, 2)->default('0.00');
            $table->decimal('total_net', 18, 2)->default('0.00')->comment('total_gross - total_discount_amount');
            $table->decimal('total_vat', 18, 2)->default('0.00');
            $table->decimal('total_excise', 18, 2)->default('0.00');
            $table->decimal('grand_total', 18, 2)->default('0.00')->comment(' total_net + total_vat + total_excise');
            $table->text('any_comment')->nullable();

            $table->enum('current_stage', ['Pending','Approved','Rejected','In-Process','Partial-Deliver','Completed'])->default('Pending');
            $table->text('current_stage_comment')->nullable();

            $table->string('sign_image')->nullable();
            $table->integer('source')->comment('Order placed from. like 1:Mobile, 2:Backend, 3:Frontend');
            
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('lob_id')->nullable();
            
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
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
        Schema::dropIfExists('orders');
    }
}
