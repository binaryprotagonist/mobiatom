<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkFlowObjectActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_flow_object_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('work_flow_object_id');
            $table->unsignedBigInteger('user_id')->comment('who\'s fire this event');
            $table->boolean('approved_or_rejected')->default(1)->comment('if approved:1/true else 0/false');

            $table->foreign('work_flow_object_id')->references('id')->on('work_flow_objects')->onDelete('cascade');
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
        Schema::dropIfExists('work_flow_object_actions');
    }
}
