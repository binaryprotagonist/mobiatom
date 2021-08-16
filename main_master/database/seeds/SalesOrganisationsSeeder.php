<?php

use App\Model\SalesOrganisation;
use Illuminate\Database\Seeder;

class SalesOrganisationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $sales_organisation = new SalesOrganisation;
        $sales_organisation->organisation_id = 2;
        $sales_organisation->code = '1';
        $sales_organisation->name = $faker->name;
        $sales_organisation->status = 1;
        $sales_organisation->save();
    }
}