<?php

use App\Model\Route;
use Illuminate\Database\Seeder;

class RoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $route = new Route;
        $route->organisation_id = 2;
        $route->area_id = 1;
        $route->route_code = '1';
        $route->route_name = $faker->name;
        $route->status = 1;
        $route->save();
    }
}