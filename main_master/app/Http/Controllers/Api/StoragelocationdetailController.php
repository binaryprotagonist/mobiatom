<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Storagelocation;
use App\Model\Warehouse;
use App\Model\Item;

use App\Model\StoragelocationDetail;


class StoragelocationdetailController extends Controller
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

        $storagelocationdetail = StoragelocationDetail::with(
            'item:id,item_code,item_name',
            'itemUom:id,code,name'
        )
            ->where('storage_location_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        /* $storagelocationdetail_array = array();
        if (is_object($storagelocationdetail)) {
            foreach ($storagelocationdetail as $key => $storagelocationdetai1) {
                $storagelocationdetail_array[] = $storagelocationdetai1[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($storagelocationdetail_array[$offset])) {
                    $data_array[] = $storagelocationdetail_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($storagelocationdetail_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($storagelocationdetail_array);
        } else {
            $data_array = $storagelocationdetail_array;
        }*/

        return prepareResult(true, $storagelocationdetail, [], "Storagelocation detail listing", $this->success);

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
            $conversation = getItemDetails2($request->item_id, $request->item_uom_id, $request->qty);
            // If add already present item same based on  "warehouse" and "item_uom_id", "item id" it will add quentity count in same item               
            $storagelcationddetail = StoragelocationDetail::where('item_id', $request->item_id)
                ->where('item_uom_id', $conversation['UOM'])
                ->where('storage_location_id', $request->storage_location_id)
                ->first();

            if (is_object($storagelcationddetail)) {

                $qty = $storagelcationddetail->qty + $conversation['Qty'];
                $storagelcationddetail->qty = $qty;
                $storagelcationddetail->save();

                // If child warehouse item means reduce item quentiy from paraent warehouse item quentiy  
                $Storagelocation = Storagelocation::where('id', $request->storage_location_id)
                    ->first();

                if (is_object($Storagelocation)) {
                    $storagelocationdetail = StoragelocationDetail::where('item_id', $request->item_id)
                        ->where('item_uom_id', $conversation['UOM'])
                        ->where('storage_location_id', $request->storage_location_id)
                        ->first();

                    $qty = $request->qty;
                    $storagelocationdetail->qty = $conversation['Qty'];
                    $storagelocationdetail->save();
                }
            } else {
                $qty = $request->qty;
                $storagelcationdetail = new StoragelocationDetail;
                $storagelcationdetail->storage_location_id         = $request->storage_location_id;
                $storagelcationdetail->item_id         = $request->item_id;
                $storagelcationdetail->item_uom_id            = $conversation['UOM'];
                $storagelcationdetail->qty            = $conversation['Qty'];
                $storagelcationdetail->save();
            }


            \DB::commit();

            return prepareResult(true, $storagelcationddetail, [], "Storagelocation detail added successfully", $this->created);
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

        $storagelcationddetail = StoragelocationDetail::with(
            'item:id,item_code,item_name',
            'itemUom:id,code,name'
        )
            ->where('uuid', $uuid)
            ->first();


        if (!is_object($storagelcationddetail)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $storagelcationddetail, [], "Warehouse detail Edit", $this->success);
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
            $storagelocationdetai1 = StoragelocationDetail::where('uuid', $uuid)->first();

            // Batch logic need

            $storagelocationdetai1->storage_location_id         = $request->storage_location_id;
            $storagelocationdetai1->item_id         = $request->item_id;
            $storagelocationdetai1->item_uom_id            = $request->item_uom_id;
            $storagelocationdetai1->qty            = $request->qty;
            $storagelocationdetai1->save();



            \DB::commit();

            $storagelocationdetai1->getSaveData();
            return prepareResult(true, $storagelocationdetai1, [], "Warehouse detail updated successfully", $this->created);
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
    public function getStoragelocationItem(Request $request, $Storagelocation_id, $item_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (empty($Storagelocation_id) || is_null($Storagelocation_id) || $Storagelocation_id == 0) {
            return prepareResult(false, [], "Storagelocation_id is empty or invalid", "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
        if (empty($item_id) || is_null($item_id) || $item_id == 0) {
            return prepareResult(false, [], "item_id is empty or invalid", "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }

        $storage_items_qty_count = StoragelocationDetail::where('storage_location_id', $request->storage_location_id)
            ->where('item_id', $request->item_id)
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $storage_items_qty_count, [], "Storagelocation items qty count detail", $this->success);
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
