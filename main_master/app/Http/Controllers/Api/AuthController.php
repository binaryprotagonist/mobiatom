<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Currency;
use App\Model\CurrencyMaster;
use App\Model\DecimalRate;
use App\Model\DeviceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Validator;
use App\Model\LoginLog;
use App\Model\Organisation;
use App\Model\SalesmanLoginLog;
use App\Model\SalesmanTripInfos;
use App\Model\SalesmanNumberRange;
use App\Model\SalesmanRoleMenu;
use App\Model\SalesmanRoleMenuDefault;
use App\Model\Verification;
use App\User;
use Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "login") {
            $validator = Validator::make($input, [
                'email'     => 'required|email|max:255',
                'password'     => 'required',
                'remember_me' => 'boolean'
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "verification") {
            $validator = Validator::make($input, [
                'token'     => 'required',
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "slogin") {
            $validator = Validator::make($input, [
                'email'     => 'required|email|max:255',
                'type'     => 'required'
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "signup") {
            $validator = Validator::make($input, [
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'email' => 'required|regex:/(.+)@(.+)\.(.+)/i|string|max:255|unique:users',
                // 'password' => 'required|string|confirmed',
                'mobile' => 'required|numeric|min:10',
                'country_id' => 'required'
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $user = getUser();
        if (is_object($user)) {
            return prepareResult(false, $user, [], "User not authenticate", $this->unauthorized);
        }

        $input      = $request->json()->all();
        $validate     = $this->validations($input, "signup");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating signup", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $user = new User;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->password = \Hash::make($request->password);
            $user->api_token = \Str::random(35);
            $user->mobile = $request->mobile;
            $user->country_id = $request->country_id;
            $user->login_type = $request->login_type;
            $user->status = 0;
            $user->save();

            if ($user) {
                $roles = Role::whereName('org-admin')->orderBy('id', 'ASC')->first(); //assigned org-roles all permission
                foreach ($roles->permissions as $key => $permission) {
                    $user->givePermissionTo($permission->name);
                }
            }

            // Send mail
            $this->dispatch(new \App\Jobs\NewUserRegisterJob($user));

            \DB::commit();
            return prepareResult(true, ["accessToken" => $user->createToken('authLoginToken')->accessToken, 'user_info' => $user, 'permissions-name' => $user->getPermissionNames()], [], "User created successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     * @return [object] user
     */
    public function login(Request $request)
    {
        $input      = $request->json()->all();
        $validate     = $this->validations($input, "login");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating user", $this->unprocessableEntity);
        }

        $user = User::where("email", $input['email'])
            ->first();

        if ($user) {
            if (!isset($user->organisation_id) && $user->organisation_id) {
                return prepareResult(false, [], "You are not completed the verification process please completed your verification process first.", "User not found", $this->bed_request);
            }
            if (Hash::check($input['password'], $user->password)) {
                if ($user->status == 1) {
                    LoginLog::create([
                        'user_id'   => $user->id,
                        'ip'        => $request->ip()
                    ]);

                    DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
                    $usersPermissions = DB::table('users as us')
                        ->join('organisation_role_has_permissions as orhp', 'us.role_id', '=', 'orhp.organisation_role_id')
                        ->join('default_permissions as dp', 'orhp.permission_id', '=', 'dp.id')
                        ->join('permission_groups as pg', 'dp.group_id', '=', 'pg.id')
                        ->select('dp.id', 'dp.name AS permission', 'pg.name AS permission_group')
                        ->where("email", $user->email)
                        ->groupBy("dp.id")
                        ->orderBy("pg.id")
                        ->get();

                    $permissionsData = array();
                    $counter = $loop = 0;
                    foreach ($usersPermissions as $permission) {
                        if ($permission->permission_group == "salesmans") {
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

                    return prepareResult(true, ["accessToken" => $user->createToken('authLoginToken')
                        ->accessToken, 'user_info' => User::with('role:id,name', 'organisation:id,org_name,org_logo,is_trial_period')->find($user->id), 'permissions-name' => $permissionsData], [], "User Logged in successfully", $this->success);
                } else {
                    return prepareResult(false, [], ["Inactivated" => "Your account is temporarily deactivated."], "Your account is temporarily deactivated, Please contact to admin.", $this->unauthorized);
                }
            } else {
                return prepareResult(false, [], ["password" => "Wrong passowrd"], "Password not matched", $this->unauthorized);
            }
        } else {
            return prepareResult(false, [], ["email" => "Unable to find user"], "User not found", $this->bed_request);
        }
    }

    /**
     * Supervisor Login and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     * @return [object] user
     */
    public function supervisorLogin(Request $request)
    {
        $input      = $request->json()->all();
        $validate     = $this->validations($input, "login");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating user", $this->unprocessableEntity);
        }

        $user = User::where("email", $input['email'])
            ->first();

        if ($user) {
            if (Hash::check($input['password'], $user->password)) {
                if ($user->status == 1) {
                    LoginLog::create([
                        'user_id'   => $user->id,
                        'ip'        => $request->ip()
                    ]);

                    $device_detail = new DeviceDetail;
                    $device_detail->user_id       = $user->id;
                    $device_detail->ip            = $request->ip();
                    $device_detail->organisation_id = $user->organisation_id;
                    $device_detail->device_token  = $request->device_token;
                    $device_detail->device_name   = $request->device_name;
                    $device_detail->type   = "supervisor";
                    $device_detail->save();

                    DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
                    $usersPermissions = DB::table('users as us')
                        ->join('organisation_role_has_permissions as orhp', 'us.role_id', '=', 'orhp.organisation_role_id')
                        ->join('default_permissions as dp', 'orhp.permission_id', '=', 'dp.id')
                        ->join('permission_groups as pg', 'dp.group_id', '=', 'pg.id')
                        ->select('dp.id', 'dp.name AS permission', 'pg.name AS permission_group')
                        ->where("email", $user->email)
                        ->groupBy("dp.id")
                        ->orderBy("pg.id")
                        ->get();

                    $permissionsData = array();
                    $counter = $loop = 0;
                    foreach ($usersPermissions as $permission) {
                        if ($permission->permission_group == "salesmans") {
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

                    return prepareResult(true, ["accessToken" => $user->createToken('authLoginToken')
                        ->accessToken, 'user_info' => User::with('role:id,name', 'organisation:id,org_name,org_logo,is_trial_period')->find($user->id), 'permissions-name' => $permissionsData], [], "User Logged in successfully", $this->success);
                } else {
                    return prepareResult(false, [], ["Inactivated" => "Your account is temporarily deactivated."], "Your account is temporarily deactivated, Please contact to admin.", $this->unauthorized);
                }
            } else {
                return prepareResult(false, [], ["password" => "Wrong passowrd"], "Password not matched", $this->unauthorized);
            }
        } else {
            return prepareResult(false, [], ["email" => "Unable to find user"], "User not found", $this->bed_request);
        }
    }

    /**
     * SalesmanLogin user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     * @return [object] user
     */
    public function salesmanLogin(Request $request)
    {
        $input      = $request->json()->all();
        $validate     = $this->validations($input, "salesman_login");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating salesman", $this->unprocessableEntity);
        }

        $user = User::where('email', $input['email'])
            ->first();

        if ($user) {
            if (Hash::check($input['password'], $user->password)) {
                $salesmanInfo = $user->salesmanInfo;

                if (
                    ($salesmanInfo->block_start_date <= date('Y-m-d')) &&
                    ($salesmanInfo->block_end_date >= date('Y-m-d'))
                ) {
                    return prepareResult(false, [], ["Inactivated" => "Your account is temporarily blocked."], "Your account is temporarily blocked, Please contact to admin.", $this->unauthorized);
                }

                if ($user->status == 1 && $salesmanInfo->status == 1) {
                    LoginLog::create([
                        'user_id'   => $user->id,
                        'ip'        => $request->ip()
                    ]);

                    $salesman_login = new SalesmanLoginLog;
                    $salesman_login->user_id       = $user->id;
                    $salesman_login->ip            = $request->ip();
                    $salesman_login->organisation_id = $user->organisation_id;
                    $salesman_login->device_token  = $request->device_token;
                    $salesman_login->vesion       = $request->vesion;
                    $salesman_login->device_name   = $request->device_name;
                    $salesman_login->imei_number   = $request->imei_number;
                    $salesman_login->save();

                    $salesman_info = new SalesmanTripInfos;
                    $salesman_info->salesman_id = $user->id;
                    $salesman_info->save();

                    $device_detail = new DeviceDetail;
                    $device_detail->user_id       = $user->id;
                    $device_detail->ip            = $request->ip();
                    $device_detail->organisation_id = $user->organisation_id;
                    $device_detail->device_token  = $request->device_token;
                    $device_detail->device_name   = $request->device_name;
                    $device_detail->type   = "salesman";
                    $device_detail->save();

                    $user = User::with(
                        'role:id,name',
                        'organisation:id,org_name,org_logo,org_street1,org_street2,org_city,org_state,org_country_id,org_postal',
                        'organisation.countryInfo:id,name',
                        'salesmanInfo:id,user_id,route_id,salesman_type_id,salesman_role_id,profile_image,salesman_code,salesman_supervisor',
                        'salesmanInfo.salesmanSupervisor:id,firstname,lastname,email',
                        'salesmanInfo.route:id,uuid,area_id,route_code,area_id,depot_id,route_name',
                        'salesmanInfo.salesmanType:id,name',
                        'salesmanInfo.salesmanRole:id,name',
                        'salesmanInfo.salesmanlob',
                        'salesmanInfo.salesmanlobget'
                    )->find($user->id);

                    $salesman_number_range = SalesmanNumberRange::where('salesman_id', $user->salesmanInfo->id)
                        ->first();

                    $srm = SalesmanRoleMenu::select('id', 'salesman_role_id', 'menu_id', 'is_active')
                        ->with('roleMenu:id,name')
                        ->where('salesman_role_id', $user->salesmanInfo->salesman_role_id)
                        ->get();

                    if (count($srm)) {
                        $data = $srm;
                    } else {
                        $data = SalesmanRoleMenuDefault::select('id', 'salesman_role_id', 'menu_id', 'is_active')
                            ->with('roleMenu:id,name')
                            ->where('salesman_role_id', $user->salesmanInfo->salesman_role_id)
                            ->get();
                    }

                    // $Rolemenu = RoleMenu::select('id', 'name')
                    //     ->get();
                    // if (is_object($Rolemenu)) {
                    //     foreach ($Rolemenu as $key => $menu) {
                    //         $Userrolemenu = Userrolemenu::where('role_id', $user->salesmanInfo->salesman_role_id)
                    //             ->where('menu_id', $menu->id)
                    //             ->first();
                    //         if (!is_object($Userrolemenu)) {
                    //             unset($Rolemenu[$key]);
                    //         }
                    //     }
                    // }

                    // $salesmanType = SalesmanType::find($user->salesmanInfo->salesman_type_id);

                    $currency = '';
                    $seleced_currency = Currency::where('default_currency', 1)->first();
                    if (is_object($seleced_currency)) {
                        $currency = $seleced_currency;
                    } else {
                        $organisation = Organisation::find($user->organisation->id);
                        $org_currency = $organisation->org_currency;
                        $currency = CurrencyMaster::where('code', $org_currency)->first();
                    }
                    $Decimalrate = DecimalRate::where('organisation_id', $user->organisation->id)->first();

                    return prepareResult(true, [
                        "accessToken" => $user->createToken('authLoginToken')
                            ->accessToken, 'user_info' => $user,
                        'salesman_number_range' => $salesman_number_range,
                        'active_menu' => $data,
                        'currency' => $currency,
                        'decimal_rate' => (is_object($Decimalrate)) ? $Decimalrate->decimal_rate : 2
                        // 'salesman_type' => $salesmanType
                    ], [], "Salesman Logged in successfully", $this->success);
                } else {
                    return prepareResult(false, [], ["Inactivated" => "Your account is temporarily deactivated."], "Your account is temporarily deactivated, Please contact to admin.", $this->unauthorized);
                }
            } else {
                return prepareResult(false, [], ["password" => "Wrong passowrd"], "Password not matched", $this->unauthorized);
            }
        } else {
            return prepareResult(false, [], ["email" => "Unable to find user"], "User not found", $this->bed_request);
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return prepareResult(true, [], [], "Successfully logged out", $this->success);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function userDetail(Request $request)
    {
        $accessToken = $request->header('Authorization');

        $user = Auth::user();
        if (isset($user->organisation_id) && $user->organisation_id) {
            $user->organisation_trim;
        }

        return prepareResult(true, $user, [], "user detail", $this->success);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     * @return [object] user
     */
    public function socialMedialogin(Request $request)
    {
        $input      = $request->json()->all();
        $validate     = $this->validations($input, "slogin");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating user", $this->unprocessableEntity);
        }

        $user = User::where("email", $input['email'])
            ->where('login_type', $input['type'])
            ->first();

        if ($user) {
            if ($user->status == 1) {
                LoginLog::create([
                    'user_id'   => $user->id,
                    'ip'        => $request->ip()
                ]);

                DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
                $usersPermissions = DB::table('users as us')
                    ->join('organisation_role_has_permissions as orhp', 'us.role_id', '=', 'orhp.organisation_role_id')
                    ->join('default_permissions as dp', 'orhp.permission_id', '=', 'dp.id')
                    ->join('permission_groups as pg', 'dp.group_id', '=', 'pg.id')
                    ->select('dp.id', 'dp.name AS permission', 'pg.name AS permission_group')
                    ->where("email", $user->email)
                    ->groupBy("dp.id")
                    ->orderBy("pg.id")
                    ->get();

                $permissionsData = array();
                $counter = $loop = 0;
                foreach ($usersPermissions as $permission) {
                    $permissionsData[$counter]['moduleName'] = $permission->permission_group;
                    $permissionsData[$counter]['permissions'][]["name"] = $permission->permission;
                    if (isset($usersPermissions[$loop + 1]) and $usersPermissions[$loop + 1]->permission_group != $permission->permission_group)
                        $counter++;
                    $loop++;
                }

                return prepareResult(true, ["accessToken" => $user->createToken('authLoginToken')
                    ->accessToken, 'user_info' => User::with('role:id,name', 'organisation:id,org_name,org_logo,is_trial_period')->find($user->id), 'permissions-name' => $permissionsData], [], "User Logged in successfully", $this->success);
            } else {
                return prepareResult(false, [], ["Inactivated" => "Your account is temporarily deactivated."], "Your account is temporarily deactivated, Please contact to admin.", $this->unauthorized);
            }
        } else {
            return prepareResult(false, [], ["email" => "Unable to find user"], "User not found", $this->bed_request);
        }
    }

    public function userVerification(Request $request)
    {
        if ($this->isAuthorized) {
            // 404
            return prepareResult(false, [], [], "User already verified", $this->not_found);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "verification");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating verification", $this->success);
        }

        $token = $request->token;
        $verify = Verification::where('token', $token)->first();

        if (!is_object($verify)) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $user = User::where('email', $verify->email)->first();
        $user->status = 1;
        $user->email_verified_at = date('Y-m-d H:m:s');
        $user->save();

        $verify->delete();

        if ($user) {
            if ($user->status == 1) {
                LoginLog::create([
                    'user_id'   => $user->id,
                    'ip'        => $request->ip()
                ]);

                DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
                $usersPermissions = DB::table('users as us')
                    ->join('organisation_role_has_permissions as orhp', 'us.role_id', '=', 'orhp.organisation_role_id')
                    ->join('default_permissions as dp', 'orhp.permission_id', '=', 'dp.id')
                    ->join('permission_groups as pg', 'dp.group_id', '=', 'pg.id')
                    ->select('dp.id', 'dp.name AS permission', 'pg.name AS permission_group')
                    ->where("email", $user->email)
                    ->groupBy("dp.id")
                    ->orderBy("pg.id")
                    ->get();

                $permissionsData = array();
                $counter = $loop = 0;
                foreach ($usersPermissions as $permission) {
                    $permissionsData[$counter]['moduleName'] = $permission->permission_group;
                    $permissionsData[$counter]['permissions'][]["name"] = $permission->permission;
                    if (isset($usersPermissions[$loop + 1]) and $usersPermissions[$loop + 1]->permission_group != $permission->permission_group)
                        $counter++;
                    $loop++;
                }

                return prepareResult(true, ["accessToken" => $user->createToken('authLoginToken')
                    ->accessToken, 'user_info' => User::with('role:id,name', 'organisation:id,org_name,org_logo,is_trial_period')->find($user->id), 'permissions-name' => $permissionsData], [], "User Logged in successfully", $this->success);
            } else {
                return prepareResult(false, [], ["Inactivated" => "Your account is temporarily deactivated."], "Your account is temporarily deactivated, Please contact to admin.", $this->unauthorized);
            }
        } else {
            return prepareResult(false, [], ["email" => "Unable to find user"], "User not found", $this->bed_request);
        }
    }
}
