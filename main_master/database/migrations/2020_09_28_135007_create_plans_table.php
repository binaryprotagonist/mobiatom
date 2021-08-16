<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('software_id');
            $table->string('name');
            $table->decimal('monthly_price', 8,2);
            $table->integer('maximum_user');
            $table->string('stripe_plan_id')->nullable();
            $table->decimal('yearly_price', 8,2);
            $table->boolean('is_active')->default(1);

            $table->foreign('software_id')->references('id')->on('softwares')->onDelete('cascade');
            
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
        Schema::dropIfExists('plans');
    }
}
