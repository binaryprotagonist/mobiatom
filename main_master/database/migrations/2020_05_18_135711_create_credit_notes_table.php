<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('customer_id')->comment('comes from users table');
            $table->unsignedBigInteger('salesman_id')->comment('comes from users table');
            $table->unsignedBigInteger('trip_id')->comment('comes from trips table');
            $table->string('credit_note_number');
            $table->date('credit_note_date');
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->decimal('total_qty', 18, 2)->default('0.00');
            $table->decimal('total_gross', 18, 2)->default('0.00');
            $table->decimal('total_discount_amount', 18, 2)->default('0.00');
            $table->decimal('total_net', 18, 2)->default('0.00')->comment('total_gross - total_discount_amount');
            $table->decimal('total_vat', 18, 2)->default('0.00');
            $table->decimal('total_excise', 18, 2)->default('0.00');
            $table->decimal('grand_total', 18, 2)->default('0.00')->comment(' total_net + total_vat + total_excise');
            $table->decimal('pending_credit', 18, 2)->default('0.00');
            $table->string('reason');
            $table->integer('source');
            $table->boolean('status')->default(1);

            $table->text('credit_note_comment')->nullable();

            $table->enum('current_stage',['Pending','Approved','Rejected','In-Process','Completed'])->default('Pending');
            $table->text('current_stage_comment')->nullable();
            $table->unsignedBigInteger('lob_id')->nullable();


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
        Schema::dropIfExists('credit_notes');
    }
}
