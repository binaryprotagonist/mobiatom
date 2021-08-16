<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOddoPostIdInLoadRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->string('oddo_post_id')
            ->after('approval_status')
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
            $table->dropColumn('oddo_post_id');
        });
    }
}
