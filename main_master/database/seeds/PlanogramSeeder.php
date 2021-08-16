<?php

use App\Model\Planogram;
use Illuminate\Database\Seeder;

class PlanogramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $start_date = array("2020-09-28", "2020-09-29", "2020-09-30", "2020-10-01", "2020-10-02");
        $end_date = array("2020-10-10", "2020-10-12", "2020-10-11", "2020-10-15", "2020-10-18");
        $customer = array(329, 344, 346, 349, 351, 352, 353, 354, 355, 356, 360, 361, 362, 363);

        for ($i = 1; $i <= 1000; $i++) {
            $planogram = new Planogram;
            $planogram->organisation_id = 61;
            $planogram->customer_id = $customer[array_rand($customer)];
            $planogram->name = $faker->name;
            $planogram->start_date = $start_date[array_rand($start_date)];
            $planogram->end_date = $end_date[array_rand($end_date)];
            $planogram->status = 1;
            $planogram->save();
        }
    }
}
