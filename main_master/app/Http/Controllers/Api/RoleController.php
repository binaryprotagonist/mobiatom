<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Model\OrganisationRole;
use App\Model\OrganisationRoleHasPermission;
use App\User;

class RoleController extends Controller
{
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $defaultRoles = OrganisationRole::select('id', 'uuid', 'name', 'description')
        ->with('organisationRoleHasPermissions:id,permission_id,organisation_role_id')
        ->get();

        return prepareResult(true, $defaultRoles, [], "Organisation Roles list", $this->success);
    }

    public function rolesPermission()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $defaultRoles = OrganisationRole::select('id', 'uuid', 'name', 'description')->with('organisationRoleHasPermissions:id,organisation_role_id,permission_id', 'organisationRoleHasPermissions.permission:id,name,group_id')->get();

        return prepareResult(true, $defaultRoles, [], "Organisation Roles with permissions list", $this->success);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating role", $this->unprocessableEntity);
        }

        if (is_array($request->permissions) && sizeof($request->permissions) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one permisson.", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {
            $orgRole = new OrganisationRole;
            $orgRole->name = $request->name;
            $orgRole->description = $request->description;
            $orgRole->save();
             
            foreach ($request->permissions as $permission) {
                $addRolePermission = new OrganisationRoleHasPermission;
                $addRolePermission->organisation_role_id = $orgRole->id;
                $addRolePermission->permission_id = $permission;
                $addRolePermission->save();
            }

            \DB::commit();
            return prepareResult(true, $orgRole, [], "Role added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        try {
            $organisationRole = OrganisationRole::where('uuid', $uuid)
                ->with('organisationRoleHasPermissions.permission')
                ->first();
            return prepareResult(true, $organisationRole, [], "Organisation Role Edit", $this->success);
        } catch (\Exception $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating region", $this->unprocessableEntity);
        }

        if (is_array($request->permissions) && sizeof($request->permissions) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one permisson.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $orgRole = OrganisationRole::where('uuid', $uuid)->first();
            $orgRole->name = $request->name;
            $orgRole->description = $request->description;
            $orgRole->save();

            OrganisationRoleHasPermission::whereOrganisationRoleId($orgRole->id)->delete();
            foreach ($request->permissions as $permission) {
                $addRolePermission = new OrganisationRoleHasPermission;
                $addRolePermission->organisation_role_id = $orgRole->id;
                $addRolePermission->permission_id = $permission;
                $addRolePermission->save();
            }

            /////Refresh Permissions for all users role wise
            $users = User::where('role_id', $orgRole->id)->get();
            foreach ($users as $key => $user) {
                foreach ($request->permissions as $permission) {
                    $user->givePermissionTo($permission); 
                }
            }
            \DB::commit();
            return prepareResult(true, $orgRole, [], "Role updated successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        try {
            $orgRole = OrganisationRole::where('uuid', $uuid)->delete();
            return prepareResult(true, [], [], "Role deleted successfully", $this->created);
        } catch (\Exception $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'name'  => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}