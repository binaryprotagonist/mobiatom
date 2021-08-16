<?php

use App\Model\SalesmanRole;
use Illuminate\Database\Seeder;

class SalesmanRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $salesman_role = new SalesmanRole;
        $salesman_role->code = "SR001";
        $salesman_role->name = "Presales";
        $salesman_role->status = 1;
        $salesman_role->save();

        $salesman_role = new SalesmanRole;
        $salesman_role->code = "SR002";
        $salesman_role->name = "Vansale";
        $salesman_role->status = 1;
        $salesman_role->save();

        $salesman_role = new SalesmanRole;
        $salesman_role->code = "SR003";
        $salesman_role->name = "Hybrid";
        $salesman_role->status = 1;
        $salesman_role->save();

        $salesman_role = new SalesmanRole;
        $salesman_role->code = "SR004";
        $salesman_role->name = "delivery";
        $salesman_role->status = 1;
        $salesman_role->save();

    }
}
