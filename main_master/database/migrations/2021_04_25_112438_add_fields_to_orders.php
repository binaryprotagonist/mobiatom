<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')
                ->after('salesman_id')
                ->nullable();

            $table->enum('approval_status', ['Deleted', 'Created', 'Updated', 'In-Process', 'Partial-Delivered', 'Delivered', 'Completed'])
                ->after('current_stage_comment')
                ->default('Created')
                ->nullable();

            $table->foreign('route_id')
                ->references('id')
                ->on('routes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
}
