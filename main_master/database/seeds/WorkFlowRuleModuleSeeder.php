<?php

use Illuminate\Database\Seeder;
use App\Model\WorkFlowRuleModule;

class WorkFlowRuleModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = ['Customer', 'Item', 'Salesman', 'Journey Plan', 'Order', 'Deliviery', 'Invoice', 'Collection', 'Credit Note'];
        foreach ($modules as $key => $value) {
            $add = new WorkFlowRuleModule;
            $add->name = $value;
            $add->save();
        }
    }
}
