<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceReminderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_reminder_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('invoice_reminder_id');
            $table->integer('reminder_day');
            $table->date('reminder_date');
            $table->boolean('is_automatically')->default(1);
            $table->enum('date_prefix', ['after', 'before']);
            $table->foreign('invoice_reminder_id')->references('id')->on('invoice_reminders')->onDelete('cascade');
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
        Schema::dropIfExists('invoice_reminder_details');
    }
}
