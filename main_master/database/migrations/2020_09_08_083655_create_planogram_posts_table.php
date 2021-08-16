<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanogramPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('planogram_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('planogram_id');
            $table->unsignedBigInteger('salesman_id');
            $table->unsignedBigInteger('trip_id')->default(0)->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('distribution_id');
            $table->text('description')->nullable();
            $table->string('feedback')->nullable();
            $table->decimal('score', 8,2)->default("0.00")->nullable();
            $table->boolean('status')->default(1);
            
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('planogram_id')->references('id')->on('planograms')->onDelete('cascade');
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
        Schema::dropIfExists('planogram_posts');
    }
}
