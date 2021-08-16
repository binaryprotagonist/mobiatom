<?php

use App\Model\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plan_array = array(
            "1" => array(
                '0-5 User',
                '0-20 User',
                '0-50 User',
                'Enterprise'
            ),
            "2" => array(
                '0-10 User',
                '0-20 User',
                '0-50 User',
                'Enterprise'
            ),
            "3" => array(
                '0-5 User',
                '0-20 User',
                '0-50 User',
                'Enterprise'
            ),
            "4" => array(
                '0-10 User',
                '0-20 User',
                '0-50 User',
                'Enterprise'
            ),
            "5" => array(
                '0-5 User',
                '0-20 User',
                '0-50 User',
                'Enterprise'
            ),
            "6" => array(
                '0-5 User',
                '0-20 User',
                '0-50 User',
                'Enterprise'
            )
        );

        foreach ($plan_array as $key => $plans) {
            foreach ($plans as $plan_name) {
                $paln = new Plan;
                $paln->software_id = $key;
                $paln->name = $plan_name;
                $paln->current_price = 120.00;
                $paln->is_active = 1;
                $paln->save();
            }
        }
    }
}
