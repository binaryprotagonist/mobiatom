<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionalPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotional_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('promotional_id');
            $table->unsignedBigInteger('trip_id')->default(0)->nullable();
            $table->unsignedBigInteger('salesman_id');
            $table->string('cusotmer');
            $table->string('invoice_code');
            $table->string('phone');
            $table->decimal('amount_spend', 8,2);
            $table->text('image');
            
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('promotional_id')->references('id')->on('promotionals')->onDelete('cascade');

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
        Schema::dropIfExists('promotional_posts');
    }
}
