<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCombinationPlanKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('combination_plan_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->string('combination_key_name')->nullable();
            $table->string('combination_key')->comment('like Area/Material, Area/Material etc.');
            $table->string('combination_key_code')->comment('define number of key like Area/Material : 3/11, Country/Area/Material: 1/3/11 etc. check code sequence in pricing_combination_masters table');
            $table->boolean('status')->default(1);

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
        Schema::dropIfExists('combination_plan_keys');
    }
}
