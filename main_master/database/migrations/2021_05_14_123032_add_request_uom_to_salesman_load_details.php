<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestUomToSalesmanLoadDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesman_load_details', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_item_uom_id')
                ->after('requested_qty')
                ->nullable();

            $table->foreign('requested_item_uom_id', 'fr_requested_uom_id')
                ->references('id')
                ->on('item_uoms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesman_load_details', function (Blueprint $table) {
            //
        });
    }
}
