<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Van;
use App\Imports\VanImport;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class VanController extends Controller
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

        if (!checkPermission('route-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('route-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $van = Van::select('id', 'uuid', 'organisation_id', 'van_code', 'plate_number', 'description', 'capacity', 'van_type_id', 'van_category_id', 'van_status')
            ->with(
                'type:id,name',
                'category:id,name',
                'customFieldValueSave',
                'customFieldValueSave.customField'
            )
            ->orderBy('id', 'desc')
            ->get();
        $van_array = array();
        if (is_object($van)) {
            foreach ($van as $key => $van1) {
                $van_array[] = $van[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($van_array[$offset])) {
                    $data_array[] = $van_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($van_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($van_array);
        } else {
            $data_array = $van_array;
        }

        return prepareResult(true, $data_array, [], "Van listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @return [json] van object
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating van", $this->unprocessableEntity);
        }

        $van = new Van;
        $van->van_code = nextComingNumber('App\Model\Van', 'van', 'van_code', $request->van_code);
        $van->plate_number = $request->plate_number;
        $van->description = $request->description;
        $van->capacity = $request->capacity;
        $van->van_type_id = $request->van_type_id;
        $van->van_category_id = $request->van_category_id;
        $van->van_status = $request->van_status;
        $van->save();

        if ($van) {
            updateNextComingNumber('App\Model\Van', 'van');
            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($van->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }
            return prepareResult(true, $van, [], "Van added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
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

        $van = Van::where('uuid', $uuid)
            ->first();

        if (!is_object($van)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating van", $this->unprocessableEntity);
        }

        // Create van object
        $van->plate_number = $request->plate_number;
        $van->description = $request->description;
        $van->capacity = $request->capacity;
        $van->van_type_id = $request->van_type_id;
        $van->van_category_id = $request->van_category_id;
        $van->van_status = $request->van_status;
        $van->save();

        if (is_array($request->modules) && sizeof($request->modules) >= 1) {
            CustomFieldValueSave::where('record_id', $van->id)->delete();
            foreach ($request->modules as $module) {
                savecustomField($van->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
            }
        }

        return prepareResult(true, $van, [], "Van updated successfully", $this->success);
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

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating van", $this->unauthorized);
        }

        // Create region object
        $van = Van::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'van_code', 'plate_number', 'description', 'capacity', 'van_type_id', 'van_category_id', 'van_status')
            ->with(
                'type:id,name',
                'category:id,name',
                'customFieldValueSave',
                'customFieldValueSave.customField'
                )
            ->first();

        if (!is_object($van)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $van, [], "Region Edit", $this->success);
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
            return prepareResult(false, [], [], "Error while validating van", $this->unauthorized);
        }

        $van = Van::where('uuid', $uuid)
            ->first();

        if (is_object($van)) {
            $van->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
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
                Van::where('uuid', $uuid)->update([
                    'van_status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $region = $this->index();
            return prepareResult(true, $region, [], "Van status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->region_ids;
            foreach ($uuids as $uuid) {
                Van::where('uuid', $uuid)->delete();
            }
            $region = $this->index();
            return prepareResult(true, $region, [], "Van deleted success", $this->success);
        } else if ($action == 'add') {
            $uuids = $request->region_ids;
            foreach ($uuids as $uuid) {
                $van = new Van;

                $van->van_code = nextComingNumber('App\Model\Van', 'van', 'van_code', $request->van_code);
                $van->plate_number = $uuid['plate_number'];
                $van->description = $uuid['description'];
                $van->capacity = $uuid['capacity'];
                $van->van_type_id = $uuid['van_type_id'];
                $van->van_category_id = $uuid['van_category_id'];
                $van->van_status = $uuid['van_status'];
                $van->save();

                updateNextComingNumber('App\Model\Van', 'van');
            }
            $region = $this->index();
            return prepareResult(true, $region, [], "Van added success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'plate_number'     => 'required',
                'description'     => 'required',
                'van_code'     => 'required',
                'van_type_id'     => 'required|integer|exists:van_types,id'
                // 'capacity'     => 'required',
                // 'van_category_id'     => 'required|integer|exists:van_categories,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'van_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate van import", $this->unauthorized);
        }

        Excel::import(new VanImport, request()->file('van_file'));
        return prepareResult(true, [], [], "Van successfully imported", $this->success);
    }
}
