<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('customer_id')->comment('comes from users table');
            $table->unsignedBigInteger('invoice_id');
			$table->decimal('amount', 18, 2);
            $table->decimal('pending_amount', 18, 2);
            $table->unsignedBigInteger('lob_id')->nullable();
            $table->unsignedBigInteger('type')->comment('1:invoice, 2:debit_note, 3:credit_note');



            $table->timestamps();
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
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
        Schema::dropIfExists('collection_details');
    }
}
