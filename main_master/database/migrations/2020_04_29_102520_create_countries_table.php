<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->string('name')->comment('Like India, United States');
            $table->string('country_code',10)->comment('Like IN, US');
            $table->string('dial_code',10)->nullable()->comment('Like +91, +12');
            $table->string('currency', 50)->comment('Like Indian rupee, United States dollar');
            $table->string('currency_code', 10)->nullable()->comment('Like INR, USD');
            $table->string('currency_symbol', 50)->comment('Like ₹, $');
            $table->boolean('status')->default(1);
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
        Schema::dropIfExists('countries');
    }
}
