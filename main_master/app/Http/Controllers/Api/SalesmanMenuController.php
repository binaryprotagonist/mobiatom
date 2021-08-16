<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SalesmanRoleMenu;
use App\Model\SalesmanRoleMenuDefault;
use Illuminate\Http\Request;

class SalesmanMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $salesman_role_menu_default = SalesmanRoleMenuDefault::select('id', 'salesman_role_id', 'menu_id')->with('roleMenu:id,name')->get();
        $data = $salesman_role_menu_default;

        $salesman_role_menu = SalesmanRoleMenu::select('id', 'salesman_role_id', 'menu_id', 'is_active')->with('roleMenu:id,name')->get();
        if ($salesman_role_menu->count()) {
            $data = $salesman_role_menu;
        }

        return prepareResult(true, $data, [], "Salesman menu listing", $this->success);
    }

    public function roleChange(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $salesman_role_menu = SalesmanRoleMenu::where('salesman_role_id', $request->salesman_role_id)
            ->where('id', $request->id)
            ->first();

        if (is_object($salesman_role_menu)) {
            $salesman_role_menu->is_active = $request->is_active;
            $salesman_role_menu->save();

            return prepareResult(true, $salesman_role_menu, [], "Salesman menu updated", $this->success);
        }

        $salesman_role_menu_default = SalesmanRoleMenuDefault::where('salesman_role_id', $request->salesman_role_id)->get();

        if (count($salesman_role_menu_default)) {
            foreach ($salesman_role_menu_default as $srmd) {
                $srm = new SalesmanRoleMenu;
                $srm->organisation_id = $request->user()->organisation_id;
                $srm->salesman_role_id = $request->salesman_role_id;
                $srm->menu_id = $srmd->menu_id;
                if ($srmd->id == $request->id) {
                    $srm->is_active = $request->is_active;
                } else {
                    $srm->is_active = 1;
                }
                $srm->save();
            }
        }
        return prepareResult(true, [], [], "Salesman menu updated", $this->success);
    }
}
