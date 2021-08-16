<?php

use App\Model\SalesmanRole;
use Illuminate\Database\Seeder;

class SalesmanRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = array('Presales', 'Vansale', 'Hybrid', 'Delivery', 'Merchandiser');
        foreach ($roles as $key => $role) {
            $role = new SalesmanRole;
            // $role->code = "SALRL0000" . $key;
            $role->code = "SALRL0000";
            $role->name = $role;
            $role->status = 1;
            $role->save();
        }
    }
}
