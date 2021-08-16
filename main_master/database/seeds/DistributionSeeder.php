<?php

use App\Model\Distribution;
use Illuminate\Database\Seeder;

class DistributionSeeder extends Seeder
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

        for ($i = 1; $i <= 1000; $i++) {
            $distribution = new Distribution;
            $distribution->organisation_id = 61;
            $distribution->name = $faker->name;
            $distribution->start_date = $start_date[array_rand($start_date)];
            $distribution->end_date = $end_date[array_rand($end_date)];
            $distribution->height = rand(1000,9999);
            $distribution->width = rand(1000,9999);
            $distribution->depth = rand(1000,9999);
            $distribution->status = 1;
            $distribution->save();
        }
    }
}
