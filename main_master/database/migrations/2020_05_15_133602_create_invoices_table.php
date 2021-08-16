<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('depot_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_type_id');
            $table->unsignedBigInteger('delivery_id')->nullable();
            $table->unsignedBigInteger('salesman_id')->nullable()->comment('comes from user table');
            $table->unsignedBigInteger('trip_id')->nullable()->comment('comes from trips table');
            $table->enum('invoice_type',[1,2])->default(1)->comment('1.Invoicing, 2.OTC-Order to Cash. if invoice_type=2 then order_id and delivery_id will be null.');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('invoice_due_date')->comment('this will auto filled according to customer_infos table, credit_days column.');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->decimal('total_qty', 18, 2)->default('0.00');
            $table->decimal('total_gross', 18, 2)->default('0.00');
            $table->decimal('total_discount_amount', 18, 2)->default('0.00');
            $table->decimal('total_net', 18, 2)->default('0.00')->comment('total_gross - total_discount_amount');
            $table->decimal('total_vat', 18, 2)->default('0.00');
            $table->decimal('total_excise', 18, 2)->default('0.00');
            $table->decimal('grand_total', 18, 2)->default('0.00')->comment(' total_net + total_vat + total_excise');

            $table->enum('current_stage',['Pending','Approved','Rejected','In-Process','Completed'])->default('Pending');
            $table->text('current_stage_comment')->nullable();

            $table->boolean('payment_received')->default(0)->comment('if full amount (grand_total) received');
            $table->integer('source')->comment('Order placed from. like 1:Mobile, 2:Backend, 3:Frontend');

            $table->boolean('status')->default(1);
            
            $table->boolean('is_premium_invoice')->nullable()->comment('is_premium_invoice = 1 means, invoice is premium, is_premium_invoice = null means, invoice is not premium');
            $table->unsignedBigInteger('lob_id')->nullable();

            $table->unsignedBigInteger('customer_lpo')->nullable();

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
        Schema::dropIfExists('invoices');
    }
}
