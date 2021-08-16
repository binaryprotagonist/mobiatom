<?php

use App\Model\DistributionExpireItem;
use Illuminate\Database\Seeder;

class DistributionExpireItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $salesman = array(338,359,376);
        $customer = array(329, 344, 346, 349, 351, 352, 353, 354, 355, 356, 360, 361, 362, 363);
        $items = array(74,76,86);
        $item_uom = array(58,59,60);
        $end_date = array("2020-10-10", "2020-10-12", "2020-10-11", "2020-10-15", "2020-10-18");

        for ($i = 1; $i <= 1000; $i++) {
            $distribution_expire_item = new DistributionExpireItem;
            $distribution_expire_item->organisation_id = 61;
            $distribution_expire_item->distribution_id = rand(32, 1034);
            $distribution_expire_item->salesman_id = $salesman[array_rand($salesman)];
            $distribution_expire_item->customer_id= $customer[array_rand($customer)];
            $distribution_expire_item->item_id= $items[array_rand($items)];
            $distribution_expire_item->item_uom_id= $item_uom[array_rand($item_uom)];
            $distribution_expire_item->qty= rand(10, 99);
            $distribution_expire_item->expiry_date= $end_date[array_rand($end_date)];
            $distribution_expire_item->save();
        }
    }
}
