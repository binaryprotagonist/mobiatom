<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShelfRentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shelf_rents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('lob_id');
            $table->string('agreement_id');
            $table->string('customer_code')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->decimal('amount', 18,2)->default('0.00')->nullable();  
            $table->date('from_date');
            $table->date('to_date');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('lob_id')->references('id')->on('lobs')->onDelete('cascade');
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
        Schema::dropIfExists('shelf_rents');
    }
}
