<?php

use App\Model\ItemUomMaster;
use Illuminate\Database\Seeder;

class ItemUomMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $uom = array('PCS', 'CASE', 'BOX', 'KG');

        foreach ($uom as $u) {
            $item_uom = new ItemUomMaster;
            $item_uom->name = $u;
            $item_uom->status = 1;
            $item_uom->save();
        }
    }
}
