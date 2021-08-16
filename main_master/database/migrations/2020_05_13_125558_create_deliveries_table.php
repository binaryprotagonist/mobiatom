<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->comment('from users table.');
            $table->unsignedBigInteger('salesman_id')->comment('from users table.');
            $table->unsignedBigInteger('delivery_type')->comment('come from order type table.');
            $table->enum('delivery_type_source',['1','2'])->comment('1: convert to delivery, 2: Direct Delivery');
            $table->string('delivery_number');
            $table->date('delivery_date');
            $table->date('delivery_due_date');
            $table->string('delivery_weight')->nullable();

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
        Schema::dropIfExists('deliveries');
    }
}
