<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;

class WarehouseController extends Controller
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

        $warehouse = Warehouse::with(
            'depot:id,depot_code,depot_name',
            'route:id,route_code,route_name',
            'parentWarehouses',
            'customFieldValueSave',
            'customFieldValueSave.customField',
            'organisation:id,org_name'
        )
            //->where('order_date', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        $warehouse_array = array();
        if (is_object($warehouse)) {
            foreach ($warehouse as $key => $warehouse1) {
                $warehouse_array[] = $warehouse[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($warehouse_array[$offset])) {
                    $data_array[] = $warehouse_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($warehouse_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($warehouse_array);
        } else {
            $data_array = $warehouse_array;
        }

        return prepareResult(true, $data_array, [], "Warehouse listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating warehouse", $this->unprocessableEntity);
        }

        if ($request->parent_warehouse_id) {
            if (!$request->depot_id && !$request->route_id) {
                return prepareResult(false, [], 'You have to pass depot or route.', "Error while validating warehouse", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            // $status = (isset($request->status))?$request->status:0;
            // if ($isActivate = checkWorkFlowRule('Warehouse', 'create')) {
            //     $status = 0;
            //     $this->createWorkFlowObject($isActivate, 'Warehouse',$request);
            // }

            $warehouse = new Warehouse;
            $warehouse->code         = nextComingNumber('App\Model\Warehouse', 'warehouse', 'code', $request->code);
            $warehouse->name            = (!empty($request->name)) ? $request->name : null;
            $warehouse->address            = (!empty($request->address)) ? $request->address : null;
            $warehouse->manager       = (!empty($request->manager)) ? $request->manager : null;
            $warehouse->depot_id        = (!empty($request->depot_id)) ? $request->depot_id : null;
            $warehouse->route_id        = (!empty($request->route_id)) ? $request->route_id : null;
            $warehouse->is_main        = (!empty($request->is_main)) ? 1 : 0;  // 1 is main and 0 means normal child warehouse
            $warehouse->parent_warehouse_id        = (!empty($request->parent_warehouse_id)) ? $request->parent_warehouse_id : null;
            $warehouse->lat        = (!empty($request->lat)) ? $request->lat : null;
            $warehouse->lang        = (!empty($request->lang)) ? $request->lang : null;
            $warehouse->status        = (!empty($request->status)) ? $request->status : 1;
            $warehouse->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($warehouse->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\Warehouse', 'warehouse');

            $warehouse->getSaveData();

            return prepareResult(true, $warehouse, [], "Warehouse added successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating warehouse.", $this->unauthorized);
        }
        $warehouse = Warehouse::with(
            'depot:id,depot_code,depot_name',
            'route:id,route_code,route_name',
            'parentWarehouses',
            'customFieldValueSave'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($warehouse)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $warehouse, [], "Warehouse Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating warehouse.", $this->unprocessableEntity);
        }

        if ($request->parent_warehouse_id) {
            if (!$request->depot_id && !$request->route_id) {
                return prepareResult(false, [], 'You have to pass depot or route.', "Error while validating warehouse", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $warehouse = Warehouse::where('uuid', $uuid)->first();
            $warehouse->code         = (!empty($request->code)) ? $request->code : null;
            $warehouse->name            = (!empty($request->name)) ? $request->name : null;
            $warehouse->address            = (!empty($request->address)) ? $request->address : null;
            $warehouse->manager       = (!empty($request->manager)) ? $request->manager : null;
            $warehouse->depot_id        = (!empty($request->depot_id)) ? $request->depot_id : null;
            $warehouse->route_id        = (!empty($request->route_id)) ? $request->route_id : null;
            $warehouse->parent_warehouse_id        = (!empty($request->parent_warehouse_id)) ? $request->parent_warehouse_id : null;
            $warehouse->lat        = (!empty($request->lat)) ? $request->lat : null;
            $warehouse->lang        = (!empty($request->lang)) ? $request->lang : null;
            $warehouse->status        = (!empty($request->status)) ? $request->status : 1;
            $warehouse->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                CustomFieldValueSave::where('record_id', $warehouse->id)->delete();
                foreach ($request->modules as $module) {
                    savecustomField($warehouse->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            $warehouse->getSaveData();
            return prepareResult(true, $warehouse, [], "Warehouse updated successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating warehouse.", $this->unauthorized);
        }

        $warehouse = Warehouse::where('uuid', $uuid)->first();

        if (is_object($warehouse)) {
            $warehouseId = $warehouse->id;
            $warehouse->delete();
            if ($warehouse) {
                WarehouseDetail::where('warehouse_id', $warehouseId)->delete();
            }
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating warehouse", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->warehouse_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $warehouse = Warehouse::where('uuid', $uuid)->first();
                $warehouseId = $warehouse->id;
                $warehouse->delete();
                if ($warehouse) {
                    WarehouseDetail::where('warehouse_id', $warehouseId)->delete();
                }
            }
            $warehouse = $this->index();
            return prepareResult(true, $warehouse, [], "Warehouse deleted success", $this->success);
        }
    }
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'code' => 'required',
                'name' => 'required',
                //  'depot_id' => 'required|integer|exists:depots,id'
                // 'route_id' => 'required|integer|exists:routes,id'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'warehouse_ids'     => 'required'
            ]);
        }

        if ($type == 'getDetails') {
            $validator = \Validator::make($input, [
                'depot_id'        => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
    /**
     * Get price specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $item_id, $item_uom_id, $item_qty
     * @return \Illuminate\Http\Response
     */
    public function getwarehousedetail(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "getDetails");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating warehouse.", $this->unprocessableEntity);
        }

        $warehouse = Warehouse::with(
            'warehouseDetails',
            'warehouseDetails.item:id,item_name',
            'warehouseDetails.itemUom:id,name,code'
        )
            // ->where('route_id', $route_id)
            ->whereNull('route_id')
            ->where('depot_id', $request->depot_id)
            ->orderBy('id', 'desc')
            ->get();

        $warehouse_array = array();
        if (is_object($warehouse)) {
            foreach ($warehouse as $key => $warehouse1) {
                $warehouse_array[] = $warehouse[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($warehouse_array[$offset])) {
                    $data_array[] = $warehouse_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($warehouse_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($warehouse_array);
        } else {
            $data_array = $warehouse_array;
        }

        return prepareResult(true, $data_array, [], "Warehouse detail listing", $this->success, $pagination);

        // return prepareResult(true, $warehouse, [], "Warehouse detail listing", $this->success);
    }

    public function getitemstock($depot_id, $item_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$item_id) {
            return prepareResult(false, [], [], "Error while validating warehouse detail.", $this->unauthorized);
        }
        if (!$depot_id) {
            return prepareResult(false, [], [], "Error while validating warehouse detail.", $this->unauthorized);
        }

        $warehouse = Warehouse::where('depot_id', $depot_id)
            ->whereNull('route_id')
            ->first();

        if (is_object($warehouse)) {
            $warehousedetail = WarehouseDetail::with(
                'item:id,item_code,item_name',
                'itemUom:id,code,name'
            )
                ->where('item_id', $item_id)
                ->where('warehouse_id', $warehouse->id)
                ->get();

            $warehouse->warehousedetail = $warehousedetail;
        }

        return prepareResult(true, $warehouse, [], "Item stock", $this->success);
    }

    // public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request)
    // {
    //     $createObj = new WorkFlowObject;
    //     $createObj->work_flow_rule_id   = $work_flow_rule_id;
    //     $createObj->module_name         = $module_name;
    //     $createObj->request_object      = $request->all();
    //     $createObj->save();
    // }
}
