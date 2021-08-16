<?php

use App\Model\Van;
use Illuminate\Database\Seeder;

class VanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $van_type = new Van;
        $van_type->organisation_id = 2;
        $van_type->van_code = 'VN0001';
        $van_type->plate_number = '1';
        $van_type->description = 'loream content';
        $van_type->capacity = 5;
        $van_type->van_type_id = 1;
        $van_type->van_category_id = 1;
        $van_type->van_status = 1;
        $van_type->save();
    }
}