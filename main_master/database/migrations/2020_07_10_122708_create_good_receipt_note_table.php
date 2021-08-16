<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodReceiptNoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_receipt_note', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
			$table->unsignedBigInteger('source_warehouse')->nullable();
			$table->unsignedBigInteger('destination_warehouse')->nullable();
			$table->string('grn_number');
			$table->date('grn_date');
            $table->text('grn_remark')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('good_receipt_note');
    }
}
