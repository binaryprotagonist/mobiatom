<?php

use App\Model\AssetTracking;
use Illuminate\Database\Seeder;

class AssetTrackingSeeder extends Seeder
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
        $start_date = array("2020-09-28", "2020-09-29", "2020-09-30", "2020-10-01", "2020-10-02");
        $end_date = array("2020-10-10", "2020-10-12", "2020-10-11", "2020-10-15", "2020-10-18");


        for ($i = 1; $i <= 500; $i++) {


            $asset_tracking = new AssetTracking;
            $asset_tracking->organisation_id = 61;
            $asset_tracking->customer_id = $customer[array_rand($customer)];

            $asset_tracking->title = $faker->name;
            $asset_tracking->start_date = $start_date[array_rand($start_date)];
            $asset_tracking->end_date = $end_date[array_rand($end_date)];
            $asset_tracking->description = $faker->text;
            $asset_tracking->model_name = $faker->lastName;
            $asset_tracking->barcode = $faker->ean13;
            $asset_tracking->category = "test";
            $asset_tracking->location = $faker->address;
            $asset_tracking->lat = $faker->latitude(-90, 90);
            $asset_tracking->lng = $faker->latitude(-180, 180);
            $asset_tracking->area = "test area";
            $asset_tracking->parent_id = NULL;
            $asset_tracking->wroker = $faker->firstNameFemale;
            $asset_tracking->additional_wroker = NULL;
            $asset_tracking->team = $faker->company;
            $asset_tracking->vendors = $faker->jobTitle;
            $asset_tracking->purchase_date = $start_date[array_rand($start_date)];
            $asset_tracking->placed_in_service = $end_date[array_rand($end_date)];
            $asset_tracking->purchase_price = $faker->randomNumber(2);
            $asset_tracking->warranty_expiration = $start_date[array_rand($start_date)];
            $asset_tracking->residual_price = $faker->randomNumber(2);
            $asset_tracking->additional_information = NULL;
            $asset_tracking->useful_life = $end_date[array_rand($end_date)];
            $asset_tracking->save();
        }
    }
}
