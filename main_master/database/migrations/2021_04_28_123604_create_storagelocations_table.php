<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoragelocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('storagelocations')) {
            Schema::create('storagelocations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('route_id')->nullable()->comment('If route location');
                $table->unsignedBigInteger('warehouse_id')->nullable()->comment('If warehouse location');
                $table->string('code');
                $table->string('name');
                $table->enum('loc_type', [1, 2])->default(1)->comment('1-Finish,2-Bad');
                $table->foreign('organisation_id')
                    ->references('id')
                    ->on('organisations')
                    ->onDelete('cascade');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('storagelocations');
    }
}
