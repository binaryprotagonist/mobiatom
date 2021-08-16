<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerMerchandizersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_merchandizers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('merchandizer_id');
            $table->foreign('user_id')->references('user_id')->on('customer_infos')->onDelete('cascade');
            $table->foreign('merchandizer_id')->references('user_id')->on('salesman_infos')->onDelete('cascade');
            $table->timestamps();
            //            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_merchandizer');
    }
}
