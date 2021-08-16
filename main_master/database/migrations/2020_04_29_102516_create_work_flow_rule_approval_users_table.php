<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkFlowRuleApprovalUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_flow_rule_approval_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('work_flow_rule_id');
            $table->unsignedBigInteger('wfr_approval_role_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('work_flow_rule_id')->references('id')->on('work_flow_rules')->onDelete('cascade');
            $table->foreign('wfr_approval_role_id')->references('id')->on('work_flow_rule_approval_roles')->onDelete('cascade');
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
        Schema::dropIfExists('work_flow_rule_approval_users');
    }
}
