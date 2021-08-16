<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\VantovanTransfer;
use App\Model\VantovanTransferdetail;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;
use App\Model\WarehouseDetailLog;

class VantovanTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $VantovanTransferQuery = VantovanTransfer::with(
            'sourceroute:id,route_name',
            'destinationroute:id,route_name',
            'vantovantransferdetail',
            'vantovantransferdetail.item:id,item_name',
            'vantovantransferdetail.itemUom:id,name,code'
        );


        if ($request->date) {
			$VantovanTransferQuery->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
		}

		if ($request->code) {
			$VantovanTransferQuery->where('code', 'like', '%' . $request->code . '%');
		}

        if ($request->status) {
			$VantovanTransferQuery->where('status', 'like', '%' . $request->status . '%');
		}

		if ($request->sourceroute) {
			$route_name = $request->sourceroute;
			$VantovanTransferQuery->whereHas('sourceroute', function ($q) use ($route_name) {
				$q->where('route_name', 'like', $route_name);
			});
		}

		if ($request->destinationroute) {
			$route_name = $request->destinationroute;
			$VantovanTransferQuery->whereHas('destinationroute', function ($q) use ($route_name) {
				$q->where('route_name', 'like', $route_name);
			});
		}

        $VantovanTransfer = $VantovanTransferQuery->orderBy('id', 'desc')
            ->get();

        $VantovanTransfer_array = array();
        if (is_object($VantovanTransfer)) {
            foreach ($VantovanTransfer as $key => $VantovanTransfer1) {
                $VantovanTransfer_array[] = $VantovanTransfer[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($VantovanTransfer_array[$offset])) {
                    $data_array[] = $VantovanTransfer_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($VantovanTransfer_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($VantovanTransfer_array);
        } else {
            $data_array = $VantovanTransfer_array;
        }
        return prepareResult(true, $data_array, [], "Van to van Transfer listing", $this->success, $pagination);

        // return prepareResult(true, $VantovanTransfer, [], "Van to van Transfer listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Van to van Transfer", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $VantovanTransfer = new VantovanTransfer;
            $VantovanTransfer->source_route_id            = (!empty($request->source_route_id)) ? $request->source_route_id : null;
            $VantovanTransfer->destination_route_id            = (!empty($request->destination_route_id)) ? $request->destination_route_id : null;
            $VantovanTransfer->code            = (!empty($request->code)) ? $request->code : null;
            $VantovanTransfer->code            = nextComingNumber('App\Model\VantovanTransfer', 'van_to_van_transfer', 'code', $request->code);
            $VantovanTransfer->date       = date('Y-m-d', strtotime($request->date));
            $VantovanTransfer->status            = 'Pending';
            $VantovanTransfer->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $VantovanTransferdetail = new VantovanTransferdetail;
                    $VantovanTransferdetail->vantovantransfer_id      = $VantovanTransfer->id;
                    $VantovanTransferdetail->item_id       = $item['item_id'];
                    $VantovanTransferdetail->item_uom_id   = $item['item_uom_id'];
                    $VantovanTransferdetail->quantity   = $item['quantity'];
                    $VantovanTransferdetail->save();
                }
            }

            \DB::commit();

            updateNextComingNumber('App\Model\VantovanTransfer', 'van_to_van_transfer');

            $VantovanTransfer->getSaveData();

            return prepareResult(true, $VantovanTransfer, [], "Van to van Transfer added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating Van to van Transfer.", $this->unauthorized);
        }

        $VantovanTransfer = VantovanTransfer::with(
            'sourceroute:id,route_name',
            'destinationroute:id,route_name',
            'vantovantransferdetail',
            'vantovantransferdetail.item:id,item_name',
            'vantovantransferdetail.itemUom:id,name,code'
        )
            ->where('uuid', $uuid)
            ->get();

        if (isset($VantovanTransfer->vantovantransferdetail)) {
            if (count($VantovanTransfer->vantovantransferdetail) > 0) {
                foreach ($VantovanTransfer->vantovantransferdetail as $key => $row) {
                    if ($VantovanTransfer->vantovantransferdetail[$key]->item) {
                        $VantovanTransfer->vantovantransferdetail[$key]->item_name = $VantovanTransfer->vantovantransferdetail[$key]->item->item_name;
                        //unset($VantovanTransfer->vantovantransferdetail[$key]->item);
                    } else {
                        $VantovanTransfer->vantovantransferdetail[$key]->item_name = '';
                    }
                    if ($VantovanTransfer->vantovantransferdetail[$key]->itemUom) {
                        $VantovanTransfer->vantovantransferdetail[$key]->item_uom_name = $VantovanTransfer->vantovantransferdetail[$key]->itemUom->name;
                        //unset($VantovanTransfer->vantovantransferdetail[$key]->itemUom);
                    } else {
                        $VantovanTransfer->vantovantransferdetail[$key]->item_uom_name = '';
                    }
                }
            }
        }

        if (!is_object($VantovanTransfer)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $VantovanTransfer, [], "Van to van Transfer Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Van to van Transfer.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $VantovanTransfer = VantovanTransfer::where('uuid', $uuid)->first();
            $VantovanTransfer->source_route_id            = (!empty($request->source_route_id)) ? $request->source_route_id : null;
            $VantovanTransfer->destination_route_id            = (!empty($request->destination_route_id)) ? $request->destination_route_id : null;
            $VantovanTransfer->code            = (!empty($request->code)) ? $request->code : null;
            $VantovanTransfer->date       = date('Y-m-d', strtotime($request->date));
            $VantovanTransfer->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    if ($item['id'] > 0) {
                        $VantovanTransferdetail = VantovanTransferdetail::find($item['id']);
                        $VantovanTransferdetail->vantovantransfer_id      = $VantovanTransfer->id;
                        $VantovanTransferdetail->item_id       = $item['item_id'];
                        $VantovanTransferdetail->item_uom_id   = $item['item_uom_id'];
                        $VantovanTransferdetail->quantity   = $item['quantity'];
                        $VantovanTransferdetail->save();
                    } else {
                        $VantovanTransferdetail = new VantovanTransferdetail;
                        $VantovanTransferdetail->vantovantransfer_id      = $VantovanTransfer->id;
                        $VantovanTransferdetail->item_id       = $item['item_id'];
                        $VantovanTransferdetail->item_uom_id   = $item['item_uom_id'];
                        $VantovanTransferdetail->quantity   = $item['quantity'];
                        $VantovanTransferdetail->save();
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $VantovanTransfer, [], "Van to van Transfer updated successfully", $this->success);

            $VantovanTransfer->getSaveData();
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
            return prepareResult(false, [], [], "Error while validating van to van transfer.", $this->unauthorized);
        }

        $VantovanTransfer = VantovanTransfer::where('uuid', $uuid)
            ->first();

        if (is_object($VantovanTransfer)) {
            $VantovanTransferId = $VantovanTransfer->id;
            $VantovanTransfer->delete();
            if ($VantovanTransfer) {
                VantovanTransferdetail::where('vantovantransfer_id', $VantovanTransferId)->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Van to van Transfer", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->vantovantransfer_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $VantovanTransfer = VantovanTransfer::where('uuid', $uuid)
                    ->first();
                if (is_object($VantovanTransfer)) {
                    $VantovanTransferId = $VantovanTransfer->id;
                    $VantovanTransfer->delete();
                    if ($VantovanTransfer) {
                        VantovanTransferdetail::where('vantovantransfer_id', $VantovanTransferId)->delete();
                    }
                    return prepareResult(true, [], [], "Record delete successfully", $this->success);
                }
            }
            $VantovanTransfer = $this->index();
            return prepareResult(true, $VantovanTransfer, [], "Van to van Transfer deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'source_route_id' => 'required|integer|exists:routes,id',
                'destination_route_id' => 'required|integer|exists:routes,id',
                'code' => 'required',
                'date' => 'required|date',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'vantovantransfer_ids'     => 'required'
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
     * @param  int  $route_id
     * @return \Illuminate\Http\Response
     */

    public function itemlist($route_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $items = Warehouse::with(
            'warehouseDetails.item:id,item_name',
            'warehouseDetails.itemUom:id,name,code'
        )
            ->where('route_id', $route_id)
            ->get();

        return prepareResult(true, $items, [], "Items listing", $this->success);
    }

    /**
     * Get price specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function accept($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating van to van transfer.", $this->unauthorized);
        }

        $VantovanTransfer = VantovanTransfer::find($id);
        $VantovanTransfer->status = 'Approve';
        $VantovanTransfer->save();
        $vantovantransferdetail = VantovanTransferdetail::where('vantovantransfer_id', $id)->get();
        if (is_object($vantovantransferdetail)) {
            foreach ($vantovantransferdetail as $detail) {
                //source warehouse
                $sourcewarehouse = Warehouse::where('route_id', $VantovanTransfer->source_route_id)->first();
                $sourcewarehousedetail = WarehouseDetail::where('warehouse_id', $sourcewarehouse->id)
                    ->where('item_id', $detail->item_id)
                    ->where('item_uom_id', $detail->item_uom_id)
                    ->first();
                if (is_object($sourcewarehousedetail)) {
                    $sourcewarehousedetail->qty = ($sourcewarehousedetail->qty - $detail->quantity);
                    $sourcewarehousedetail->save();
                } else {
                    $sourcewarehousedetail = new WarehouseDetail;
                    $sourcewarehousedetail->warehouse_id         = $sourcewarehouse->id;
                    $sourcewarehousedetail->item_id         = $detail->item_id;
                    $sourcewarehousedetail->item_uom_id            = $detail->item_uom_id;
                    $sourcewarehousedetail->qty            = (0 - $detail->quantity);
                    $sourcewarehousedetail->batch       = "";
                    $sourcewarehousedetail->save();
                }

                $sourcewarehousedetail_log = new WarehouseDetailLog;
                $sourcewarehousedetail_log->warehouse_id = $sourcewarehouse->id;
                $sourcewarehousedetail_log->warehouse_detail_id = $sourcewarehousedetail->id;
                $sourcewarehousedetail_log->item_uom_id = $detail->item_uom_id;
                $sourcewarehousedetail_log->qty = $detail->quantity;
                $sourcewarehousedetail_log->action_type = 'Unload';
                $sourcewarehousedetail_log->save();
                //source warehouse

                //destination warehouse
                $destinationwarehouse = Warehouse::where('route_id', $VantovanTransfer->destination_route_id)->first();
                $destinationwarehousedetail = WarehouseDetail::where('warehouse_id', $destinationwarehouse->id)
                    ->where('item_id', $detail->item_id)
                    ->where('item_uom_id', $detail->item_uom_id)
                    ->first();
                if (is_object($destinationwarehousedetail)) {
                    $destinationwarehousedetail->qty = ($destinationwarehousedetail->qty + $detail->quantity);
                    $destinationwarehousedetail->save();
                } else {
                    $destinationwarehousedetail = new WarehouseDetail;
                    $destinationwarehousedetail->warehouse_id         = $destinationwarehouse->id;
                    $destinationwarehousedetail->item_id         = $detail->item_id;
                    $destinationwarehousedetail->item_uom_id            = $detail->item_uom_id;
                    $destinationwarehousedetail->qty            = $detail->quantity;
                    $destinationwarehousedetail->batch       = "";
                    $destinationwarehousedetail->save();
                }

                $destinationwarehousedetail_log = new WarehouseDetailLog;
                $destinationwarehousedetail_log->warehouse_id = $destinationwarehouse->id;
                $destinationwarehousedetail_log->warehouse_detail_id = $destinationwarehousedetail->id;
                $destinationwarehousedetail_log->item_uom_id = $detail->item_uom_id;
                $destinationwarehousedetail_log->qty = $detail->quantity;
                $destinationwarehousedetail_log->action_type = 'Load';
                $destinationwarehousedetail_log->save();
                //destination warehouse
            }
        }

        return prepareResult(true, $VantovanTransfer, [], "Request accepted", $this->success);
    }

    public function liststock()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $VantovanTransfer = VantovanTransfer::with(
            'sourceroute:id,route_name',
            'destinationroute:id,route_name',
            'vantovantransferdetail'
        )
            ->where('status', 'Pending')
            ->get();

        $VantovanTransfer_array = array();
        if (is_object($VantovanTransfer)) {
            foreach ($VantovanTransfer as $key => $VantovanTransfer1) {
                $VantovanTransfer_array[] = $VantovanTransfer[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($VantovanTransfer_array[$offset])) {
                    $data_array[] = $VantovanTransfer_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($VantovanTransfer_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($VantovanTransfer_array);
        } else {
            $data_array = $VantovanTransfer_array;
        }
        return prepareResult(true, $data_array, [], "Van to van Transfer listing", $this->success, $pagination);

        // return prepareResult(true, $VantovanTransfer, [], "Van to van Transfer listing", $this->success);
    }
}
