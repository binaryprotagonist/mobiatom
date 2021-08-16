<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
			$table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
			$table->string('vender_code');
			$table->string('firstname');
			$table->string('lastname');
			$table->string('email');
			$table->string('company_name');
			$table->string('mobile');
			$table->string('website')->nullable();
			$table->string('address1');
			$table->string('address2')->nullable();
			$table->string('city');
			$table->string('state');
			$table->string('zip')->nullable();
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
        Schema::dropIfExists('vendors');
    }
}
