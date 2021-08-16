<?php

use App\Model\VanCategory;
use Illuminate\Database\Seeder;

class VanCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $van_category = new VanCategory;
        $van_category->organisation_id = 2;
        $van_category->code = '1';
        $van_category->name = 'Test category';
        $van_category->status = 1;
        $van_category->save();
    }
}