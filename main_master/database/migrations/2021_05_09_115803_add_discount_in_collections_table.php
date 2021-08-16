<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountInCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            //
            $table->decimal('discount', 18, 2)->after('invoice_amount')->default('0.00');

            $table->enum('collection_status',['Created','Posted','PDC','Bounce'])->after('discount')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            //
            $table->dropColumn('discount');
            $table->dropColumn('collection_status');
        });
    }
}
