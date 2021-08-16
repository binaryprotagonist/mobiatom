<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Model\PermissionGroup;
use Illuminate\Support\Facades\DB;

class DefaultRolePermissionController extends Controller
{
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $defaultRoles = Role::select('id', 'name')->whereNotIn('name', ['superadmin', 'admin'])->get();

        return prepareResult(true, $defaultRoles, [], "Default Roles list", $this->success);
    }

    public function rolesPermission()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $defaultRoles = Role::select('id', 'name')->whereNotIn('name', ['superadmin', 'admin'])->with('permissions:id,name,group_id')->get();

        return prepareResult(true, $defaultRoles, [], "Default Roles with permissions list", $this->success);
    }



    public function groupPermissions()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $allPermissions = PermissionGroup::select('id', 'name', 'module_name')
            ->whereNotIn('id', ['1', '2'])
            ->with('permissions:id,name,group_id')
            ->orderBy('module_name', 'asc')
            ->get();

        $data = array();
        foreach ($allPermissions as $key => $permissions) {
            if ($permissions->module_name == "Dashboard") {
                $data['dashboard']['module'] = $permissions->module_name;
                $data['dashboard']['submodules'][] = $permissions;
            }

            if ($permissions->module_name == "Master") {
                $data['master']['module'] = $permissions->module_name;
                $data['master']['submodules'][] = $permissions;
            }

            if ($permissions->module_name == "Reports") {
                $data['reports']['module'] = $permissions->module_name;
                $data['reports']['submodules'][] = $permissions;
            }
        }

        return prepareResult(true, array($data), [], "Group wise permission list", $this->success);
    }


    public function permissions()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $defaultRoles = Permission::select('id', 'name')
            ->whereNotIn('group_id', ['1', '2'])
            ->get();

        return prepareResult(true, $defaultRoles, [], "All permissions list", $this->success);
    }

    public function permissionSingle(Request $request)
    {
        $user_info = $request->user();

        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $usersPermissions = DB::table('users as us')
            ->join('organisation_role_has_permissions as orhp', 'us.role_id', '=', 'orhp.organisation_role_id')
            ->join('default_permissions as dp', 'orhp.permission_id', '=', 'dp.id')
            ->join('permission_groups as pg', 'dp.group_id', '=', 'pg.id')
            ->select('dp.id', 'dp.name AS permission', 'pg.name AS permission_group')
            ->where("email", $user_info->email)
            ->groupBy("dp.id")
            ->orderBy("pg.id")
            ->get();

        $permissionsData = array();
        $counter = $loop = 0;

        foreach ($usersPermissions as $permission) {
            if ($permission->permission_group == "salesmans" && config('app.current_domain') == "merchandising") {
                $permissionsData[$counter]['moduleName'] = "merchandising";
                $permissionsData[$counter]['permissions'][]["name"] = $permission->permission;
            } else {
                $permissionsData[$counter]['moduleName'] = $permission->permission_group;
                $permissionsData[$counter]['permissions'][]["name"] = $permission->permission;
            }

            if (isset($usersPermissions[$loop + 1]) and $usersPermissions[$loop + 1]->permission_group != $permission->permission_group)
                $counter++;
            $loop++;
        }

        return $permissionsData;
    }
}
