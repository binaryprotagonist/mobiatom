<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Area;

class AreaController extends Controller
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

        if (!checkPermission('area-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('area-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $area = Area::select('id', 'uuid', 'organisation_id', 'parent_id', 'area_name', 'node_level', 'status')
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        $area_array = array();
        if (is_object($area)) {
            foreach ($area as $key => $area1) {
                $area_array[] = $area[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($area_array[$offset])) {
                    $data_array[] = $area_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($area_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($area_array);
        } else {
            $data_array = $area_array;
        }

        return prepareResult(true, $data_array, [], "Area listing", $this->success, $pagination);
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

        if (!checkPermission('area-add')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating area", $this->unprocessableEntity);
        }

        $area = new Area;
        $area->parent_id = $request->parent_id;
        $area->node_level = $request->node_level;
        $area->area_name = $request->area_name;
        $area->status = $request->status;
        $area->save();

        if ($area) {
            return prepareResult(true, $area, [], "Area added successfully", $this->success);
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

        if (!checkPermission('area-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $area = Area::with('children')
        // ->where('uuid', $uuid)
        ->first();

        if (!is_object($area)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $area, [], "Area Edit", $this->success);
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

        if (!checkPermission('area-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating area", $this->unprocessableEntity);
        }

        $area = Area::where('uuid', $uuid)
            ->first();

        if (!is_object($area)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        $area->parent_id = $request->parent_id;
        $area->area_name = $request->area_name;
        $area->node_level = $request->node_level;
        $area->status = $request->status;
        $area->save();

        return prepareResult(true, $area, [], "Area updated successfully", $this->success);
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

        if (!checkPermission('area-delete')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $area = Area::where('uuid', $uuid)
            ->first();

        if (is_object($area)) {
            $area->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $validator = [];
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'parent_id' => 'nullable|integer|exists:areas,id',
                'area_name' => 'required|string',
                'status' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'area_ids'     => 'required'
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

        // if (!checkPermission('item-group-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating area.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->area_ids;

            foreach ($uuids as $uuid) {
                Area::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $area = $this->index();
            return prepareResult(true, $area, [], "area status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->area_ids;
            foreach ($uuids as $uuid) {
                Area::where('uuid', $uuid)->delete();
            }

            $area = $this->index();
            return prepareResult(true, $area, [], "area deleted success", $this->success);
        }
    }
}
