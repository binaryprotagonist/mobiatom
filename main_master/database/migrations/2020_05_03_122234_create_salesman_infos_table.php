<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesmanInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salesman_infos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('salesman_type_id');
            $table->unsignedBigInteger('salesman_role_id');
            $table->string('salesman_code', 20);
            $table->string('salesman_supervisor')->nullable();
            $table->date('date_of_joning')->nullable();
            $table->boolean('status')->default(0);
            $table->string('profile_image')->nullable();

            $table->enum('current_stage', ['Pending','Approved','Rejected'])->default('Pending');
            $table->text('current_stage_comment')->nullable();
            $table->boolean('is_lob')->default(0)->comment('1=LOB');
                    

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('salesman_infos');
    }
}