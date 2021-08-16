<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceDiscoPromoPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_disco_promo_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('combination_plan_key_id');
            $table->enum('use_for',['Pricing', 'Discount','Promotion'])->default('Pricing');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('combination_key_value')->comment('define number of key like Area/Material : 3/14, Country/Area/Material: 1/3/14 etc. check code sequence in pricing_combination_masters table');
            
            //For Promotion
            $table->enum('order_item_type', array('Any', 'All'))->nullable()->comment('Will be used in Promotion');
            $table->enum('offer_item_type', array('Any', 'All'))->nullable()->comment('Will be used in Promotion');

            //For Discounts
            $table->enum('type', [1,2])->default(1)->comment('1:Normal, 2:Slab');
            $table->decimal('qty_from', 18, 2)->nullable()->comment('Will be used in Discounts');
            $table->decimal('qty_to', 18, 2)->nullable()->comment('Will be used in Discounts. if this column is null then discount type 1:Percentage OR 2:Fixed Amount');
            $table->enum('discount_apply_on', [1,2])->default(1)->comment('1:Quantity, 2:Value');
            $table->enum('discount_type', [1,2])->default(1)->comment('1:Fixed, 2:Percentage');
            $table->decimal('discount_value', 18, 2)->comment('Will be used in Discounts. discounted amount / percentage according to discount type')->default('0.00');
            $table->string('discount_percentage', 10);

            $table->integer('priority_sequence')->default(1);
            $table->boolean('status')->default(1);
            $table->boolean('discount_main_type')->default(0)->comment('0:header, 1:item');

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('combination_plan_key_id')->references('id')->on('combination_plan_keys')->onDelete('cascade');
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
        Schema::dropIfExists('price_disco_promo_plans');
    }
}
