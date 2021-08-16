<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanUnloadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_unloads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            
            $table->string('code');
			$table->unsignedBigInteger('trip_id'); // It's trip id or else
			$table->unsignedBigInteger('unload_type')->comment('Unload type from. like 1:Fresh, 2:bad return, 3: , 4:end inventory, 5: variant ');
			$table->unsignedBigInteger('route_id');
			$table->unsignedBigInteger('salesman_id')->comment('comes form user table');
            $table->date('transaction_date');
            $table->boolean('status')->default(1);
            $table->integer('source')->comment('Unload placed from. like 1:Mobile, 2:Backend, 3:Frontend');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('salesman_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salesman_unloads');
    }
}
