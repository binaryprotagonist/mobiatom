<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('software_id');
            $table->string('name');
            $table->boolean('is_active')->default(0);
            
            $table->foreign('software_id')->references('id')->on('softwares');

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
        Schema::dropIfExists('setting_menus');
    }
}
