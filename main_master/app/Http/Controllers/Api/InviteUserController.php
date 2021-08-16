<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Model\InviteUser;
use App\Model\Organisation;
use App\Model\OrganisationPurchasePlan;
use App\User;
use App\Model\OrganisationRole;
use App\Model\Plan;
use App\Model\Software;
use App\Model\Subscription;
use URL;

class InviteUserController extends Controller
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

        $users = User::get();
        $user_ids = $users->pluck('id')->toArray();

        $inviteUser = InviteUser::select('id', 'uuid', 'user_id', 'invited_user_id', 'role_id', 'status')
            ->with(
                'user',
                'user.role:id,uuid,organisation_id,name,description',
                'invitedUser:id,firstname,lastname'
            )
            ->whereIn('invited_user_id', $user_ids)
            ->orderBy('id', 'desc')
            ->get();

        $org = request()->user()->organisation_id;

        $organisation = Organisation::find($org);
        $user = User::with('role:id,uuid,organisation_id,name,description')->where('organisation_id', $organisation->id)->where('usertype', 1)->first();
        $org_array = array($user->toArray());

        $inviteUser = array_merge($inviteUser->toArray(), $org_array);

        $inviteUser_array = array();
        if (is_array($inviteUser)) {
            foreach ($inviteUser as $key => $inviteUser1) {
                $inviteUser_array[] = $inviteUser[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($inviteUser_array[$offset])) {
                    $data_array[] = $inviteUser_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($inviteUser_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($inviteUser_array);
        } else {
            $data_array = $inviteUser_array;
        }

        return prepareResult(true, $data_array, [], "Invited Users listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invite user", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $subDomain = config('app.current_domain');
            $software = Software::where('slug', $subDomain)->first();
            // if (!is_object($software)) {
            //     return prepareResult(false, [], 'Your choose atlest one plan.', "Error while validating invite user", $this->unprocessableEntity);
            // }
            //
            $loginUser = request()->user();
            $organisation = $loginUser->organisation;
            //
            // $organisationPurchasePlan = $organisation->organisationPurchasePlan()->where('software_id', $software->id)->first();
            //
            // if (!is_object($organisationPurchasePlan)) {
            //     return prepareResult(false, [], 'Your choose atlest one plan.', "Error while validating invite user", $this->unprocessableEntity);
            // }
            //
            // if ($organisation->is_trial_period) {
            //     return prepareResult(false, [], 'Your in the free plan.', "Error while validating invite user", $this->unprocessableEntity);
            // }
            //
            // $sub = Subscription::where('software_id', $software->id)->first();

            $user = new User;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->password = Hash::make('123456');
            $user->organisation_id = $loginUser->organisation_id;
            $user->usertype = 4;
            $user->parent_id = $loginUser->parent_id;
            $user->country_id = $loginUser->country_id;
            $user->role_id = $request->role_id;
            $user->api_token = \Str::random(35);
            $user->mobile = (!empty($request->mobile)) ? $request->mobile : "1234567890";
            $user->save();

            $inviteUser = new InviteUser;
            $inviteUser->user_id = $user->id;
            $inviteUser->invited_user_id = $loginUser->id;
            $inviteUser->role_id = $request->role_id;
            $inviteUser->software_id = $software->id;
            $inviteUser->plan_id = 8;
            $inviteUser->save();

            $orgRole = OrganisationRole::find($request->role_id);
            foreach ($orgRole->organisationRoleHasPermissions as $key => $permission) {
                $user->givePermissionTo($permission['permission_id']);
            }

            $this->dispatch(new \App\Jobs\InviteUserJob($user));

            // $subDomain = 'vansales';
            // $software = Software::where('access_link', $subDomain)->first();

            // $OrganisationPurchasePlan = OrganisationPurchasePlan::where('software_id', $software->id)
            // ->where('organisation_id', $user->organisation_id)
            // ->first();

            // Org Set user limit

            \DB::commit();
            $inviteUser->getSaveData();
            return prepareResult(true, $user, [], "Invite user added successfully", $this->success);
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
     * @param  \App\InviteUser  $inviteUser
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating item sub category", $this->unauthorized);
        }

        $inviteUser = InviteUser::where('uuid', $uuid)->first();

        $user = User::where('id', $inviteUser->user_id)
            ->with('role')
            ->first();

        if (is_object($user)) {
            return prepareResult(true, $user, [], "User added successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\InviteUser  $inviteUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "edit");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Invited user", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $loginUser = request()->user();

            $inviteUser = InviteUser::select('id', 'uuid', 'user_id', 'invited_user_id', 'role_id')
                ->where('uuid', $request->uuid)
                ->first();

            $inviteUser->role_id = $request->role_id;
            $inviteUser->save();

            $user = $inviteUser->user;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->role_id = $request->role_id;
            $user->mobile = $request->mobile;
            $user->status = $request->status;
            $user->save();

            \DB::table('model_has_permissions')->where('model_id', $user->id)->delete();
            $orgRole = OrganisationRole::find($request->role_id);  //assigned all permission related to role

            foreach ($orgRole->organisationRoleHasPermissions as $key => $permission) {
                $user->givePermissionTo($permission['permission_id']);
            }

            \DB::commit();
            $inviteUser->getSaveData();

            return prepareResult(true, $user, [], "Invite user updated successfully", $this->success);
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
     * @param  \App\InviteUser  $inviteUser
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Invite user", $this->unauthorized);
        }

        $inviteUser = InviteUser::where('uuid', $uuid)
            ->first();

        if (is_object($inviteUser)) {
            $inviteUser->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'firstname'     => 'required',
                'lastname'     => 'required',
                'role_id'     => 'required',
                'mobile'     => 'required',
                'email' => 'required|email|unique:users,email',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "edit") {
            $validator = \Validator::make($input, [
                'role_id'     => 'required|integer|exists:organisation_roles,id',
                'firstname'     => 'required',
                'lastname'     => 'required',
                'mobile'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "passAdd") {
            $validator = \Validator::make($input, [
                'password'     => 'required|confirmed',
                'password_confirmation'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\InviteUser  $inviteUser
     * @return \Illuminate\Http\Response
     */
    public function ChangePassword(Request $request)
    {
        $input = request()->json()->all();
        $validate = $this->validations($input, "passAdd");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invite user", $this->unprocessableEntity);
        }

        $user = User::where('uuid', $request->uuid)->where('email', $request->email)->first();
        if (!$user) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            // return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $user->password = Hash::make(request()->password);
        $user->save();

        return prepareResult(true, $user, [], "Password added successfully", $this->success);
    }
}
