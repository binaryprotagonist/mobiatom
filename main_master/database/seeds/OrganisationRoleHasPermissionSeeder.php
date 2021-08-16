<?php

use App\Model\OrganisationRoleHasPermission;
use Illuminate\Database\Seeder;

class OrganisationRoleHasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($p = 1; $p < 354; $p++) {
            $orhp = new OrganisationRoleHasPermission;
            $orhp->organisation_role_id = 2;
            $orhp->permission_id = $p;
            $orhp->save();
        }
    }
}
