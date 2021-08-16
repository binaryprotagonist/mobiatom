<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->unsignedBigInteger('usertype')->default(1)->comment('0:superadmin, 1:admin (organisation), 2:customer, 3:salesman..., we\'ll add all the users type after confirmation');
            $table->string('parent_id')->nullable()->comment('If the user type is admin then put the admin id here to make it as a group.');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('api_token')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile')->nullable();
            $table->integer('country_id')->nullable();
            $table->boolean('is_approved_by_admin')->default(1);
            $table->boolean('status')->default(1);
            $table->string('id_stripe')->nullable();
            $table->enum('login_type', ['system', 'google', 'facebook', 'twitter', 'mobile']);
            $table->integer('role_id')->default(2)->comment('1:superadmin, 2 org-admin, 3...');
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
