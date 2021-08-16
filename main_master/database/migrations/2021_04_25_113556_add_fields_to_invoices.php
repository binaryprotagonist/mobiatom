<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')
                ->after('salesman_id')
                ->nullable();

            $table->decimal('pending_credit', 18, 3)
                ->after('grand_total')
                ->nullable();

            $table->boolean('is_exchange')
                ->after('pending_credit')
                ->default(0);

            $table->string('exchange_number')
                ->after('is_exchange')
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
        Schema::table('invoices', function (Blueprint $table) {
            //
        });
    }
}
