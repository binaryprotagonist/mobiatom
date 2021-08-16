<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganisationRoleHasPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organisation_role_has_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('software_id');
            $table->string('module_name');
            $table->unsignedBigInteger('organisation_role_id');
            $table->unsignedBigInteger('permission_id');
            $table->foreign('organisation_role_id')->references('id')->on('organisation_roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('default_permissions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organisation_role_has_permissions');
    }
}
