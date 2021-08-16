<?php

use App\Model\DistributionStock;
use Illuminate\Database\Seeder;

class DistributionStockSeeder extends Seeder
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

        for ($i = 1; $i <= 1000; $i++) {
            $distribution_stock = new DistributionStock;
            $distribution_stock->organisation_id = 61;
            $distribution_stock->distribution_id = rand(32, 1034);
            $distribution_stock->salesman_id = $salesman[array_rand($salesman)];
            $distribution_stock->customer_id= $customer[array_rand($customer)];
            $distribution_stock->item_id= $items[array_rand($items)];
            $distribution_stock->item_uom_id= $item_uom[array_rand($item_uom)];
            $distribution_stock->stock= rand(10, 99);
            $distribution_stock->save();
        }
    }
}
