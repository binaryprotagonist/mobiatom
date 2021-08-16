<?php

use App\Model\VanType;
use Illuminate\Database\Seeder;

class VanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $van_type = new VanType;
        $van_type->organisation_id = 2;
        $van_type->code = '1';
        $van_type->type = 'Test type';
        $van_type->status = 1;
        $van_type->save();
    }
}