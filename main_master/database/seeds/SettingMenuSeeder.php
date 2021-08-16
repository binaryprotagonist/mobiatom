<?php

use App\Model\SettingMenu;
use Illuminate\Database\Seeder;

class SettingMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = array(
            "Organization",
            "Users & Roles",
            "Preferences",
            "Taxes",
            "Currency",
            "BANK",
            "Warehouse",
            "Country",
            "Region",
            "Branch/Depot",
            "Van Master",
            "Route",
            "Customer Category",
            "Credit Limits",
            "Outlet Product Code",
            "Item Group",
            "UOM"
        );

        for ($i = 1; $i < 10; $i++) {
            foreach ($data as $d) {
                $setting = new SettingMenu;
                $setting->software_id = $i;
                $setting->name = $d;
                $setting->is_active = 1;
                $setting->save();
            }
        }
    }
}
