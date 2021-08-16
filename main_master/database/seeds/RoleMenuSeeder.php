<?php

use App\Model\RoleMenu;
use Illuminate\Database\Seeder;

class RoleMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = array('sales', 'order', 'delivery', 'collection', 'return', 'merchandiser');
        foreach ($roles as $key => $role) {
            $role = new RoleMenu;
            // $role->code = "SALRL0000" . $key;
            $role->name = $role;
            $role->status = 1;
            $role->save();
        }
    }
}
