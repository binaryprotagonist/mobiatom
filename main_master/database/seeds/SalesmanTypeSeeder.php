<?php

use App\Model\SalesmanType;
use Illuminate\Database\Seeder;

class SalesmanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $salesman_type = new SalesmanType;
        $salesman_type->name = "Salesman";
        $salesman_type->code = "ST001";
        $salesman_type->status = 1;
        $salesman_type->save();

        $salesman_type = new SalesmanType;
        $salesman_type->name = "Merchandise";
        $salesman_type->code = "ST002";
        $salesman_type->status = 1;
        $salesman_type->save();
    }
}
