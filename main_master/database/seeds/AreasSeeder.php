<?php

use App\Model\Area;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $area = new Area;
        $area->organisation_id = 2;
        $area->area_name = $faker->name;
        $area->area_code = '1';
        $area->parent_id = "";
        $area->node_level = "";
        $area->status = 1;
        $area->save();

        $area = new Area;
        $area->organisation_id = 2;
        $area->area_name = $faker->name;
        $area->area_code = '1';
        $area->parent_id = "";
        $area->node_level = "";
        $area->status = 1;
        $area->save();

        // $plan_array = array(
        //     "Bread" => array(
        //         'Sliced Bread',
        //         'Arabic Bread',
        //         'Bun',
        //         'Rolls'
        //     ),
        //     "Cheese" => array(
        //         'Portion'
        //     ),
        //     "Cond" => array(
        //         'Tomato Paste'
        //     ),
        //     "Cream" => array(
        //         'Regular'
        //     ),
        //     "Lbneh" => array(
        //         'Plain'
        //     ),
        //     "Smooth" => array(
        //         'Flavored'
        //     ),
        //     "JNSD" => array(
        //         'Drink',
        //         'Juice',
        //         'Juice 100%',
        //         'Nectar'
        //     ),
        //     "Pastry" => array(
        //         'Cup Cake',
        //         'Sliced Cake',
        //         'Croissant'
        //     ),
        //     "Lban" => array(
        //         'Plain',
        //         'Mango',
        //         'Camel Laban',
        //         'Strawberry',
        //         'Flavored'
        //     ),
        //     "Milk" => array(
        //         'Camel Milk',
        //         'Plain',
        //         'Full Cream',
        //         'Flavored Milk',
        //         'Flavored LF',
        //         'Flavored'
        //     ),
        //     "Sparkling Water" => array(
        //         'Plain',
        //         'Flavored'
        //     ),
        //     "Still Water" => array(
        //         'Plain',
        //         'One-Way'
        //     ),
        //     "Yog" => array(
        //         'Plain'
        //     )
        // );
        //
        // foreach ($plan_array as $key => $plans) {
        //     $category = ItemMajorCategory::where('name', $key)->first();
        //     foreach ($plans as $plan_name) {
        //         $paln = new ItemMajorCategory;
        //         $paln->uuid = Str::random(35);
        //         $paln->organisation_id = 1;
        //         $paln->parent_id = $category->id;
        //         $paln->name = $plan_name;
        //         $paln->node_level = 1;
        //         $paln->status = 1;
        //         $paln->save();
        //     }
        // }
    }
}
