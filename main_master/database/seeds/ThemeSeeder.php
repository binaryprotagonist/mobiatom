<?php

use App\Model\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (explode(',', "Theme 1,Theme 2,Theme 3") as $t) {
            $theme = new Theme;
            $theme->name = $t;
            $theme->status = 1;
            $theme->save();
        }
    }
}
