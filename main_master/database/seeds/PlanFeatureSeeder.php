<?php

use App\Model\PlanFeature;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = array(
            "1" => array(
                array(
                    "dashboard" => array(
                        'Dashboard',
                    ), 
                ),
                array(
                    "master" => array(
                        'Customer',
                        'Item',
                        'Salesman',
                        'Journey Plan',
                        'Route Item Grouping',
                        'Portfolio Management'
                    ), 
                ),
                array(
                    "sales trancation" => array(
                        'Order'                      
                    )
                ),
                array(
                    "pricing" => array(
                        'Pricing',
                        'Promotion',
                        'Discount'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),

                'Product Catalog',
            ),
            "2" => array(
                array(
                    "dashboard" => array(
                        'Dashboard',
                    ), 
                ),
                array(
                    "master" => array(
                        'Customer',
                        'Item',
                        'Salesman',
                        'Journey Plan',
                        'Route Item Grouping',
                        'Portfolio Management'
                    ), 
                ),
                array(
                    "sales trancation" => array(
                        'Order'                      
                    )
                ),
                array(
                    "pricing" => array(
                        'Pricing',
                        'Promotion',
                        'Discount'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),
                'Product Catalog',
            ),
            "3" => array(
                array(
                    "dashboard" => array(
                        'Dashboard',
                    ), 
                ),
                array(
                    "master" => array(
                        'Customer',
                        'Item',
                        'Salesman',
                        'Journey Plan',
                        'Route Item Grouping',
                        'Portfolio Management'
                    ), 
                ),
                array(
                    "sales trancation" => array(
                        'Order'                      
                    )
                ),
                array(
                    "pricing" => array(
                        'Pricing',
                        'Promotion',
                        'Discount'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),
                'Product Catalog',
                'Custom Field',
                'Workflow'
            ),
            "4" => array(
                array(
                    "dashboard" => array(
                        'Dashboard',
                    ), 
                ),
                array(
                    "master" => array(
                        'Customer',
                        'Item',
                        'Salesman',
                        'Journey Plan',
                        'Route Item Grouping',
                        'Portfolio Management'
                    ), 
                ),
                array(
                    "sales trancation" => array(
                        'Order'                      
                    )
                ),
                array(
                    "pricing" => array(
                        'Pricing',
                        'Promotion',
                        'Discount'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),
                array(
                    "sales taraget" => array(
                        'sales taraget'
                    )
                ),

                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing-Advanced',
                'Promotion',
                'Discount',
                'Order',
                'Product Catalog',
                'Route Item Grouping',
                'Portfolio Management',
                'Sales Target',
                'Custom Field',
                'Workflow'
            ),
            "5" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing-Basic',
                'Promotion',
                'Discount',
                'Rebate',
                'Order',
                'Delivery',
                'Invoice',
                'Credit Note',
                'Debit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Salesman Load',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stock Adjustment',
                'Cashier Receipt',
                'Session Endorsment',
                'Expenses',
                'Extimate'
            ),
            "6" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing-Advanced',
                'Promotion',
                'Discount',
                'Rebate',
                'Order',
                'Delivery',
                'Invoice',
                'Credit Note',
                'Debit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Salesman Load',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stock Adjustment',
                'Cashier Receipt',
                'Session Endorsment',
                'Expenses',
                'Extimate'
            ),
            "7" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing-Advanced',
                'Promotion',
                'Discount',
                'Rebate',
                'Order',
                'Delivery',
                'Invoice',
                'Credit Note',
                'Debit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Salesman Load',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stock Adjustment',
                'Cashier Receipt',
                'Session Endorsment',
                'Expenses',
                'Extimate',
                'Workflow',
                'Route Flag',
                'Customer flag',
                'Advanced Setting'
            ),
            "8" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing-Advanced',
                'Promotion',
                'Discount',
                'Rebate',
                'Order',
                'Delivery',
                'Invoice',
                'Credit Note',
                'Debit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Salesman Load',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stock Adjustment',
                'Cashier Receipt',
                'Session Endorsment',
                'Expenses',
                'Extimate',
                'Workflow',
                'Route Flag',
                'Customer flag',
                'Advanced Setting'
            ),
            "9" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing',
                'Promotion',
                'Discount',
                'Order',
                'Credit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Stock In Store',
                'Complaint Feedback',
                'Competitor Info',
                'Campaign',
                'Planogram',
                'Shelf Display',
                'Product Catalog',
                'Asset Tracking',
                'New Lunch',
                'Promotional Accountibility'
            ),
            "10" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing',
                'Promotion',
                'Discount',
                'Order',
                'Credit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Stock In Store',
                'Complaint Feedback',
                'Competitor Info',
                'Campaign',
                'Planogram',
                'Shelf Display',
                'Product Catalog',
                'Asset Tracking',
                'New Lunch',
                'Promotional Accountibility'
            ),
            "11" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing',
                'Promotion',
                'Discount',
                'Order',
                'Credit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Stock In Store',
                'Complaint Feedback',
                'Competitor Info',
                'Campaign',
                'Planogram',
                'Shelf Display',
                'Product Catalog',
                'Asset Tracking',
                'New Lunch',
                'Promotional Accountibility',
                'WorkFlow',
                'Route Flag',
                'Customer Flag',
                'Adavanced Setting'
            ),
            "12" => array(
                'Dashboard',
                'Customer',
                'Item',
                'Salesman',
                'Journey Plan',
                'Pricing',
                'Promotion',
                'Discount',
                'Order',
                'Credit Note',
                'Colelction',
                'Route Item Grouping',
                'Portfolio Management',
                'Stock In Store',
                'Complaint Feedback',
                'Competitor Info',
                'Campaign',
                'Planogram',
                'Shelf Display',
                'Product Catalog',
                'Asset Tracking',
                'New Lunch',
                'Promotional Accountibility',
                'WorkFlow',
                'Route Flag',
                'Customer Flag',
                'Adavanced Setting'
            ),
            "13" => array(
                'Dashboard',
                'Item',
                'Stock In',
                'Stock Out',
                'Stock Audit',
                'History',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stok Adjustment',
                'Warehouse',
                'Report',
                'Returns-Credit Note',
                'Sales Order',
                'Delivery',
                'Invoice',
                'Collection',
            ),
            "14" => array(
                'Dashboard',
                'Item',
                'Stock In',
                'Stock Out',
                'Stock Audit',
                'History',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stok Adjustment',
                'Warehouse',
                'Report',
                'Returns-Credit Note',
                'Sales Order',
                'Delivery',
                'Invoice',
                'Collection',
            ),
            "15" => array(
                'Dashboard',
                'Item',
                'Stock In',
                'Stock Out',
                'Stock Audit',
                'History',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stok Adjustment',
                'Warehouse',
                'Report',
                'Returns-Credit Note',
                'Sales Order',
                'Delivery',
                'Invoice',
                'Collection',
                'Workflow',
                'Custom Field',
            ),
            "16" => array(
                'Dashboard',
                'Item',
                'Stock In',
                'Stock Out',
                'Stock Audit',
                'History',
                'Vendor',
                'Purchase Order',
                'GRN',
                'Depot Damage/Expiry',
                'Stok Adjustment',
                'Warehouse',
                'Report',
                'Returns-Credit Note',
                'Sales Order',
                'Delivery',
                'Invoice',
                'Collection',
                'Workflow',
                'Custom Field'
            ),
            "17" => array(
                'Dashboard',
                'Customer',
                'Levels',
                'Points transfer',
                'Transacions',
                'Earning rules',
                'POS',
                'Merchants',
                'Segment',
                'Reward Campaigns'                
            ),
            "18" => array(
                'Dashboard',
                'Customer',
                'Levels',
                'Points transfer',
                'Transacions',
                'Earning rules',
                'POS',
                'Merchants',
                'Segment',
                'Reward Campaigns'                
            ),
            "19" => array(
                'Dashboard',
                'Customer',
                'Levels',
                'Points transfer',
                'Transacions',
                'Earning rules',
                'POS',
                'Merchants',
                'Segment',
                'Reward Campaigns'                
            ),
            "20" => array(
                'Dashboard',
                'Customer',
                'Levels',
                'Points transfer',
                'Transacions',
                'Earning rules',
                'POS',
                'Merchants',
                'Segment',
                'Reward Campaigns'                
            )
        );

        foreach ($plans as $key => $plan) {
            foreach ($plan as $plan_f) {
                $paln_feature = new PlanFeature;
                $paln_feature->feature_name = $plan_f;
                $paln_feature->plan_id = $key;
                $paln_feature->save();
            }
        }
    }
}
