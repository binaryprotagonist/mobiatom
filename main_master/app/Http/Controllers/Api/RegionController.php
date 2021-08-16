<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Region;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RegionImport;
use App\Model\SalesmanInfo;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate.", $this->unauthorized);
        }

        if (!checkPermission('region-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('region-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $region = Region::select('id', 'uuid', 'organisation_id', 'country_id', 'region_name', 'region_code', 'region_status')
            ->with('country:id,name,uuid', 'customFieldValueSave', 'customFieldValueSave.customField')
            ->orderBy('id', 'desc')
            ->get();

        $region_array = array();
        if (is_object($region)) {
            foreach ($region as $key => $region1) {
                $region_array[] = $region[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($region_array[$offset])) {
                    $data_array[] = $region_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($region_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($region_array);
        } else {
            $data_array = $region_array;
        }

        return prepareResult(true, $data_array, [], "Region listing", $this->success, $pagination);
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

        if (!checkPermission('region-save')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating region", $this->unprocessableEntity);
        }

        // Create region object
        $region = new Region;
        $region->country_id = $request->country_id;
        $region->region_code = nextComingNumber('App\Model\Region', 'region', 'region_code', $request->region_code);
        // $region->region_code =  $request->region_code;
        $region->region_name = $request->region_name;
        $region->region_status = $request->region_status;
        $region->save();

        if ($region) {
            updateNextComingNumber('App\Model\Region', 'region');

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($region->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }
            $region->country;
            return prepareResult(true, $region, [], "Region added successfully", $this->success);
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

        if (!checkPermission('region-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        // Create region object
        $region = Region::where('uuid', $uuid)
            ->select('id', 'organisation_id', 'country_id', 'region_name', 'region_code', 'region_status')
            ->with('country:id,name,uuid', 'customFieldValueSave')
            ->first();

        if (!is_object($region)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $region, [], "Region Edit", $this->success);
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

        if (!checkPermission('region-update')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating region", $this->unprocessableEntity);
        }

        // Create region object
        $region = Region::where('uuid', $uuid)
            ->first();

        if (!is_object($region)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }
        $region->country_id = $request->country_id;
        $region->region_name = $request->region_name;
        $region->region_status = $request->region_status;
        $region->save();

        if (is_array($request->modules) && sizeof($request->modules) >= 1) {
            CustomFieldValueSave::where('record_id', $region->id)->delete();
            foreach ($request->modules as $module) {
                savecustomField($region->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
            }
        }
        $region->country;
        return prepareResult(true, $region, [], "Region updated successfully", $this->success);
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

        if (!checkPermission('region-delete')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating regions", $this->unauthorized);
        }

        $region = Region::where('uuid', $uuid)
            ->first();

        if (is_object($region)) {
            $region->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access.", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'country_id'     => 'required|integer|exists:countries,id',
                'region_name'     => 'required',
                'region_code'     => 'required',
                'region_status'     => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'     => 'required',
                'region_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
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

        if (!checkPermission('region-bulk-action')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating region", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->region_ids;
            foreach ($uuids as $uuid) {
                Region::where('uuid', $uuid)->update([
                    'region_status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $region = $this->index();
            return prepareResult(true, $region, [], "Region status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->region_ids;
            foreach ($uuids as $uuid) {
                Region::where('uuid', $uuid)->delete();
            }
            $region = $this->index();
            return prepareResult(true, $region, [], "Region deleted success", $this->success);
        } else if ($action == 'add') {
            $uuids = $request->region_ids;
            foreach ($uuids as $uuid) {
                $region = new Region;
                $region->country_id = $uuid['country_id'];
                $region->region_code = nextComingNumber('App\Model\Region', 'region', 'region_code', $request->region_code);
                $region->region_name = $uuid['region_name'];
                $region->region_status = $uuid['region_status'];
                $region->save();
                updateNextComingNumber('App\Model\Region', 'region');
            }
            $region = $this->index();
            return prepareResult(true, $region, [], "Region added success", $this->success);
        }
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = \Validator::make($request->all(), [
            'region_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate region import", $this->unauthorized);
        }

        Excel::import(new RegionImport, request()->file('region_file'));
        return prepareResult(true, [], [], "Region successfully imported", $this->success);
    }

    public function regionSupervisor($region_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$region_id) {
            return prepareResult(false, [], [], "Error while validating route supervisor", $this->unauthorized);
        }

        $salesman_info = SalesmanInfo::select('id', 'region_id', 'user_id', 'salesman_supervisor')
            ->with(
                'region:id,region_name',
                'salesmanSupervisor:id,firstname,lastname',
            )
            ->where('region_id', $region_id)
            ->orderBy('id', 'desc')
            ->groupBy('salesman_supervisor')
            ->get();

        foreach ($salesman_info as $key => $salesman) {
            $salesmanInfo = SalesmanInfo::with('user:id,firstname,lastname')
                ->where('salesman_supervisor', $salesman->salesman_supervisor)
                ->get();

            $salesman_info[$key]->salesmans = $salesmanInfo;
        }

        if (is_object($salesman_info)) {
            return prepareResult(true, $salesman_info, [], "Region Salesman listed successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }
}
