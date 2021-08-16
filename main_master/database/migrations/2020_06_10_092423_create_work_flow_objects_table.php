<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkFlowObjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_flow_objects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('work_flow_rule_id');
            $table->unsignedBigInteger('raw_id');
            $table->string('module_name')->comment('like Item, Invoice.. etc.');
            $table->json('request_object')->comment('complete json object store');
            $table->boolean('is_approved_all')->default(0)->comment('If approved from all levels then this record will be true or 1 and event will be fired (Create/Update/Delete)');
            $table->boolean('is_anyone_reject')->default(0)->comment('If anyone rejects this then this record will be true or 1 and the event will never be fired');
            $table->integer('currently_approved_stage')->default(0)->comment('How many levels is it approved');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('work_flow_rule_id')->references('id')->on('work_flow_rules')->onDelete('cascade');
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
        Schema::dropIfExists('work_flow_objects');
    }
}
