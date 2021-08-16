<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('expense_category_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 18,2)->default('0.00');
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('lob_id')->nullable();

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories');
           // $table->foreign('customer_id')->references('id')->on('customer_infos');
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
        Schema::dropIfExists('expenses');
    }
}
