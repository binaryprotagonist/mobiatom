<?php

use App\Model\CustomerCategory;
use Illuminate\Database\Seeder;

class CustomerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $customer_category = new CustomerCategory;
        $customer_category->organisation_id = 2;
        $customer_category->customer_category_code = "CC001";
        $customer_category->customer_category_name = $faker->name;
        $customer_category->status = 1;
        $customer_category->save();

        $customer_category = new CustomerCategory;
        $customer_category->organisation_id = 2;
        $customer_category->customer_category_code = "CC002";
        $customer_category->customer_category_name = $faker->name;
        $customer_category->status = 1;
        $customer_category->save();
    }
}
