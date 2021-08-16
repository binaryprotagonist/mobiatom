<?php

use App\Model\Module;
use App\Model\ModuleMaster;
use Illuminate\Database\Seeder;

class ModuleMastersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $module_names = array(
            'Country',
            'Region',
            'Branch/Depot',
            'Van Master',
            'Customer Category',
            'Items',
            'UOM'
        );

        foreach ($module_names as $name) {
            $module_master = new ModuleMaster;
            $module_master->module_name = $name;
            $module_master->custom_field_status = 0;
            $module_master->status = 1;
            $module_master->save();
        }
    }
}
