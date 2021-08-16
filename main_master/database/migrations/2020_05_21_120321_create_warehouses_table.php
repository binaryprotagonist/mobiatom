<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->string('code');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('manager')->nullable();
            $table->boolean('is_main')->default(0);

            $table->string('lat')->nullable();
            $table->string('lang')->nullable();
            $table->boolean('status')->default(1);
            // $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('depot_id')->nullable()->comment('if warehouse is parent then this will be null');
            $table->unsignedBigInteger('route_id')->nullable()->comment('if warehouse is parent then this will be null');
            $table->unsignedBigInteger('parent_warehouse_id')->nullable()->comment('comes from same table.');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
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
        Schema::dropIfExists('warehouses');
    }
}
