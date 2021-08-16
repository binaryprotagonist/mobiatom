<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCodeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('code_settings')) {
            Schema::create('code_settings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('organisation_id');
                $table->boolean('is_code_auto_depot')->default(1);
                $table->string('prefix_code_depot', 20)->nullable();
                $table->string('start_code_depot', 20)->nullable();
                $table->string('next_coming_number_depot')->nullable()->default(1);
                $table->boolean('is_final_update_depot')->default(0);

                $table->boolean('is_code_auto_customer')->default(1);
                $table->string('prefix_code_customer', 20)->nullable();
                $table->string('start_code_customer', 20)->nullable();
                $table->string('next_coming_number_customer')->nullable()->default(1);
                $table->boolean('is_final_update_customer')->default(0);

                $table->boolean('is_code_auto_bank_information')->default(1);
                $table->string('prefix_code_bank_information', 20)->nullable();
                $table->string('start_code_bank_information', 20)->nullable();
                $table->string('next_coming_number_bank_information')->nullable()->default(1);
                $table->boolean('is_final_update_bank_information')->default(0);

                $table->boolean('is_code_auto_customer_category')->default(1);
                $table->string('prefix_code_customer_category', 20)->nullable();
                $table->string('start_code_customer_category', 20)->nullable();
                $table->string('next_coming_number_customer_category')->nullable()->default(1);
                $table->boolean('is_final_update_customer_category')->default(0);

                $table->boolean('is_code_auto_customer_group')->default(1);
                $table->string('prefix_code_customer_group', 20)->nullable();
                $table->string('start_code_customer_group', 20)->nullable();
                $table->string('next_coming_number_customer_group')->nullable()->default(1);
                $table->boolean('is_final_update_customer_group')->default(0);

                $table->boolean('is_code_auto_customer_type')->default(1);
                $table->string('prefix_code_customer_type', 20)->nullable();
                $table->string('start_code_customer_type', 20)->nullable();
                $table->string('next_coming_number_customer_type')->nullable()->default(1);
                $table->boolean('is_final_update_customer_type')->default(0);

                $table->boolean('is_code_auto_item')->default(1);
                $table->string('prefix_code_item', 20)->nullable();
                $table->string('start_code_item', 20)->nullable();
                $table->string('next_coming_number_item')->nullable()->default(1);
                $table->boolean('is_final_update_item')->default(0);

                $table->boolean('is_code_auto_item_group')->default(1);
                $table->string('prefix_code_item_group', 20)->nullable();
                $table->string('start_code_item_group', 20)->nullable();
                $table->string('next_coming_number_item_group')->nullable()->default(1);
                $table->boolean('is_final_update_item_group')->default(0);

                $table->boolean('is_code_auto_region')->default(1);
                $table->string('prefix_code_region', 20)->nullable();
                $table->string('start_code_region', 20)->nullable();
                $table->string('next_coming_number_region')->nullable()->default(1);
                $table->boolean('is_final_update_region')->default(0);

                $table->boolean('is_code_auto_route')->default(1);
                $table->string('prefix_code_route', 20)->nullable();
                $table->string('start_code_route', 20)->nullable();
                $table->string('next_coming_number_route')->nullable()->default(1);
                $table->boolean('is_final_update_route')->default(0);

                $table->boolean('is_code_auto_route_item_grouping')->default(1);
                $table->string('prefix_code_route_item_grouping', 20)->nullable();
                $table->string('start_code_route_item_grouping', 20)->nullable();
                $table->string('next_coming_number_route_item_grouping')->nullable()->default(1);
                $table->boolean('is_final_update_route_item_grouping')->default(0);

                $table->boolean('is_code_auto_salesman')->default(1);
                $table->string('prefix_code_salesman', 20)->nullable();
                $table->string('start_code_salesman', 20)->nullable();
                $table->string('next_coming_number_salesman')->nullable()->default(1);
                $table->boolean('is_final_update_salesman')->default(0);

                $table->boolean('is_code_auto_salesman_role')->default(1);
                $table->string('prefix_code_salesman_role', 20)->nullable();
                $table->string('start_code_salesman_role', 20)->nullable();
                $table->string('next_coming_number_salesman_role')->nullable()->default(1);
                $table->boolean('is_final_update_salesman_role')->default(0);

                $table->boolean('is_code_auto_salesman_type')->default(1);
                $table->string('prefix_code_salesman_type', 20)->nullable();
                $table->string('start_code_salesman_type', 20)->nullable();
                $table->string('next_coming_number_salesman_type')->nullable()->default(1);
                $table->boolean('is_final_update_salesman_type')->default(0);

                $table->boolean('is_code_auto_van')->default(1);
                $table->string('prefix_code_van', 20)->nullable();
                $table->string('start_code_van', 20)->nullable();
                $table->string('next_coming_number_van')->nullable()->default(1);
                $table->boolean('is_final_update_van')->default(0);

                $table->boolean('is_code_auto_van_category')->default(1);
                $table->string('prefix_code_van_category', 20)->nullable();
                $table->string('start_code_van_category', 20)->nullable();
                $table->string('next_coming_number_van_category')->nullable()->default(1);
                $table->boolean('is_final_update_van_category')->default(0);

                $table->boolean('is_code_auto_van_type')->default(1);
                $table->string('prefix_code_van_type', 20)->nullable();
                $table->string('start_code_van_type', 20)->nullable();
                $table->string('next_coming_number_van_type')->nullable()->default(1);
                $table->boolean('is_final_update_van_type')->default(0);

                $table->boolean('is_code_auto_warehouse')->default(1);
                $table->string('prefix_code_warehouse', 20)->nullable();
                $table->string('start_code_warehouse', 20)->nullable();
                $table->string('next_coming_number_warehouse')->nullable()->default(1);
                $table->boolean('is_final_update_warehouse')->default(0);

                $table->boolean('is_code_auto_customer_info')->default(1);
                $table->string('prefix_code_customer_info', 20)->nullable();
                $table->string('start_code_customer_info', 20)->nullable();
                $table->string('next_coming_number_customer_info')->nullable()->default(1);
                $table->boolean('is_final_update_customer_info')->default(0);

                $table->boolean('is_code_auto_outlet_product_codes')->default(1);
                $table->string('prefix_code_outlet_product_codes', 20)->nullable();
                $table->string('start_code_outlet_product_codes', 20)->nullable();
                $table->string('next_coming_number_outlet_product_codes')->nullable()->default(1);
                $table->boolean('is_final_update_outlet_product_codes')->default(0);

                $table->boolean('is_code_auto_item_uoms')->default(1);
                $table->string('prefix_code_item_uoms', 20)->nullable();
                $table->string('start_code_item_uoms', 20)->nullable();
                $table->string('next_coming_number_item_uoms')->nullable()->default(1);
                $table->boolean('is_final_update_item_uoms')->default(0);

                $table->boolean('is_code_auto_country')->default(1);
                $table->string('prefix_code_country', 20)->nullable();
                $table->string('start_code_country', 20)->nullable();
                $table->string('next_coming_number_country')->nullable()->default(1);
                $table->boolean('is_final_update_country')->default(0);

                $table->boolean('is_code_auto_estimate')->default(1);
                $table->string('prefix_code_estimate', 20)->nullable();
                $table->string('start_code_estimate', 20)->nullable();
                $table->string('next_coming_number_estimate')->nullable()->default(1);
                $table->boolean('is_final_update_estimate')->default(0);

                $table->boolean('is_code_auto_reason')->default(1);
                $table->string('prefix_code_reason', 20)->nullable();
                $table->string('start_code_reason', 20)->nullable();
                $table->string('next_coming_number_reason')->nullable()->default(1);
                $table->boolean('is_final_update_reason')->default(0);

                $table->boolean('is_code_auto_vendor')->default(1);
                $table->string('prefix_code_vendor', 20)->nullable();
                $table->string('start_code_vendor', 20)->nullable();
                $table->string('next_coming_number_vendor')->nullable()->default(1);
                $table->boolean('is_final_update_vendor')->default(0);

                $table->boolean('is_code_auto_portfolio')->default(1);
                $table->string('prefix_code_portfolio', 20)->nullable();
                $table->string('start_code_portfolio', 20)->nullable();
                $table->string('next_coming_number_portfolio')->nullable()->default(1);
                $table->boolean('is_final_update_portfolio')->default(0);

                $table->boolean('is_code_auto_order')->default(1);
                $table->string('prefix_code_order', 20)->nullable();
                $table->string('start_code_order', 20)->nullable();
                $table->string('next_coming_number_order')->nullable()->default(1);
                $table->boolean('is_final_update_order')->default(0);

                $table->boolean('is_code_auto_delivery')->default(1);
                $table->string('prefix_code_delivery', 20)->nullable();
                $table->string('start_code_delivery', 20)->nullable();
                $table->string('next_coming_delivery')->nullable()->default(1);
                $table->boolean('is_final_update_delivery')->default(0);

                $table->boolean('is_code_auto_invoice')->default(1);
                $table->string('prefix_code_invoice', 20)->nullable();
                $table->string('start_code_invoice', 20)->nullable();
                $table->string('next_coming_invoice')->nullable()->default(1);
                $table->boolean('is_final_update_invoice')->default(0);

                $table->boolean('is_code_auto_goodreceiptnote')->default(1);
                $table->string('prefix_code_goodreceiptnote', 20)->nullable();
                $table->string('start_code_goodreceiptnote', 20)->nullable();
                $table->string('next_coming_goodreceiptnote')->nullable()->default(1);
                $table->boolean('is_final_update_goodreceiptnote')->default(0);

                $table->boolean('is_code_auto_damage_expiry')->default(1);
                $table->string('prefix_code_damage_expiry', 20)->nullable();
                $table->string('start_code_damage_expiry', 20)->nullable();
                $table->string('next_coming_damage_expiry')->nullable()->default(1);
                $table->boolean('is_final_update_damage_expiry')->default(0);

                $table->boolean('is_code_auto_stock_adjustment')->default(1);
                $table->string('prefix_code_stock_adjustment', 20)->nullable();
                $table->string('start_code_stock_adjustment', 20)->nullable();
                $table->string('next_coming_stock_adjustment')->nullable()->default(1);
                $table->boolean('is_final_update_stock_adjustment')->default(0);

                $table->boolean('is_code_auto_depot_damage_expiry')->default(1);
                $table->string('prefix_code_depot_damage_expiry', 20)->nullable();
                $table->string('start_code_depot_damage_expiry', 20)->nullable();
                $table->string('next_coming_depot_damage_expiry')->nullable()->default(1);
                $table->boolean('is_final_update_depot_damage_expiry')->default(0);

                $table->boolean('is_code_auto_collection')->default(1);
                $table->string('prefix_code_collection', 20)->nullable();
                $table->string('start_code_collection', 20)->nullable();
                $table->string('next_coming_collection')->nullable()->default(1);
                $table->boolean('is_final_update_collection')->default(0);

                $table->boolean('is_code_auto_purchase_order')->default(1);
                $table->string('prefix_code_purchase_order', 20)->nullable();
                $table->string('start_code_purchase_order', 20)->nullable();
                $table->string('next_coming_purchase_order')->nullable()->default(1);
                $table->boolean('is_final_update_purchase_order')->default(0);

                $table->boolean('is_code_auto_estimation')->default(1);
                $table->string('prefix_code_estimation', 20)->nullable();
                $table->string('start_code_estimation', 20)->nullable();
                $table->string('next_coming_estimation')->nullable()->default(1);
                $table->boolean('is_final_update_estimation')->default(0);

                $table->boolean('is_code_auto_credit_note')->default(1);
                $table->string('prefix_code_credit_note', 20)->nullable();
                $table->string('start_code_credit_note', 20)->nullable();
                $table->string('next_coming_credit_note')->nullable()->default(1);
                $table->boolean('is_final_update_credit_note')->default(0);

                $table->boolean('is_code_auto_debit_note')->default(1);
                $table->string('prefix_code_debit_note', 20)->nullable();
                $table->string('start_code_debit_note', 20)->nullable();
                $table->string('next_coming_debit_note')->nullable()->default(1);
                $table->boolean('is_final_update_debit_note')->default(0);

                $table->boolean('is_code_auto_van_to_van_transfer')->default(1);
                $table->string('prefix_code_van_to_van_transfer', 20)->nullable();
                $table->string('start_code_van_to_van_transfer', 20)->nullable();
                $table->string('next_coming_van_to_van_transfer')->nullable()->default(1);
                $table->boolean('is_final_update_van_to_van_transfer')->default(0);

                $table->boolean('is_code_auto_cashier_reciept')->default(1);
                $table->string('prefix_code_cashier_reciept', 20)->nullable();
                $table->string('start_code_cashier_reciept', 20)->nullable();
                $table->string('next_coming_number_cashier_reciept')->nullable()->default(1);
                $table->boolean('is_final_update_cashier_reciept')->default(0);

                $table->boolean('is_code_auto_unload_number')->default(1);
                $table->string('prefix_code_unload_number', 20)->nullable();
                $table->string('start_code_unload_number', 20)->nullable();
                $table->string('next_coming_unload_number')->nullable()->default(1);
                $table->boolean('is_final_update_unload_number')->default(0);

                $table->boolean('is_code_auto_asset_tracking')->default(1);
                $table->string('prefix_code_asset_tracking', 20)->nullable();
                $table->string('start_code_asset_tracking', 20)->nullable();
                $table->string('next_coming_asset_tracking')->nullable()->default(1);
                $table->boolean('is_final_update_asset_tracking')->default(0);

                // $table->boolean('is_code_auto_salesman_load')->default(1);
                // $table->string('prefix_code_salesman_load', 20)->nullable();
                // $table->string('start_code_salesman_load', 20)->nullable();
                // $table->string('next_coming_number_salesman_load')->nullable()->default(1);
                // $table->boolean('is_final_update_salesman_load')->default(0);

                // $table->boolean('is_code_auto_salesman_unload')->default(1);
                // $table->string('prefix_code_salesman_unload', 20)->nullable();
                // $table->string('start_code_salesman_unload', 20)->nullable();
                // $table->string('next_coming_number_salesman_unload')->nullable()->default(1);
                // $table->boolean('is_final_update_salesman_unload')->default(0);



                $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('code_settings');
    }
}
