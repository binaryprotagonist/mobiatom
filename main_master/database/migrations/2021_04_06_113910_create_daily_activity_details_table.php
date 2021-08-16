<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyActivityDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_activity_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('daily_activity_id');
            $table->unsignedBigInteger('daily_activity_customer_id');
            $table->unsignedBigInteger('supervisor_category_id');
            $table->boolean('supervisor_category_status')->comment('1.Yes, 0:No');

            $table->foreign('daily_activity_id')
                ->references('id')
                ->on('daily_activities');

            $table->foreign('daily_activity_customer_id')
                ->references('id')
                ->on('daily_activity_customers');

            $table->foreign('supervisor_category_id')
                ->references('id')
                ->on('supervisor_categories');

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
        Schema::dropIfExists('daily_activity_details');
    }
}
