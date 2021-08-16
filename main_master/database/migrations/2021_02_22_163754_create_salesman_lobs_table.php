<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanLobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('salesman_lobs')) {
            Schema::create('salesman_lobs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('salesman_info_id');
                $table->unsignedBigInteger('lob_id');
                $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
                $table->foreign('salesman_info_id')->references('id')->on('salesman_infos')->onDelete('cascade');
                $table->foreign('lob_id')->references('id')->on('lobs')->onDelete('cascade');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesman_lobs');
    }
}
