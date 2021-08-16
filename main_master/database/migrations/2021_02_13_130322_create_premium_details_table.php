<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePremiumDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('premium_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->string('name');
            $table->date('valid_from');
            $table->date('valid_to'); 
            $table->unsignedBigInteger('type');        
            $table->decimal('qty', 18, 2)->nullable();          
            $table->unsignedBigInteger('amount')->nullable();    
            $table->unsignedBigInteger('invoice_amount'); 
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
        Schema::dropIfExists('premium_details');
    }
}
