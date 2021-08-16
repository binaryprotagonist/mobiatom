<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currency_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('name_plural');
            $table->string('symbol');
            $table->string('symbol_native');
            $table->bigInteger('decimal_digits');
            $table->bigInteger('rounding');
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
        Schema::dropIfExists('currency_masters');
    }
}
