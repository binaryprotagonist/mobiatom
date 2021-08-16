<?php

use App\Model\DefaultRoles;
use Illuminate\Database\Seeder;

class DefaultRolesSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default_roles = new DefaultRoles;
        $default_roles->name = 'superadmin';
        $default_roles->guard_name = 'web';
        $default_roles->save();

        $default_roles = new DefaultRoles;
        $default_roles->name = 'org-admin';
        $default_roles->guard_name = 'web';
        $default_roles->save();

        $default_roles = new DefaultRoles;
        $default_roles->name = 'manager';
        $default_roles->guard_name = 'web';
        $default_roles->save();
    }
}
