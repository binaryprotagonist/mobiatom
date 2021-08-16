<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Model\OrganisationRole;
use App\Model\OrganisationRoleHasPermission;
use App\User;

class UserPermissionController extends Controller
{
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $users = User::with('permissions:id,name')->get();

        return prepareResult(true, $users, [], "User list with permissions.", $this->success);
    }

    public function userAssignedRole(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "assigned-role");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating outlet product code", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $user = User::where('uuid', $uuid)->first();
            $user->role_id = $request->role_id;
            $user->save();
            if($user)
            {
            	\DB::table('model_has_permissions')->where('model_id',$user->id)->delete();
            	$orgRole = OrganisationRole::find($request->role_id);  //assigned all permission related to role
	            foreach ($orgRole->organisationRoleHasPermissions as $key => $permission) {
	            	$user->givePermissionTo($permission['permission_id']); 
	            }
            }
            \DB::commit();
            return prepareResult(true, $user, [], "Role assigned successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function userAssignedCustomPermission(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->permissions) && sizeof($request->permissions) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one permission.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $user = User::where('uuid', $uuid)->first();
            if($user)
            {
            	\DB::table('model_has_permissions')->where('model_id',$user->id)->delete();
	            foreach ($request->permissions as $key => $permission) {
	            	$user->givePermissionTo($permission); 
	            }
            }
            \DB::commit();
            return prepareResult(true, $user, [], "Custom permission assigned successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }


    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "assigned-role") {
            $validator = \Validator::make($input, [
                'role_id' => 'required|integer|exists:organisation_roles,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
