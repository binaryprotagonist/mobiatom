<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOddoIdInSalesmanUnloadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salesman_unloads', function (Blueprint $table) {
            //
            $table->bigInteger('oddo_id')->nullable()->after('source');
            $table->longText('odoo_failed_response')->after('oddo_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salesman_unload', function (Blueprint $table) {
            //
            $table->dropColumn('oddo_id');
            $table->dropColumn('odoo_failed_response');
        });
    }
}
