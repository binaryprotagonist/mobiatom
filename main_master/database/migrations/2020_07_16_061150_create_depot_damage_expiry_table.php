<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepotDamageExpiryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depot_damage_expiry', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
			$table->unsignedBigInteger('depot_id');
			$table->string('reference_code');
			$table->date('date');
			$table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
			$table->foreign('depot_id')->references('id')->on('depots')->onDelete('cascade');
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
        Schema::dropIfExists('depot_damage_expiry');
    }
}
