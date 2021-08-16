<?php

use App\Model\Software;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class SoftwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $software_name = array('Pre Sale', 'Vansales Hybrid', 'Merchandising', 'Inventory', 'Loyality Management', 'Forecast', 'WOM', 'Aseet Tracking', 'Invoice');

        foreach ($software_name as $name) {
            $software = new Software;
            $software->name = $name;
            $software->details = "This is our " . $name;
            $software->access_link = "";
            $software->slug = Str::slug($name, '-');
            $software->status = "1";
            $software->save();
        }
    }
}
