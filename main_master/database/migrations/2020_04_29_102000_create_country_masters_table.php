<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Like India, United States');
            $table->string('country_code',10)->comment('Like IN, US');
            $table->string('dial_code',10)->comment('Like +91, +12');
            $table->string('currency', 50)->comment('Like Indian rupee, United States dollar');
            $table->string('currency_code', 10)->comment('Like INR, USD');
            $table->string('currency_symbol', 50)->comment('Like ₹, $');
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
        Schema::dropIfExists('country_masters');
    }
}
