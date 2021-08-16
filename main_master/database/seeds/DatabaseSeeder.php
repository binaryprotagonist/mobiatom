<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //\DB::table('country_masters')->delete();
        //\DB::table('organisations')->delete();
        //\DB::table('users')->delete();
        \DB::unprepared(file_get_contents(storage_path('backups/country_masters.sql')));
        $this->call(OrganisationSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ThemeSeeder::class);
        $this->call(TemplateSeeder::class);
        $this->call(SurveyTypeSeeder::class);
        $this->call(SalesmanRoleSeeder::class);
        $this->call(RoleMenuSeeder::class);

        // add the survey type , salesman role - role_menu, salesman_role_menu_defalt,

        // $this->call(CountriesSeeder::class);
        // $this->call(RegionsSeeder::class);
        // $this->call(AreasSeeder::class);
        // $this->call(DepotsSeeder::class);
        // $this->call(SalesOrganisationsSeeder::class);
        // $this->call(ChannelsSeeder::class);
        // $this->call(RoutesSeeder::class);
        // $this->call(BrandsSeeder::class);
        // $this->call(VanTypeSeeder::class);
        // $this->call(VanCategoriesSeeder::class);
        // $this->call(VanSeeder::class);
        // $this->call(WorkFlowRuleModuleSeeder::class);

        //$this->call(DummyContentSeeder::class);
    }
}
