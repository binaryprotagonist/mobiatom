<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanRouteChangeApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('salesman_route_change_approvals')) {

            Schema::create('salesman_route_change_approvals', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('route_id');
                $table->unsignedBigInteger('salesman_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('journey_plan_id');
                $table->unsignedBigInteger('supervisor_id');
                $table->date('requested_date');
                $table->date('approval_date')->nullable();
                $table->enum('route_approval', ['Approved', 'Rejected', 'Pending']);
                $table->string('reason')->nullable();

                $table->foreign('organisation_id')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');

                $table->foreign('route_id')
                    ->references('id')
                    ->on('routes')
                    ->onDelete('cascade');

                $table->foreign('salesman_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->foreign('journey_plan_id')
                    ->references('id')
                    ->on('journey_plans')
                    ->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesman_route_change_approval');
    }
}
