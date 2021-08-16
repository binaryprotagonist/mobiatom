<?php

use App\Model\Depot;
use Illuminate\Database\Seeder;

class DepotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $depot = new Depot;
        $depot->organisation_id = 2;
        $depot->region_id = 1;
        $depot->area_id = 1;
        $depot->user_id = 2;
        $depot->depot_name = $faker->name;
        $depot->depot_code = '1';
        $depot->depot_manager = $faker->name;
        $depot->depot_manager_contact = $faker->e164PhoneNumber;
        $depot->status = 1;
        $depot->save();
    }
}