<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidToLoadRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->enum('current_stage', ['Pending', 'Approved', 'Rejected'])
                ->after('source')
                ->default('Pending');


            $table->text('current_stage_comment')
                ->after('current_stage')
                ->nullable();

            $table->enum('approval_status', ['Created', 'Load Created', 'Updated', 'Deleted'])
                ->after('current_stage_comment')
                ->default('Created');


            $table->unsignedBigInteger('trip_id')
                ->after('salesman_id')
                ->nullable()
                ->comment('Only Comming from mobile');

            $table->foreign('trip_id')
                ->references('id')
                ->on('trips');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('load_requests', function (Blueprint $table) {
            //
        });
    }
}
