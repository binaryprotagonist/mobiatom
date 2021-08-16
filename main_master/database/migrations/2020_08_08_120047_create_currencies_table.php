<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('currency_master_id');
            $table->string('name');
            $table->char('symbol', 10);
            $table->string('code');
            $table->string('name_plural');
            $table->char('symbol_native', 10);
            $table->bigInteger('decimal_digits');
            $table->bigInteger('rounding');
            $table->boolean('default_currency')->default(0);
            $table->enum('format', ['1,234,567.89', '1.234.567.89', '1 234 567.89']);
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('currency_master_id')->references('id')->on('currency_masters')->onDelete('cascade');
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
        Schema::dropIfExists('currencies');
    }
}
