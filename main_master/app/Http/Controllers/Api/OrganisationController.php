<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CountryMaster;
use App\Model\Currency;
use App\Model\CurrencyMaster;
use Illuminate\Http\Request;
use App\Model\Organisation;
use Spatie\Permission\Models\Role;
use App\Model\OrganisationRole;
use App\Model\OrganisationRoleHasPermission;
use App\Model\PlanHistory;
use App\Model\PlanInvoice;
use App\Model\Software;
use App\Model\Subscription;
use App\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Support\Str;
// use PhpParser\Builder\Function_;
use App\Model\UserCreditLimit;

class OrganisationController extends Controller
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

        $organisation = Organisation::where('id', $this->user->organisation_id)
            // ->where('user', )
            ->orderBy('id', 'desc')
            ->get();
        $organisation_array = array();
        if (is_object($organisation)) {
            foreach ($organisation as $key => $organisation1) {
                $organisation_array[] = $organisation[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($organisation_array[$offset])) {
                    $data_array[] = $organisation_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($organisation_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($organisation_array);
        } else {
            $data_array = $organisation_array;
        }

        return prepareResult(true, $data_array, [], "Organisation listing", $this->success, $pagination);
    }

    public function usersList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $users = User::whereNotIn('usertype', [2, 3])
            ->with('role:id,name', 'organisation:id,org_name,org_logo')
            ->get();

        $users_array = array();
        if (is_object($users)) {
            foreach ($users as $key => $users1) {
                $users_array[] = $users[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($users_array[$offset])) {
                    $data_array[] = $users_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($users_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($users_array);
        } else {
            $data_array = $users_array;
        }

        return prepareResult(true, $data_array, [], "Users listing", $this->success, $pagination);

        // return prepareResult(true, $users, [], "users listing", $this->success);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->user = getUser();
        if (!is_object($this->user)) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Organisation", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $software_id = Software::where('slug', config('app.current_domain'))
                ->first();

            $organisation = new Organisation;
            $organisation->org_company_id = $request->org_company_id;
            $organisation->org_tax_id = $request->org_tax_id;
            $organisation->org_name = $request->org_name;
            $organisation->org_street1 = $request->org_street1;
            $organisation->org_street2 = $request->org_street2;
            $organisation->org_city = $request->org_city;
            $organisation->org_state = $request->org_state;
            $organisation->org_country_id = $request->org_country_id;
            $organisation->org_postal = $request->org_postal;
            $organisation->org_phone = $request->org_phone;
            $organisation->org_contact_person = $request->org_contact_person;
            $organisation->org_contact_person_number = $request->org_contact_person_number;
            $organisation->org_currency = $request->org_currency;
            // $organisation->user_created = 1;
            $organisation->is_trial_period = 1;
            $organisation->reg_software_id = $software_id->id;
            // logo storage

            if (!empty($request->org_logo)) {
                $destinationPath    = 'uploads/avatar/';
                $image_name = Str::slug(substr($request->org_name, 0, 30));
                $image = $request->org_logo;
                $getBaseType = explode(',', $image);
                $getExt = explode(';', $image);
                $image = str_replace($getBaseType[0] . ',', '', $image);
                $image = str_replace(' ', '+', $image);
                $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                \File::put($destinationPath . $fileName, base64_decode($image));
                $organisation->org_logo           = URL('/') . '/' . $destinationPath . $fileName;
            }

            $organisation->org_fasical_year = $request->org_fasical_year;
            $organisation->org_status = $request->org_status;
            $organisation->save();

            $this->user->organisation_id = $organisation->id;
            $this->user->save();
            \DB::table('model_has_permissions')->where('model_id', $this->user->id)->delete();

            $orgRole = new OrganisationRole;
            $orgRole->organisation_id = $organisation->id;
            $orgRole->name = 'org-admin';
            $orgRole->save();

            $names = array('NSM', 'ASM', 'Supervisor');
            foreach ($names as $name) {
                $this->saveOrgRole($organisation->id, $name);
            }

            $defaultRole = Role::find(2);  //assigned org-roles all permission

            foreach ($defaultRole->permissions as $key => $permission) {
                $addRolePermission = new OrganisationRoleHasPermission;
                $this->user->givePermissionTo($permission['id']);
                $addRolePermission->organisation_role_id = $orgRole->id;
                $addRolePermission->permission_id = $permission['id'];
                $addRolePermission->save();
            }

            $this->user->role_id = $orgRole->id;
            $this->user->save();

            // Save country
            $this->saveCurrency($organisation->org_country_id);

            // add  User Credit Limit option
            $UserCreditLimit = new UserCreditLimit;
            $UserCreditLimit->user_id           = $this->user->id;
            $UserCreditLimit->credit_limit_type = 1;
            $UserCreditLimit->save();

            if (!$UserCreditLimit) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            }


            \DB::commit();
            return prepareResult(true, $organisation, [], "Organisation added successfully", $this->success);
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
     * @return \Illuminate\Http\Response
     */
    private function saveCurrency($country_id)
    {
        $country = CountryMaster::find($country_id);
        $currency_master = CurrencyMaster::where('code', $country->currency_code)->first();
        if (!is_object($currency_master)) {
            $currency_master = CurrencyMaster::where('code', "AED")->first();
        }

        $currency = new Currency;
        $currency->currency_master_id = $currency_master->id;
        $currency->name = $currency_master->name;
        $currency->symbol = $currency_master->symbol;
        $currency->code = $currency_master->code;
        $currency->name_plural = $currency_master->name_plural;
        $currency->symbol_native = $currency_master->symbol_native;
        $currency->decimal_digits = $currency_master->decimal_digits;
        $currency->rounding = $currency_master->rounding;
        $currency->decimal_digits = 2;
        $currency->default_currency = 1;
        $currency->default_currency = "1,234,567.89";
        $currency->save();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $organisation = $this->user->organisation;

        if (!is_object($organisation)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }

        return prepareResult(true, $organisation, [], "Van category added successfully", $this->success);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  uuid  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $this->user = getUser();
        if (!is_object($this->user)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $organisation = Organisation::where('id', $this->user->organisation_id)
            ->where('uuid', $uuid)
            ->first();


        if (!is_object($organisation)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $organisation, [], "Organisation Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        $this->user = getUser();
        if (!is_object($this->user)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating organisation", $this->success);
        }

        $organisation = Organisation::where('id', $this->user->organisation_id)
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($organisation)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $organisation->org_company_id = $request->org_company_id;
        $organisation->org_tax_id = $request->org_tax_id;
        $organisation->org_name = $request->org_name;
        $organisation->org_street1 = $request->org_street1;
        $organisation->org_street2 = $request->org_street2;
        $organisation->org_city = $request->org_city;
        $organisation->org_state = $request->org_state;
        $organisation->org_country_id = $request->org_country_id;
        $organisation->org_postal = $request->org_postal;
        $organisation->org_phone = $request->org_phone;
        $organisation->org_contact_person = $request->org_contact_person;
        $organisation->org_contact_person_number = $request->org_contact_person_number;
        $organisation->org_currency = $request->org_currency;

        if (!empty($request->org_logo)) {
            $destinationPath    = 'uploads/avatar/';
            $image_name = Str::slug(substr($request->org_name, 0, 30));
            $image = $request->org_logo;
            $getBaseType = explode(',', $image);
            $getExt = explode(';', $image);
            $image = str_replace($getBaseType[0] . ',', '', $image);
            $image = str_replace(' ', '+', $image);
            $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
            \File::put($destinationPath . $fileName, base64_decode($image));
            $organisation->org_logo           = URL('/') . '/' . $destinationPath . $fileName;
        }
        $organisation->org_fasical_year = $request->org_fasical_year;
        $organisation->org_status = $request->org_status;
        $organisation->save();

        return prepareResult(true, $organisation, [], "Organisation updated successfully", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->user = getUser();
        if (!is_object($this->user)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = request()->json()->all();
        $validate = $this->validations($input, "delete");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating organisation", $this->unauthorized);
        }

        $organisation = Organisation::where('id', $this->user->organisation_id)
            ->where('id', $id)
            ->first();

        if (is_object($organisation)) {
            $organisation->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "depot") {
            $validator = \Validator::make($input, [
                'org_name' => 'required',
                'org_address' => 'required',
                'org_city' => 'required',
                'org_country_id' => 'required',
                'org_postal' => 'required',
                'org_phone' => 'required',
                'org_contact_person' => 'required',
                'org_contact_person_number' => 'required',
                'org_currency' => 'required',
                'org_logo' => 'required',
                'org_status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "orgCusstList") {
            $validator = \Validator::make($input, [
                'role_id' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "delete") {
            $validator = \Validator::make($input, [
                'id'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function orgCustomerList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "orgCusstList");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating organisation", $this->success);
        }

        // $organisation = Organisation::where('id', $this->user->organisation_id)
        //     // ->where('user', )
        //     ->get();

        $customers = User::where('role_id', $request->role_id)->get();

        return prepareResult(true, $customers, [], "Organisation listing", $this->success);
    }

    private function saveOrgRole($organisation_id, $name)
    {
        $orgRole = new OrganisationRole;
        $orgRole->organisation_id = $organisation_id;
        $orgRole->name = $name;
        $orgRole->save();

        if ($name == "NSM") {
            $defaultRole = Role::find(4);  //assigned nsm all permission
        } else if ($name == "ASM") {
            $defaultRole = Role::find(5);  //assigned asm all permission
        } else if ($name == "Supervisor") {
            $defaultRole = Role::find(6);  //assigned supervisor all permission
        }

        foreach ($defaultRole->permissions as $key => $permission) {
            $addRolePermission = new OrganisationRoleHasPermission;
            $addRolePermission->organisation_role_id = $orgRole->id;
            $addRolePermission->permission_id = $permission['id'];
            $addRolePermission->save();
        }
    }
}
