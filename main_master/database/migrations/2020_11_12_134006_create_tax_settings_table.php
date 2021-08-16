<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->boolean('is_tax_registered')->default(0);
            $table->string('trn_text')->nullable()->comment('trn is visiable only for vat');
            $table->string('number')->nullable()->comment('trn and gst number');
            $table->date('register_date')->nullable()->comment('trn and gst date');
            $table->boolean('composition_scheme')->default(0)->comment('Only for gst and vat time 0');
            $table->boolean('international_trade')->default(0)->comment('only for vat and gst 0');
            $table->integer('composition_scheme_percentage')->default(0)->comment('only for gst and vat time 0');
            $table->boolean('digital_services')->default(0)->comment('only for gst and vat 0');
            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
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
        Schema::dropIfExists('tax_settings');
    }
}
