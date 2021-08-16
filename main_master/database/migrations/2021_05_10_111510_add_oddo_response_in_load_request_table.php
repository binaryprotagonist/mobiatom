<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOddoResponseInLoadRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->longText('odoo_failed_response')
            ->after('oddo_post_id')
            ->nullable();
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
            $table->dropColumn('odoo_failed_response');
        });
    }
}
