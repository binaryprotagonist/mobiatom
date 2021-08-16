<?php

use App\Model\Brand;
use Illuminate\Database\Seeder;

class BrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $brand = new Brand;
        $brand->organisation_id = 2;
        $brand->brand_code = "1";
        $brand->brand_name = $faker->name;
        $brand->status = 1;
        $brand->save();

        $brand = new Brand;
        $brand->organisation_id = 2;
        $brand->brand_code = "1";
        $brand->brand_name = $faker->name;
        $brand->status = 1;
        $brand->save();
    }
}