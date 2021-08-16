<?php

use App\Model\SalesmanRoleMenuDefault;
use Illuminate\Database\Seeder;

class SalesmanRoleMenuDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $srmd = array(
            "1" => array(
                array(
                    "manu_id" => 1,
                    "is_active" => 0
                ),
                array(
                    "manu_id" => 2,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 3,
                    "is_active" => 0
                ),
                array(
                    "manu_id" => 4,
                    "is_active" => 0
                ),
                array(
                    "manu_id" => 5,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 6,
                    "is_active" => 0
                )
            ),
            "2" => array(
                array(
                    "manu_id" => 1,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 2,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 3,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 4,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 5,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 6,
                    "is_active" => 0
                )
            ),
            "3" => array(
                array(
                    "manu_id" => 1,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 2,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 3,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 4,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 5,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 6,
                    "is_active" => 1
                )
            ),
            "4" => array(
                array(
                    "manu_id" => 1,
                    "is_active" => 0
                ),
                array(
                    "manu_id" => 2,
                    "is_active" => 0
                ),
                array(
                    "manu_id" => 3,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 4,
                    "is_active" => 1
                ),
                array(
                    "manu_id" => 5,
                    "is_active" => 0
                ),
                array(
                    "manu_id" => 6,
                    "is_active" => 0
                )
            ),
        );

        foreach ($srmd as $key => $s) {
            foreach ($s as $role) {
                $salesman_role_menu_defaults = new SalesmanRoleMenuDefault;
                $salesman_role_menu_defaults->salesman_role_id = $key;
                $salesman_role_menu_defaults->menu_id = $role['manu_id'];
                $salesman_role_menu_defaults->is_active = $role['is_active'];
                $salesman_role_menu_defaults->save();
            }
        }

    }
}
