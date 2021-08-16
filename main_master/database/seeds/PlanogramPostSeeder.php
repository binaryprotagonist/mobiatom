<?php

use App\Model\PlanogramPost;
use Illuminate\Database\Seeder;

class PlanogramPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $customer = array(329, 344, 346, 349, 351, 352, 353, 354, 355, 356, 360, 361, 362, 363);
        $salesman = array(338,359,376);

        for ($i = 1; $i <= 1000; $i++) {
            $planogram_post = new PlanogramPost;
            $planogram_post->organisation_id = 61;
            $planogram_post->planogram_id = rand(23, 1025);
            $planogram_post->salesman_id = $salesman[array_rand($salesman)];
            $planogram_post->customer_id = $customer[array_rand($customer)];
            $planogram_post->distribution_id = rand(32,1038);
            $planogram_post->description = $faker->text;
            $planogram_post->status = 1;
            $planogram_post->save();
        }
    }
}
