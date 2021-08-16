<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanogramDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('planogram_distributions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('planogram_id');
            $table->unsignedBigInteger('planogram_customer_id');
            $table->unsignedBigInteger('distribution_id');
            $table->unsignedBigInteger('customer_id');
            $table->foreign('planogram_id')->references('id')->on('planograms')->onDelete('cascade');
            $table->foreign('planogram_customer_id')->references('id')->on('planogram_customers');
            $table->foreign('distribution_id')->references('id')->on('distributions');
            $table->foreign('customer_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('planogram_distributions');
    }
}
