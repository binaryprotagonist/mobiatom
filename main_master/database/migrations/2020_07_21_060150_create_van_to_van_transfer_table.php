<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVanToVanTransferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('van_to_van_transfer', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
			$table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('source_route_id');
			$table->unsignedBigInteger('destination_route_id');
			$table->string('code');
			$table->date('date');
			$table->enum('status', ['Pending','Approve'])->default('Pending');
			$table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
			$table->foreign('source_route_id')->references('id')->on('routes')->onDelete('cascade');
			$table->foreign('destination_route_id')->references('id')->on('routes')->onDelete('cascade');
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
        Schema::dropIfExists('van_to_van_transfer');
    }
}
