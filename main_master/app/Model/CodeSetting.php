<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;

class CodeSetting extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid',
        'organisation_id',
        'is_code_auto_depot',
        'prefix_code_depot',
        'start_code_depot',
        'next_coming_number_depot',
        'is_final_update_depot',

        'is_code_auto_customer',
        'prefix_code_customer',
        'start_code_customer',
        'next_coming_number_customer',
        'is_final_update_customer',

        'is_code_auto_bank_information',
        'prefix_code_bank_information',
        'start_code_bank_information',
        'next_coming_number_bank_information',
        'is_final_update_bank_information',

        'is_code_auto_customer_category',
        'prefix_code_customer_category',
        'start_code_customer_category',
        'next_coming_number_customer_category',
        'is_final_update_customer_category',

        'is_code_auto_customer_group',
        'prefix_code_customer_group',
        'start_code_customer_group',
        'next_coming_number_customer_group',
        'is_final_update_customer_group',

        'is_code_auto_customer_type',
        'prefix_code_customer_type',
        'start_code_customer_type',
        'next_coming_number_customer_type',
        'is_final_update_customer_type',

        'is_code_auto_item',
        'prefix_code_item',
        'start_code_item',
        'next_coming_number_item',
        'is_final_update_item',

        'is_code_auto_item_group',
        'prefix_code_item_group',
        'start_code_item_group',
        'next_coming_number_item_group',
        'is_final_update_item_group',

        'is_code_auto_region',
        'prefix_code_region',
        'start_code_region',
        'next_coming_number_region',
        'is_final_update_region',

        'is_code_auto_route',
        'prefix_code_route',
        'start_code_route',
        'next_coming_number_route',
        'is_final_update_route',

        'is_code_auto_route_item_grouping',
        'prefix_code_route_item_grouping',
        'start_code_route_item_grouping',
        'next_coming_number_route_item_grouping',
        'is_final_update_route_item_grouping',

        'is_code_auto_salesman',
        'prefix_code_salesman',
        'start_code_salesman',
        'next_coming_number_salesman',
        'is_final_update_salesman',

        'is_code_auto_salesman_role',
        'prefix_code_salesman_role',
        'start_code_salesman_role',
        'next_coming_number_salesman_role',
        'is_final_update_salesman_role',

        'is_code_auto_salesman_type',
        'prefix_code_salesman_type',
        'start_code_salesman_type',
        'next_coming_number_salesman_type',
        'is_final_update_salesman_type',

        'is_code_auto_van',
        'prefix_code_van',
        'start_code_van',
        'next_coming_number_van',
        'is_final_update_van',

        'is_code_auto_van_category',
        'prefix_code_van_category',
        'start_code_van_category',
        'next_coming_number_van_category',
        'is_final_update_van_category',

        'is_code_auto_van_type',
        'prefix_code_van_type',
        'start_code_van_type',
        'next_coming_number_van_type',
        'is_final_update_van_type',

        'is_code_auto_warehouse',
        'prefix_code_warehouse',
        'start_code_warehouse',
        'next_coming_number_warehouse',
        'is_final_update_warehouse',

        'is_code_auto_customer_info',
        'prefix_code_customer_info',
        'start_code_customer_info',
        'next_coming_number_customer_info',
        'is_final_update_customer_info',
    
        'is_code_auto_outlet_product_codes',
        'prefix_code_outlet_product_codes',
        'start_code_outlet_product_codes',
        'next_coming_number_outlet_product_codes',
        'is_final_update_outlet_product_codes',
    
        'is_code_auto_item_uoms',
        'prefix_code_item_uoms',
        'start_code_item_uoms',
        'next_coming_number_item_uoms',
        'is_final_update_item_uoms',
    
        'is_code_auto_country',
        'prefix_code_country',
        'start_code_country',
        'next_coming_number_country',
        'is_final_update_country',
    
        'is_code_auto_estimate',
        'prefix_code_estimate',
        'start_code_estimate',
        'next_coming_number_estimate',
        'is_final_update_estimate',
    
        'is_code_auto_reason',
        'prefix_code_reason',
        'start_code_reason',
        'next_coming_number_reason',
        'is_final_update_reason',
    
        'is_code_auto_reason',
        'prefix_code_reason',
        'start_code_reason',
        'next_coming_number_reason',
        'is_final_update_reason',
    
        'is_code_auto_vendor',
        'prefix_code_vendor',
        'start_code_vendor',
        'next_coming_number_vendor',
        'is_final_update_vendor',
    
        'is_code_auto_portfolio',
        'prefix_code_portfolio',
        'start_code_portfolio',
        'next_coming_number_portfolio',
        'is_final_update_portfolio',
    
        'is_code_auto_order',
        'prefix_code_order',
        'start_code_order',
        'next_coming_number_order',
        'is_final_update_order',
    
        'is_code_auto_delivery',
        'prefix_code_delivery',
        'start_code_delivery',
        'next_coming_number_delivery',
        'is_final_update_delivery',
    
        'is_code_auto_invoice',
        'prefix_code_invoice',
        'start_code_invoice',
        'next_coming_number_invoice',
        'is_final_update_invoice',
    
        'is_code_auto_goodreceiptnote',
        'prefix_code_goodreceiptnote',
        'start_code_goodreceiptnote',
        'next_coming_number_goodreceiptnote',
        'is_final_update_goodreceiptnote',
    
        'is_code_auto_depot_damage_expiry',
        'prefix_code_depot_damage_expiry',
        'start_code_depot_damage_expiry',
        'next_coming_number_depot_damage_expiry',
        'is_final_update_depot_damage_expiry',
    
        'is_code_auto_stock_adjustment',
        'prefix_code_stock_adjustment',
        'start_code_stock_adjustment',
        'next_coming_number_stock_adjustment',
        'is_final_update_stock_adjustment',
    
        'is_code_auto_purchase_order',
        'prefix_code_purchase_order',
        'start_code_purchase_order',
        'next_coming_number_purchase_order',
        'is_final_update_purchase_order',
    
        'is_code_auto_credit_note',
        'prefix_code_credit_note',
        'start_code_credit_note',
        'next_coming_number_credit_note',
        'is_final_update_credit_note',
    
        'is_code_auto_debit_note',
        'prefix_code_debit_note',
        'start_code_debit_note',
        'next_coming_number_debit_note',
        'is_final_update_debit_note',
    
        'is_code_auto_van_to_van_transfer',
        'prefix_code_van_to_van_transfer',
        'start_code_van_to_van_transfer',
        'next_coming_number_van_to_van_transfer',
        'is_final_update_van_to_van_transfer',

        'is_code_auto_cashier_reciept',
        'prefix_code_cashier_reciept',
        'start_code_cashier_reciept',
        'next_coming_number_cashier_reciept',
        'is_final_update_cashier_reciept',
    
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id', 'id');
    }
}
