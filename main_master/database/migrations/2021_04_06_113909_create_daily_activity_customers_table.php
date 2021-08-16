<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyActivityCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_activity_customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('daily_activity_id');
            $table->unsignedBigInteger('customer_id');
            $table->enum('shelf_display', ['poor', 'good', 'excellent'])->nullable();
            $table->enum('off_shelf_display', ['poor', 'good', 'excellent'])->nullable();
            $table->string('opportunity')->nullable();
            $table->string('out_of_stock')->nullable();
            $table->string('remarks')->nullable();


            $table->foreign('daily_activity_id')
                ->references('id')
                ->on('daily_activities');

            $table->foreign('customer_id')
                ->references('id')
                ->on('users');

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
        Schema::dropIfExists('daily_activity_customers');
    }
}
