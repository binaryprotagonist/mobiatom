<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVarianceToCashierRecieptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cashier_reciept', function (Blueprint $table) {
            $table->decimal('actual_amount', 18, 2)
                ->after('total_amount')
                ->default("0.00");

            $table->decimal('variance', 18, 2)
                ->after('actual_amount')
                ->default("0.00");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cashier_reciept', function (Blueprint $table) {
            //
        });
    }
}
