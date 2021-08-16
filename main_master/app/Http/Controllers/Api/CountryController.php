<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CountryMaster;
use App\Model\Country;
use App\Model\CustomFieldValueSave;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allCountry()
    {
        $country = CountryMaster::select('id', 'name', 'country_code', 'dial_code', 'currency', 'currency_code', 'currency_symbol')->get();

        return prepareResult(true, $country, [], "Country listing", $this->success);
    }

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

        $country = Country::select('id', 'uuid', 'name', 'country_code', 'dial_code', 'currency', 'currency_code', 'currency_symbol')
            ->with('customFieldValueSave', 'customFieldValueSave.customField')
            ->get();

        $country_array = array();
        if (is_object($country)) {
            foreach ($country as $key => $country1) {
                $country_array[] = $country[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($country_array[$offset])) {
                    $data_array[] = $country_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($country_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($country_array);
        } else {
            $data_array = $country_array;
        }

        return prepareResult(true, $data_array, [], "Country listing", $this->success, $pagination);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating country", $this->success);
        }

        $exist = Country::where('country_code', $request->country_code)->first();
        if (is_object($exist)) {
            return prepareResult(false, [], 'Country Code is already added.', "Error while validating country", $this->unprocessableEntity);
        }

        $country = new Country;
        $country->name = $request->name;
        $country->country_code = $request->country_code;
        $country->dial_code = $request->dial_code;
        $country->currency = $request->currency;
        $country->currency_code = $request->currency_code;
        $country->currency_symbol = $request->currency_symbol;
        $country->status = $request->status;
        $country->save();

        if ($country) {
            updateNextComingNumber('App\Model\Country', 'country');

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($country->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            return prepareResult(true, $country, [], "Country added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating country", $this->unauthorized);
        }

        $country = Country::where('uuid', $uuid)
            ->with('customFieldValueSave', 'customFieldValueSave.customField')
            ->first();

        if (!is_object($country)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $country, [], "Country Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating country", $this->success);
        }

        $country = Country::where('uuid', $uuid)
            ->first();

        if (!is_object($country)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $country->name = $request->name;
        $country->dial_code = $request->dial_code;
        $country->currency = $request->currency;
        $country->currency_code = $request->currency_code;
        $country->currency_symbol = $request->currency_symbol;
        $country->status = $request->status;
        $country->save();

        if (is_array($request->modules) && sizeof($request->modules) >= 1) {
            CustomFieldValueSave::where('record_id', $country->id)->delete();
            foreach ($request->modules as $module) {
                savecustomField($country->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
            }
        }

        return prepareResult(true, $country, [], "Country updated successfully", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating country", $this->unauthorized);
        }

        $country = Country::where('uuid', $uuid)
            ->first();

        if (is_object($country)) {
            $country->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'name' => 'required',
                'country_code' => 'required',
                // 'dial_code' => 'required',
                'currency' => 'required',
                // 'currency_code' => 'required',
                'currency_symbol' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'country_ids'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * View the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $id = $request->id;

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating country", $this->unauthorized);
        }

        $country = CountryMaster::find($id);

        if (!is_object($country)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $country, [], "Country view", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $action
     * @param  string  $status
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // if (!checkPermission('country-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating country.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->country_ids;

            foreach ($uuids as $uuid) {
                Country::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $country = $this->index();
            return prepareResult(true, $country, [], "Country status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->country_ids;
            foreach ($uuids as $uuid) {
                Country::where('uuid', $uuid)->delete();
            }

            $country = $this->index();
            return prepareResult(true, $country, [], "Country deleted success", $this->success);
        } else if ($action == 'add') {
            $uuids = $request->country_ids;
            foreach ($uuids as $uuid) {
                $country = new Country;
                $country->name = $uuid['name'];
                $country->country_code = $uuid['country_code'];
                $country->dial_code = $uuid['dial_code'];
                $country->currency = $uuid['currency'];
                $country->currency_code = $uuid['currency_code'];
                $country->currency_symbol = $uuid['currency_symbol'];
                $country->status = $uuid['status'];
                $country->save();
                updateNextComingNumber('App\Model\Country', 'country');
            }

            $country = $this->index();

            return prepareResult(true, $country, [], "Country added success", $this->success);
        }
    }
}
