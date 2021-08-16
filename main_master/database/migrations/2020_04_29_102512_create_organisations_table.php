<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganisationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('reg_software_id');
            $table->string('org_name');
            $table->string('org_company_id');
            $table->string('org_tax_id')->nullable();
            $table->string('org_street1');
            $table->string('org_street2')->nullable();
            $table->string('org_city')->nullable();
            $table->string('org_state')->nullable();
            $table->integer('org_country_id');
            $table->string('org_postal')->nullable();
            $table->string('org_phone');
            $table->string('org_contact_person')->nullable();
            $table->string('org_contact_person_number')->nullable();
            $table->string('org_currency')->default('USD');
            $table->string('org_fasical_year')->nullable();
            $table->boolean('is_batch_enabled')->default(0)->comment('if 1 then enabled batch module.');
            $table->boolean('is_credit_limit_enabled')->default(0)->comment('if 1 then enabled batch module.');
            $table->string('org_logo')->default('assets/organisation/no-image.png');
            $table->string('gstin_number', 50);
            $table->string('gst_reg_date', 50);
            $table->boolean('is_auto_approval_set')->default(0);
            $table->boolean('org_status')->default(1);
            $table->boolean('is_trial_period')->default(1);
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
        Schema::dropIfExists('organisations');
    }
}