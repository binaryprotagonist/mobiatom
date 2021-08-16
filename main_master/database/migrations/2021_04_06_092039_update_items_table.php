<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('supervisor_category_id')
                ->after('is_product_catalog')
                ->nullable();

            $table->unsignedBigInteger('lob_id')
                ->after('brand_id')
                ->nullable();

            $table->foreign('lob_id')->references('id')->on('lobs');

            // $table->foreign('supervisor_category_id')
            //     ->references('id')
            //     ->on('supervisor_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
