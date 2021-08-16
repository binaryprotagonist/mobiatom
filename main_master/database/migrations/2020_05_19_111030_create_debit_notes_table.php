<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('customer_id')->comment('comes from users table');
            $table->unsignedBigInteger('salesman_id')->nullable()->comment('comes from users table');
            $table->unsignedBigInteger('trip_id')->comment('comes from trips table');
            $table->string('reason');
            $table->string('debit_note_number');
            $table->date('debit_note_date');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->decimal('total_qty', 18, 2)->default('0.00');
            $table->decimal('total_gross', 18, 2)->default('0.00');
            $table->decimal('total_discount_amount', 18, 2)->default('0.00');
            $table->decimal('total_net', 18, 2)->default('0.00')->comment('total_gross - total_discount_amount');
            $table->decimal('total_vat', 18, 2)->default('0.00');
            $table->decimal('total_excise', 18, 2)->default('0.00');
            $table->decimal('grand_total', 18, 2)->default('0.00')->comment(' total_net + total_vat + total_excise');
            $table->text('debit_note_comment')->nullable();

            $table->integer('source')->comment('Debit note from. like 1:Mobile, 2:Backend, 3:Frontend');
            $table->boolean('status')->default(1);

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
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
        Schema::dropIfExists('debit_notes');
    }
}
