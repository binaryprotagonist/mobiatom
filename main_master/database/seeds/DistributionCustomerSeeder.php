<?php

use App\Model\DistributionCustomer;
use Illuminate\Database\Seeder;

class DistributionCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customer = array(329, 344, 346, 349, 351, 352, 353, 354, 355, 356, 360, 361, 362, 363);
        for ($i = 1; $i <= 1000; $i++) {
            $distribution_customer = new DistributionCustomer;
            $distribution_customer->distribution_id = rand(32, 1038);
            $distribution_customer->customer_id= $customer[array_rand($customer)];
            $distribution_customer->save();
        }
    }
}
