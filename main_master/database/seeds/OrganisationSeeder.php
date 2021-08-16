<?php

use Illuminate\Database\Seeder;
use App\Model\Organisation;
use App\Model\CountryMaster;
use App\Model\OrganisationRole;

class OrganisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('organisations')->delete();
        $organisation = new Organisation();
        $organisation->org_name          = 'NFPC';
        $organisation->org_company_id      = '1';
        $organisation->org_tax_id      = '78943729020';
        $organisation->org_street1      = 'Dubai';
        $organisation->org_street2      = 'Dubai';
        $organisation->org_city         = 'Dubai';
        $organisation->org_state         = 'Dubai';
        $organisation->org_country_id     = CountryMaster::whereName('United Arab Emirates')->first()->id;
        $organisation->org_postal          = 181529;
        $organisation->org_phone           = '507844941';
        $organisation->org_contact_person     = 'Mr.Sundar';
        $organisation->org_contact_person_number    = '507844941';
        $organisation->org_currency       = 'AED';
        $organisation->org_fasical_year       = 'April - March';
        $organisation->gstin_number       = '';
        $organisation->gst_reg_date       = date('Y-m-d');
        $organisation->org_currency       = 'AED';
        $organisation->org_fasical_year       = 'April - March';
        $organisation->gstin_number       = '';
        $organisation->gst_reg_date       = date('Y-m-d');
        $organisation->is_auto_approval_set       = 1;
        $organisation->org_status       = 1;
        $organisation->is_trial_period       = 1;
        $organisation->save();

        $createRole = new OrganisationRole;
        $createRole->organisation_id = $organisation->id;
        $createRole->name = 'superadmin';
        $createRole->save();

        $roles = array('org-admin', 'NSM', 'ASM', 'Supervisor', 'manager');
        $organisation_id = $organisation->id;
        collect($roles)->each(function ($role, $key) use ($organisation_id) {
            $createRole = new OrganisationRole;
            $createRole->organisation_id = $organisation_id;
            $createRole->name = $role;
            $createRole->save();
        });


        // $organisation = new Organisation();
        // $organisation->org_name          = 'Test';
        // $organisation->org_company_id      = '1';
        // $organisation->org_tax_id      = '5';
        // $organisation->org_street1      = 'India';
        // $organisation->org_street2      = '';
        // $organisation->org_city         = 'Ahemdabad';
        // $organisation->org_state         = 'Gujarat';
        // $organisation->org_country_id     = CountryMaster::whereName('India')->first()->id;
        // $organisation->org_postal          = 462026;
        // $organisation->org_phone           = '9876543210';
        // $organisation->org_contact_person     = 'Hardik';
        // $organisation->org_contact_person_number    = '9876543210';
        // $organisation->org_currency       = 'USD';
        // $organisation->org_fasical_year       = '2020';
        // $organisation->gstin_number       = '123456';
        // $organisation->gst_reg_date       = date('Y-m-d');
        // $organisation->save();

        // $createRole = new OrganisationRole;
        // $createRole->organisation_id = $organisation->id;
        // $createRole->name = 'admin';
        // $createRole->save();

        // $createRole = new OrganisationRole;
        // $createRole->organisation_id = $organisation->id;
        // $createRole->name = 'org-admin';
        // $createRole->save();
    }
}
