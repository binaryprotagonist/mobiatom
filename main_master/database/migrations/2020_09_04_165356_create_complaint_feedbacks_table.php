<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintFeedbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaint_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('route_id')->comment('comes from routes table')->nullable();
            $table->unsignedBigInteger('customer_id')->comment('comes from users table');
            $table->unsignedBigInteger('salesman_id')->comment('comes from users table');
            $table->unsignedBigInteger('trip_id');
            $table->string('complaint_id');
            $table->string('title');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->text('description');
            $table->enum('type', ['complaint', 'suggestion'])->default('complaint');
            $table->enum('current_stage', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->text('current_stage_comment')->nullable();
            // $table->string('image')->nullable();
            $table->boolean('status')->default(1);

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
            $table->foreign('salesman_id')->references('id')->on('users')->onDelete('cascade');

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
        Schema::dropIfExists('complaint_feedbacks');
    }
}
