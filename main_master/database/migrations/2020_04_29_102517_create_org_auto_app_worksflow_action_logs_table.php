<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrgAutoAppWorksflowActionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('org_auto_app_worksflow_action_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->string('log_for')->comment('like Order || Delivery || Invoice etc.');
            $table->unsignedBigInteger('log_for_id')->comment('According to Log for: id, like order_id || delivery_id || invoice_id etc.');
            $table->unsignedBigInteger('actioned_by')->comment('user id, who\'s performed this action.');
            $table->string('status')->comment('Pending, Approved, Rejected, In-Process, Completed etc..');
            $table->json('request_data')->nullable()->comment('saved json data if required.');
            
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
        Schema::dropIfExists('org_auto_app_worksflow_action_logs');
    }
}
