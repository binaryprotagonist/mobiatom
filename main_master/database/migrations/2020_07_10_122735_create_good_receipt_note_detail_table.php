<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodReceiptNoteDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_receipt_note_detail', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('good_receipt_note_id');
			$table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_uom_id')->nullable();
			$table->decimal('qty', 18, 2)->default('0.00');
            $table->string('reason', 50);
			$table->foreign('good_receipt_note_id')->references('id')->on('good_receipt_note')->onDelete('cascade');
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
        Schema::dropIfExists('good_receipt_note_detail');
    }
}
