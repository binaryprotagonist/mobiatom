<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJourneyPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journey_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->boolean('is_merchandiser')->default(0);
            $table->unsignedBigInteger('route_id')->default(0);
            $table->unsignedBigInteger('merchandiser_id')->default(0)->comment('Comes form salesman, either choose route or merchandiser');
            $table->string('name');
            $table->string('description')->nullable();
            $table->date('start_date');
            $table->boolean('no_end_date')->default(0);
            $table->date('end_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->integer('start_day_of_the_week')->default(1)->comment('1:Monday');
            $table->enum('plan_type', [1,2])->default(1)->comment('1:Day, 2:Week');
            $table->boolean('week_1')->default(0)->comment('if plan type 2:week');
            $table->boolean('week_2')->default(0)->comment('if plan type 2:week');
            $table->boolean('week_3')->default(0)->comment('if plan type 2:week');
            $table->boolean('week_4')->default(0)->comment('if plan type 2:week');
            $table->boolean('week_5')->default(0)->comment('if plan type 2:week');
            $table->boolean('is_enforce')->default(0);
            $table->boolean('status')->default(1);
            
            $table->enum('current_stage', ['Pending','Approved','Rejected'])->default('Pending');
            $table->text('current_stage_comment')->nullable();
            
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
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
        Schema::dropIfExists('journey_plans');
    }
}
