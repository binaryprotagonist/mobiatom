<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('offer_name');
            $table->date('offer_start_date');
            $table->date('offer_end_date')->nullable();
            $table->text('description');
            $table->decimal('discount_amount', 18,2)->default(0.00);
            $table->decimal('discount_percentage', 18,2)->default(0.00);
            $table->integer('duration_months')->default(0);
            $table->date('duration_end_date')->nullable();

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
        Schema::dropIfExists('offers');
    }
}
