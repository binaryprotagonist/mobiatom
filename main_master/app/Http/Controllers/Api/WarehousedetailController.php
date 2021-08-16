<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;
use App\Model\WarehouseDetailLog;

class WarehousedetailController extends Controller
{
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

        $warehousedetail = WarehouseDetail::with(
            'item:id,item_code,item_name',
            'itemUom:id,code,name'
        )
            ->where('warehouse_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $warehousedetail_array = array();
        if (is_object($warehousedetail)) {
            foreach ($warehousedetail as $key => $warehousedetail1) {
                $warehousedetail_array[] = $warehousedetail[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($warehousedetail_array[$offset])) {
                    $data_array[] = $warehousedetail_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($warehousedetail_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($warehousedetail_array);
        } else {
            $data_array = $warehousedetail_array;
        }

        return prepareResult(true, $data_array, [], "Warehouse detail listing", $this->success, $pagination);

        // return prepareResult(true, $warehousedetail, [], "Warehouse detail listing", $this->success);
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

        \DB::beginTransaction();
        try {
             // If add already present item same based on  "warehouse" and "item_uom_id", "item id" it will add quentity count in same item               
            $warehousedetail = WarehouseDetail::where('item_id', $request->item_id)
                ->where('item_uom_id', $request->item_uom_id)
                ->where('warehouse_id',$request->warehouse_id)
                ->first();

            if (is_object($warehousedetail)) {

                $qty = $warehousedetail->qty + $request->qty;
                $warehousedetail->qty = $qty;
                $warehousedetail->save();

                $warehousedetail_log = WarehouseDetailLog::where('warehouse_detail_id', $warehousedetail->id)->first();
                $warehousedetail_log->qty = $request->qty;
                $warehousedetail_log->save();

                // If child warehouse item means reduce item quentiy from paraent warehouse item quentiy  
                $Warehouse = Warehouse::where('id',$request->warehouse_id)
                                        ->whereNotNull('parent_warehouse_id') 
                                        ->first();

                if(is_object($Warehouse))
                { 
                     $warehousedetail = WarehouseDetail::where('item_id', $request->item_id)
                                        ->where('item_uom_id', $request->item_uom_id)
                                        ->where('warehouse_id',$Warehouse->parent_warehouse_id)
                                        ->first();

                    $qty = $warehousedetail->qty - $request->qty;
                    $warehousedetail->qty = $qty;
                    $warehousedetail->save();
    
                    $warehousedetail_log = WarehouseDetailLog::where('warehouse_detail_id', $warehousedetail->id)->first();
                    $warehousedetail_log->qty = $request->qty;
                    $warehousedetail_log->save();    
                } 
                
            } else {
                $qty = $request->qty;
                $warehousedetail = new WarehouseDetail;
                $warehousedetail->warehouse_id         = $request->warehouse_id;
                $warehousedetail->item_id         = $request->item_id;
                $warehousedetail->item_uom_id            = $request->item_uom_id;
                $warehousedetail->qty            = $request->qty;
                $warehousedetail->batch       = $request->batch;
                $warehousedetail->save();

                $warehousedetail_log = new WarehouseDetailLog;
                $warehousedetail_log->warehouse_id = $request->warehouse_id;
                $warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
                $warehousedetail_log->item_uom_id = $request->item_uom_id;
                $warehousedetail_log->qty = $request->qty;
                $warehousedetail_log->action_type = 'Load';
                $warehousedetail_log->save();
                

                // If child warehouse item means reduce item quentiy from paraent warehouse item quentiy,  
                $Warehouse = Warehouse::where('id',$request->warehouse_id)
                                        ->whereNotNull('parent_warehouse_id') 
                                        ->first();

                if(is_object($Warehouse))
                { 
                     $warehousedetail = WarehouseDetail::where('item_id', $request->item_id)
                                        ->where('item_uom_id', $request->item_uom_id)
                                        ->where('warehouse_id',$Warehouse->parent_warehouse_id)
                                        ->first();

                    $qty = $warehousedetail->qty - $request->qty;
                    $warehousedetail->qty = $qty;
                    $warehousedetail->save();
    
                    $warehousedetail_log = WarehouseDetailLog::where('warehouse_detail_id', $warehousedetail->id)->first();
                    $warehousedetail_log->qty = $request->qty;
                    $warehousedetail_log->save();                   

                }  
            }


            \DB::commit();

            $warehousedetail->getSaveData();

            return prepareResult(true, $warehousedetail, [], "Warehouse detail added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating warehouse detail.", $this->unauthorized);
        }

        $warehousedetail = WarehouseDetail::with(
            'item:id,item_code,item_name',
            'itemUom:id,code,name'
        )
            ->where('uuid', $uuid)
            ->first();


        if (!is_object($warehousedetail)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $warehousedetail, [], "Warehouse detail Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating warehouse detail.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $warehousedetail = WarehouseDetail::where('uuid', $uuid)->first();

            // Batch logic need

            $warehousedetail->warehouse_id         = $request->warehouse_id;
            $warehousedetail->item_id         = $request->item_id;
            $warehousedetail->item_uom_id            = $request->item_uom_id;
            $warehousedetail->qty            = $request->qty;
            $warehousedetail->batch       = $request->batch;
            $warehousedetail->save();

            WarehouseDetailLog::where('warehouse_detail_id', $warehousedetail->id)->delete();

            $warehousedetail_log = new WarehouseDetailLog;
            $warehousedetail_log->warehouse_id = (!empty($request->warehouse_id)) ? $request->warehouse_id : null;
            $warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
            $warehousedetail_log->item_uom_id = (!empty($request->item_uom_id)) ? $request->item_uom_id : null;
            $warehousedetail_log->qty = (!empty($request->qty)) ? $request->qty : null;
            $warehousedetail_log->action_type = 'Load';
            $warehousedetail_log->save();

            \DB::commit();

            $warehousedetail->getSaveData();
            return prepareResult(true, $warehousedetail, [], "Warehouse detail updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating warehouse detail.", $this->unauthorized);
        }

        $warehousedetail = WarehouseDetail::where('uuid', $uuid)
            ->first();

        if (is_object($warehousedetail)) {

            $warehousedetail_log = new WarehouseDetailLog;
            $warehousedetail_log->warehouse_id = $warehousedetail->warehouse_id;
            $warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
            $warehousedetail_log->item_uom_id = $warehousedetail->item_uom_id;
            $warehousedetail_log->qty = $warehousedetail->qty;
            $warehousedetail_log->action_type = 'Unload';
            $warehousedetail_log->save();

            $warehousedetail->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating warehouse detail", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->warehousedetail_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $warehousedetail = WarehouseDetail::where('uuid', $uuid)
                    ->first();
                if (is_object($warehousedetail)) {

                    $warehousedetail_log = new WarehouseDetailLog;
                    $warehousedetail_log->warehouse_id = $warehousedetail->warehouse_id;
                    $warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
                    $warehousedetail_log->item_uom_id = $warehousedetail->item_uom_id;
                    $warehousedetail_log->qty = $warehousedetail->qty;
                    $warehousedetail_log->action_type = 'Unload';
                    $warehousedetail_log->save();

                    $warehousedetail->delete();
                    $warehousedetail = $this->index();
                }
            }

            return prepareResult(true, $warehousedetail, [], "Warehouse detail deleted success", $this->success);
        }
    }
     /**
     * Display a listing of the resource.
     * Warehouse item qty count get based on the warehouse id and item id.
     * @return \Illuminate\Http\Response
     */
    public function getWarehouseItem(Request $request, $warehouse_id, $item_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        } 
        if(empty($warehouse_id) || is_null($warehouse_id) || $warehouse_id == 0){
             return prepareResult(false, [], "warehouse_id is empty or invalid", "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
        if(empty($item_id) || is_null($item_id) || $item_id == 0){
            return prepareResult(false, [], "item_id is empty or invalid", "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
  
        $warehouse_items_qty_count = WarehouseDetail::where('warehouse_id', $request->warehouse_id)
                                                    ->where('item_id', $request->item_id)
                                                    ->orderBy('id', 'desc')
                                                    ->get(); 

        return prepareResult(true, $warehouse_items_qty_count, [], "Warehouse items qty count detail", $this->success); 
     } 

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'item_id' => 'required',
                'item_uom_id' => 'required',
                'qty' => 'required',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'warehousedetail_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
