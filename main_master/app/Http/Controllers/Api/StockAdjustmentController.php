<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Accounts;
use App\Model\StockAdjustment;
use App\Model\StockAdjustmentDetail;
use App\Model\WarehouseDetail;

class StockAdjustmentController extends Controller
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

        $Stockadjustment = StockAdjustment::with(
            'Accounts:id,account_name',
            'warehouse:id,name',
            'reason:id,name',
            'stockadjustmentdetail',
            'stockadjustmentdetail.item:id,item_name',
            'stockadjustmentdetail.itemUom:id,name,code'
        )
        ->orderBy('id', 'desc')
            ->get();

        $Stockadjustment_array = array();
        if (is_object($Stockadjustment)) {
            foreach ($Stockadjustment as $key => $Stockadjustment1) {
                $Stockadjustment_array[] = $Stockadjustment[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($Stockadjustment_array[$offset])) {
                    $data_array[] = $Stockadjustment_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($Stockadjustment_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($Stockadjustment_array);
        } else {
            $data_array = $Stockadjustment_array;
        }

        return prepareResult(true, $data_array, [], "Stock adjustment listing", $this->success, $pagination);

        // return prepareResult(true, $Stockadjustment, [], "Stock adjustment listing", $this->success);
    }

    /**
     * get available quantity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getquantity(Request $request)
    {
        $quantity = 0;
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "getquantity");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating get available quantity", $this->unprocessableEntity);
        }
        $warehouse_id = $request->warehouse_id;
        $item_id = $request->item_id;
        $item_uom_id = $request->item_uom_id;
        $warehousedetail = WarehouseDetail::where('warehouse_id', $warehouse_id)
            ->where('item_id', $item_id)
            ->where('item_uom_id', $item_uom_id)
            ->orderBy('id', 'desc')
            ->get();
        if (is_object($warehousedetail)) {
            foreach ($warehousedetail as $detail) {
                $quantity = ($quantity + $detail->qty);
            }
        }
        return prepareResult(true, ['quantity' => $quantity], [], "Available Quantity", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating stock adjustment", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $stockadjustment = new StockAdjustment;
            $stockadjustment->account_id            = (!empty($request->account_id)) ? $request->account_id : null;
            $stockadjustment->warehouse_id            = (!empty($request->warehouse_id)) ? $request->warehouse_id : null;
            $stockadjustment->adjustment_mode            = (!empty($request->adjustment_mode)) ? $request->adjustment_mode : 'Quantity';
            $stockadjustment->reason_id            = (!empty($request->reason_id)) ? $request->reason_id : null;
            $stockadjustment->reference_number            = (!empty($request->reference_number)) ? $request->reference_number : null;
            $stockadjustment->stock_adjustment_date       = date('Y-m-d', strtotime($request->stock_adjustment_date));
            $stockadjustment->description            = (!empty($request->description)) ? $request->description : null;
            $stockadjustment->status            = (!empty($request->status)) ? $request->status : 'Draft';
            $stockadjustment->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $stockadjustmentdetail = new StockAdjustmentDetail;
                    $stockadjustmentdetail->stock_adjustment_id      = $stockadjustment->id;
                    $stockadjustmentdetail->item_id       = $item['item_id'];
                    $stockadjustmentdetail->item_uom_id   = $item['item_uom_id'];
                    $stockadjustmentdetail->available_qty   = $item['available_qty'];
                    $stockadjustmentdetail->new_qty       = $item['new_qty'];
                    $stockadjustmentdetail->adjusted_qty   = $item['adjusted_qty'];
                    $stockadjustmentdetail->save();

                    if ($request->status == 'Adjustment') {
                        $warehousedetail = WarehouseDetail::where('warehouse_id', $request->warehouse_id)
                            ->where('item_id', $item['item_id'])
                            ->where('item_uom_id', $item['item_uom_id'])
                            ->orderby('id', 'DESC')
                            ->first();
                        if (is_object($warehousedetail)) {
                            $quantity = ($warehousedetail->qty - $item['adjusted_qty']);
                            $warehousedetail->qty = $quantity;
                            $warehousedetail->save();
                        }
                    }
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\StockAdjustment', 'stock_adjustment');

            $stockadjustment->getSaveData();

            return prepareResult(true, $stockadjustment, [], "Stock adjustment added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating stock adjustment.", $this->unauthorized);
        }

        $Stockadjustment = StockAdjustment::with(
            'Accounts:id,account_name',
            'warehouse:id,name',
            'reason:id,name',
            'stockadjustmentdetail',
            'stockadjustmentdetail.item:id,item_name',
            'stockadjustmentdetail.itemUom:id,name,code'
        )
            ->where('uuid', $uuid)
            ->orderBy('id', 'desc')
            ->get();
        /* if(isset($Stockadjustment->stockadjustmentdetail)){
			if(count($Stockadjustment->stockadjustmentdetail)>0){
				foreach($Stockadjustment->stockadjustmentdetail as $key=>$row){
					if($Stockadjustment->stockadjustmentdetail[$key]->item){
						$Stockadjustment->stockadjustmentdetail[$key]->item_name = $Stockadjustment->stockadjustmentdetail[$key]->item->item_name;
						//unset($Stockadjustment->stockadjustmentdetail[$key]->item);
					}
					if($Stockadjustment->stockadjustmentdetail[$key]->itemUom){
						$Stockadjustment->stockadjustmentdetail[$key]->item_uom_name = $Stockadjustment->stockadjustmentdetail[$key]->itemUom->name;
						//unset($Stockadjustment->stockadjustmentdetail[$key]->itemUom);
					}
				}
			}
		} */
        if (!is_object($Stockadjustment)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $Stockadjustment, [], "Stock adjustment Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating stock adjustment.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $stockadjustment = StockAdjustment::where('uuid', $uuid)->first();
            $stockadjustment->account_id            = (!empty($request->account_id)) ? $request->account_id : null;
            $stockadjustment->warehouse_id            = (!empty($request->warehouse_id)) ? $request->warehouse_id : null;
            $stockadjustment->adjustment_mode            = (!empty($request->adjustment_mode)) ? $request->adjustment_mode : 'Quantity';
            $stockadjustment->reason_id            = (!empty($request->reason_id)) ? $request->reason_id : null;
            $stockadjustment->reference_number            = (!empty($request->reference_number)) ? $request->reference_number : null;
            $stockadjustment->stock_adjustment_date       = date('Y-m-d', strtotime($request->stock_adjustment_date));
            $stockadjustment->description            = (!empty($request->description)) ? $request->description : null;
            $stockadjustment->status            = (!empty($request->status)) ? $request->status : 'Draft';
            $stockadjustment->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    if ($item['id'] > 0) {
                        $stockadjustmentdetail = StockAdjustmentDetail::where('id', $item['id'])->first();

                        $new_adjusted_qty = $item['adjusted_qty'];

                        if ($request->status == 'Adjustment') {
                            $warehousedetail = WarehouseDetail::where('warehouse_id', $request->warehouse_id)
                                ->where('item_id', $item['item_id'])
                                ->where('item_uom_id', $item['item_uom_id'])
                                ->orderby('id', 'DESC')
                                ->first();
                            if (is_object($warehousedetail)) {
                                $warehousedetail->qty = ($warehousedetail->qty - $new_adjusted_qty);
                                $warehousedetail->save();
                            }
                        }

                        $stockadjustmentdetail->stock_adjustment_id      = $stockadjustment->id;
                        $stockadjustmentdetail->item_id       = $item['item_id'];
                        $stockadjustmentdetail->item_uom_id   = $item['item_uom_id'];
                        $stockadjustmentdetail->available_qty   = $item['available_qty'];
                        $stockadjustmentdetail->new_qty       = $item['new_qty'];
                        $stockadjustmentdetail->adjusted_qty   = $item['adjusted_qty'];
                        $stockadjustmentdetail->save();
                    } else {
                        $stockadjustmentdetail = new StockAdjustmentDetail;
                        $stockadjustmentdetail->stock_adjustment_id      = $stockadjustment->id;
                        $stockadjustmentdetail->item_id       = $item['item_id'];
                        $stockadjustmentdetail->item_uom_id   = $item['item_uom_id'];
                        $stockadjustmentdetail->available_qty   = $item['available_qty'];
                        $stockadjustmentdetail->new_qty       = $item['new_qty'];
                        $stockadjustmentdetail->adjusted_qty   = $item['adjusted_qty'];
                        $stockadjustmentdetail->save();

                        if ($request->status == 'Adjustment') {
                            $warehousedetail = WarehouseDetail::where('warehouse_id', $request->warehouse_id)
                                ->where('item_id', $item['item_id'])
                                ->where('item_uom_id', $item['item_uom_id'])
                                ->orderby('id', 'DESC')
                                ->first();
                            if (is_object($warehousedetail)) {
                                $quantity = ($warehousedetail->qty - $item['adjusted_qty']);
                                $warehousedetail->qty = $quantity;
                                $warehousedetail->save();
                            }
                        }
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $stockadjustment, [], "Stock adjustment updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating stock adjustment.", $this->unauthorized);
        }

        $stockadjustment = StockAdjustment::where('uuid', $uuid)
            ->first();

        if (is_object($stockadjustment)) {
            $stockadjustmentId = $stockadjustment->id;
            $warehouse_id = $stockadjustment->warehouse_id;
            $stockadjustment->delete();
            if ($stockadjustment) {
                $stockadjustmentdetail = StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->get();
                if (is_object($stockadjustmentdetail)) {
                    foreach ($stockadjustmentdetail as $detail) {
                        $warehousedetail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                            ->where('item_id', $detail->item_id)
                            ->where('item_uom_id', $detail->item_uom_id)
                            ->orderby('id', 'DESC')
                            ->first();
                        if (is_object($warehousedetail)) {
                            $quantity = ($warehousedetail->qty + $detail->qty);
                            $warehousedetail->qty = $quantity;
                            $warehousedetail->save();
                        }
                    }
                }
                StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating stock adjustment", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->stockadjustment_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $stockadjustment = StockAdjustment::where('uuid', $uuid)
                    ->first();

                if (is_object($stockadjustment)) {
                    $stockadjustmentId = $stockadjustment->id;
                    $warehouse_id = $stockadjustment->warehouse_id;
                    $stockadjustment->delete();
                    if ($stockadjustment) {
                        $stockadjustmentdetail = StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->get();
                        if (is_object($stockadjustmentdetail)) {
                            foreach ($stockadjustmentdetail as $detail) {
                                $warehousedetail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                    ->where('item_id', $detail->item_id)
                                    ->where('item_uom_id', $detail->item_uom_id)
                                    ->orderby('id', 'DESC')
                                    ->first();
                                if (is_object($warehousedetail)) {
                                    $quantity = ($warehousedetail->qty + $detail->qty);
                                    $warehousedetail->qty = $quantity;
                                    $warehousedetail->save();
                                }
                            }
                        }
                        StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->delete();
                    }
                }
            }
            $stockadjustment = $this->index();
            return prepareResult(true, $stockadjustment, [], "Stock adjustment deleted success", $this->success);
        }
        if ($action == 'adjustment') {
            foreach ($uuids as $uuid) {
                $stockadjustment = StockAdjustment::where('uuid', $uuid)
                    ->first();

                if (is_object($stockadjustment)) {
                    $stockadjustmentId = $stockadjustment->id;
                    $warehouse_id = $stockadjustment->warehouse_id;

                    $stockadjustmentdetail = StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->get();
                    if (is_object($stockadjustmentdetail)) {
                        foreach ($stockadjustmentdetail as $detail) {
                            $warehousedetail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                ->where('item_id', $detail->item_id)
                                ->where('item_uom_id', $detail->item_uom_id)
                                ->orderby('id', 'DESC')
                                ->first();
                            if (is_object($warehousedetail)) {
                                $quantity = ($warehousedetail->qty - $detail->adjusted_qty);
                                $warehousedetail->qty = $quantity;
                                $warehousedetail->save();
                            }
                        }
                    }
                    StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->delete();
                }
            }
            $stockadjustment = $this->index();
            return prepareResult(true, $stockadjustment, [], "Stock adjustment deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'account_id' => 'required|integer|exists:accounts,id',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'reason_id' => 'required|integer|exists:reasons,id',
                'adjustment_mode' => 'required',
                'reference_number' => 'required',
                'stock_adjustment_date' => 'required|date',
                'description' => 'required',
                'status' => 'required',
            ]);
        }

        if ($type == 'getquantity') {
            $validator = \Validator::make($input, [
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'item_id' => 'required|integer|exists:items,id',
                'item_uom_id' => 'required|integer|exists:item_uoms,id',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'stockadjustment_ids'     => 'required'
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

    public function convertoadjustment($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating stock adjustment.", $this->unauthorized);
        }

        $stockadjustment = StockAdjustment::where('uuid', $uuid)
            ->first();

        if (is_object($stockadjustment)) {
            $stockadjustmentId = $stockadjustment->id;
            $warehouse_id = $stockadjustment->warehouse_id;

            $stockadjustmentdetail = StockAdjustmentDetail::where('stock_adjustment_id', $stockadjustmentId)->get();
            if (is_object($stockadjustmentdetail)) {
                foreach ($stockadjustmentdetail as $detail) {
                    $warehousedetail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                        ->where('itevalidationsm_id', $detail->item_id)
                        ->where('item_uom_id', $detail->item_uom_id)
                        ->orderby('id', 'DESC')
                        ->first();
                    if (is_object($warehousedetail)) {
                        $quantity = ($warehousedetail->qty - $detail->adjusted_qty);
                        $warehousedetail->qty = $quantity;
                        $warehousedetail->save();
                    }
                }
            }

            $stockadjustment = Stockadjustment::find($stockadjustmentId);
            $stockadjustment->status = 'Adjustment';
            $stockadjustment->save();

            return prepareResult(true, [], [], "Record successfully converted to adjustment", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }
}
