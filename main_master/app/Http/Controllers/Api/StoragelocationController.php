<?php

namespace App\Http\Controllers\Api;

use DB;
use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Storagelocation;
use App\Model\Warehouse;
use App\Model\StoragelocationDetail;

class StoragelocationController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexAll(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $storagelocation = Storagelocation::select('id', 'code', 'name')
            ->orderBy('name', 'asc')
            ->get();


        // $storagelocation_array = array();
        // if (is_object($storagelocation)) {
        //     foreach ($storagelocation as $key => $storagelocation1) {
        //         $storagelocation_array[] = $storagelocation[$key];
        //     }
        // }

        // $data_array = array();
        // $page = (isset($request->page)) ? $request->page : '';
        // $limit = (isset($request->page_size)) ? $request->page_size : '';
        // $pagination = array();
        // if ($page != '' && $limit != '') {
        //     $offset = ($page - 1) * $limit;
        //     for ($i = 0; $i < $limit; $i++) {
        //         if (isset($storagelocation_array[$offset])) {
        //             $data_array[] = $storagelocation_array[$offset];
        //         }
        //         $offset++;
        //     }

        //     $pagination['total_pages'] = ceil(count($storagelocation_array) / $limit);
        //     $pagination['current_page'] = (int)$page;
        //     $pagination['total_records'] = count($storagelocation_array);
        // } else {
        //     $data_array = $storagelocation_array;
        // }

        return prepareResult(true, $storagelocation, [], "Storage Location listing", $this->success);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $storagelocation = Storagelocation::with(
            //'warehouse:id,code,name',
            'route:id,route_code,route_name',
            'organisation:id,org_name'
        )
            ->where('warehouse_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $storagelocation_array = array();
        if (is_object($storagelocation)) {
            foreach ($storagelocation as $key => $storagelocation1) {
                $storagelocation_array[] = $storagelocation[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($storagelocation_array[$offset])) {
                    $data_array[] = $storagelocation_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($storagelocation_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($storagelocation_array);
        } else {
            $data_array = $storagelocation_array;
        }

        return prepareResult(true, $data_array, [], "Storagelocation listing", $this->success, $pagination);

        // return prepareResult(true, $warehouse, [], "Warehouse listing", $this->success);
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


        /*if (!$request->route_id || !$request->warehouse_id) {
                return prepareResult(false, [], 'You have to pass route or warehouse.', "Error while validating warehouse", $this->unprocessableEntity);
            }*/


        \DB::beginTransaction();
        try {


            $Storagelocation = new Storagelocation;
            $Storagelocation->code         = (!empty($request->code)) ? $request->code : null;
            $Storagelocation->name            = (!empty($request->name)) ? $request->name : null;
            $Storagelocation->route_id       = (!empty($request->route_id)) ? $request->route_id : null;
            $Storagelocation->warehouse_id        = (!empty($request->warehouse_id)) ? $request->warehouse_id : null;
            $Storagelocation->loc_type        = (!empty($request->loc_type)) ? $request->loc_type : 1;
            $Storagelocation->save();


            \DB::commit();
            // updateNextComingNumber('App\Model\Storagelocation', 'Storagelocation');

            $Storagelocation->getSaveData();

            return prepareResult(true, $Storagelocation, [], "Storagelocation added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating Storagelocation.", $this->unauthorized);
        }
        $Storagelocation = Storagelocation::with(
            'warehouse:id,code,name',
            'route:id,route_code,route_name',
            'customFieldValueSave'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($Storagelocation)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $warehouse, [], "Storagelocation Edit", $this->success);
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

        /*if (!$request->route_id || !$request->warehouse_id) {
                return prepareResult(false, [], 'You have to pass route or warehouse.', "Error while validating warehouse", $this->unprocessableEntity);
            }*/


        \DB::beginTransaction();
        try {
            $Storagelocation = Storagelocation::where('uuid', $uuid)->first();
            $Storagelocation->code         = (!empty($request->code)) ? $request->code : null;
            $Storagelocation->name            = (!empty($request->name)) ? $request->name : null;
            $Storagelocation->route_id       = (!empty($request->route_id)) ? $request->route_id : null;
            $Storagelocation->warehouse_id        = (!empty($request->warehouse_id)) ? $request->warehouse_id : null;
            $Storagelocation->loc_type        = (!empty($request->loc_type)) ? $request->loc_type : 1;
            $Storagelocation->save();

            \DB::commit();
            $Storagelocation->getSaveData();
            return prepareResult(true, $Storagelocation, [], "Storagelocation updated successfully", $this->created);
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

        $Storagelocation = Storagelocation::where('uuid', $uuid)->first();

        if (is_object($Storagelocation)) {
            $StoragelocationId = $Storagelocation->id;
            $Storagelocation->delete();
            if ($Storagelocation) {
                StoragelocationDetail::where('storage_location_id', $StoragelocationId)->delete();
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
                //'warehouse_id' => 'exists:warehouses,id',
                // 'route_id' => 'exists:routes,id',
                'loc_type' => 'required'

            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                //'warehouse_ids'     => 'required'
            ]);
        }

        if ($type == 'getDetails') {
            $validator = \Validator::make($input, [
                // 'depot_id'        => 'required'
            ]);
        }

        if ($type == "stock_check") {
            $validator = \Validator::make($input, [
                // 'route_id' => 'required',
                'item_id' => 'required',
                'item_uom_id' => 'required',
                'item_qty' => 'required',
            ]);
        }

        if ($type == "route_item") {
            $validator = \Validator::make($input, [
                'depot_id' => 'required|integer|exists:depots,id',
                // 'item_id' => 'required|integer|exists:items,id'
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


    public function isStockCheck(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "stock_check");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating stock check", $this->unprocessableEntity);
        }

        $qty_result = getItemDetails2($request->item_id, $request->item_uom_id, $request->item_qty);

        if (!empty($request->depot_id)) {
            $warehouse = Warehouse::where('depot_id', $request->depot_id)->first();
            if (!is_object($warehouse)) {
                return prepareResult(false, [], [], "There are no warehouse attached with depot.", $this->unprocessableEntity);
            }
            $Storagelocation = Storagelocation::where('warehouse_id', $warehouse->id)
                ->where('loc_type', 1)
                ->first();
        } else {
            $Storagelocation = Storagelocation::where('route_id', $request->route_id)
                ->where('loc_type', 1)
                ->first();
        }

        if (is_object($Storagelocation)) {
            $Storagelocation_detials_results = StoragelocationDetail::where('storage_location_id', $Storagelocation->id)
                ->where('item_id', $request->item_id)
                //->where('item_uom_id',$qty_result['UOM'])
                ->first();

            if (!is_object($Storagelocation_detials_results)) {
                return prepareResult(false, false, "Error while checking Storage location detail",  'Item stock is not available.', $this->unprocessableEntity);
            }

            if ($Storagelocation_detials_results->qty >  $qty_result['Qty']) {
                $result = true;
                return prepareResult(true, $result, [], "Stock check", $this->success);
            } else {
                $result = false;
                return prepareResult(true, $result, [], "Stock check", $this->success);
            }
        } else {
            return prepareResult(false, [], 'Route Storage location is not found for given route.', "Error while checking Storage location", $this->unprocessableEntity);
        }
    }

    public function isStockCheckMultiple(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $record = array();
        $arrange_record = [];
        foreach ($input['items'] as $key => $item) {
            $validate = $this->validations($item, "stock_check");
            if ($validate["error"]) {
                $record[$key] = prepareResult(false, [], $validate['errors']->first(), "Error while validating stock check", $this->unprocessableEntity);
                continue;
            }

            $qty_result = getItemDetails2($item['item_id'], $item['item_uom_id'], $item['item_qty']);

            if (!empty($input->depot_id)) {
                $warehouse = Warehouse::where('depot_id', $input->depot_id)->first();
                if (!is_object($warehouse)) {
                    $record[$key] = prepareResult(false, [], [], "There are no warehouse attached with depot.", $this->unprocessableEntity);
                    continue;
                }
                $Storagelocation = Storagelocation::where('warehouse_id', $warehouse->id)
                    ->where('loc_type', 1)
                    ->first();
            } else {
                $Storagelocation = Storagelocation::where('route_id', $request->route_id)
                    ->where('loc_type', 1)
                    ->first();
            }

            if (is_object($Storagelocation)) {
                $Storagelocation_detials_results = StoragelocationDetail::where('storage_location_id', $Storagelocation->id)
                    ->where('item_id', $item['item_id'])
                    //->where('item_uom_id',$qty_result['UOM'])
                    ->first();

                if (!is_object($Storagelocation_detials_results)) {
                    $record[$key] = prepareResult(false, false, "Error while checking Storage location detail",  'Item stock is not available.', $this->unprocessableEntity);
                    continue;
                }

                if ($Storagelocation_detials_results->qty >  $qty_result['Qty']) {
                    $result = true;
                    $record[$key] = prepareResult(true, $result, [], "Stock check", $this->success);
                    continue;
                } else {
                    $result = false;
                    $record[$key] = prepareResult(true, $result, [], "Stock check", $this->success);
                    continue;
                }
            } else {
                $record[$key] =  prepareResult(false, [], 'Route Storage location is not found for given route.', "Error while checking Storage location", $this->unprocessableEntity);
                continue;
            }
        }

        foreach ($record as $key => $value) {
           $value->original['item_id'] =  $input['items'][$key]['item_id']; 
           $arrange_record[$key] =  $value->original;
        }

        return $arrange_record;
    }

    public function routeItemQty(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "route_item");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating stock check", $this->unprocessableEntity);
        }

        if (is_array($request->item_id) && sizeof($request->item_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        $warehouse = Warehouse::where('depot_id', $request->depot_id)->first();

        if (!is_object($warehouse)) {
            return prepareResult(false, [], [], "Warehouse Not Found", $this->success);
        }

        $storagelocation = Storagelocation::where('warehouse_id', $warehouse->id)
            ->where('loc_type', 1)
            ->first();

        if (is_object($storagelocation)) {
            $sld = StoragelocationDetail::select('id', 'storage_location_id', 'item_id', 'item_uom_id', 'qty')
                ->where('storage_location_id', $storagelocation->id)
                ->whereIn('item_id', $request->item_id)
                ->get();

            if (count($sld)) {
                return prepareResult(true, $sld, [], "Item wise qty", $this->success);
            } else {
                return prepareResult(false, [], [], "Item Not Found", $this->success);
            }
        }
        return prepareResult(false, [], [], "Warehouse Not Found", $this->success);
    }
}
