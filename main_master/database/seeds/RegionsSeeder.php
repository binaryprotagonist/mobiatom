<?php

use App\Model\Region;
use Illuminate\Database\Seeder;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $region = new Region;
        $region->organisation_id = 2;
        $region->country_id = 1;
        $region->region_code = "1";
        $region->region_name = "East Zone";
        $region->region_status = 1;
        $region->save();
    }
}