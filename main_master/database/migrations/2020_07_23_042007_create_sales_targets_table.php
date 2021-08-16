<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
			$table->enum('TargetEntity', [1,2,3])->default(2)->comment('1=Depot,2=Salesman,3=Region');
			$table->string('TargetName');
			$table->unsignedBigInteger('TargetOwnerId');
			$table->date('StartDate')->nullable();
			$table->date('EndDate')->nullable();
			$table->enum('Applyon', [1,2])->default(null)->comment('1=item,2=header');
			$table->enum('TargetType', [1,2])->default(null)->comment('1=Quantity,2=Value');
			$table->enum('TargetVariance', [1,2])->default(null)->comment('1=Fixed,2=Slab');
			$table->enum('CommissionType', [1,2])->default(null)->comment('1=Fixed,2=Percentage');
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('sales_targets');
    }
}
