<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\SalesmanLoad;
use DB;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\SalesmanUnload;
use App\Model\SalesmanUnloadDetail;
use App\Model\Transaction;
use App\Model\TransactionDetail;
use App\Model\Trip;
use App\Model\ItemMainPrice;
use App\Model\Route;
use App\Model\SalesmanInfo;
use App\Model\SalesmanLoadDetails;
use App\Model\SalesmanTripInfos;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;
use App\User;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;

class SalesmanLoadController extends Controller
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

        $loadheader_query = SalesmanLoad::with(
            'salesmanLoadDetails',
            'salesmanLoadDetails.item:id,item_name,item_code',
            'salesmanLoadDetails.itemUOM:id,name',
            'route:id,route_name,route_code',
            'depot:id,depot_code,depot_name',
            'trip',
            'salesman_infos.user:id,firstname,lastname'
        );

        if ($request->date) {
            $loadheader_query->whereDate('load_date', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->code) {
            $loadheader_query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->load_type) {
            $loadheader_query->where('load_type', 'like', '%' . $request->load_type . '%');
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $loadheader_query->whereHas('salesman_infos.user', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $loadheader_query->whereHas('salesman_infos.user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->salesman_code) {
            $salesman_code = $request->salesman_code;
            $loadheader_query->whereHas('salesman_infos', function ($q) use ($salesman_code) {
                $q->where('salesman_code', 'like', '%' . $salesman_code . '%');
            });
        }

        if ($request->route) {
            $route = $request->route;
            $loadheader_query->whereHas('route', function ($q) use ($route) {
                $q->where('route_name', 'like', '%' . $route . '%');
            });
        }

        if ($request->depot) {
            $depot = $request->depot;
            $loadheader_query->whereHas('depot', function ($q) use ($depot) {
                $q->where('depot_name', 'like', '%' . $depot . '%');
            });
        }

        $loadheader = $loadheader_query->orderBy('id', 'desc')
            ->get();

        $loadheader_array = array();

        if (is_object($loadheader)) {
            foreach ($loadheader as $key => $loadheader1) {
                $loadheader_array[] = $loadheader[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($loadheader_array[$offset])) {
                    $data_array[] = $loadheader_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($loadheader_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($loadheader_array);
        } else {
            $data_array = $loadheader_array;
        }

        return prepareResult(true, $data_array, [], "Load header listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $status = 0;
            // if ($isActivate = checkWorkFlowRule('Load Header', 'create')) {
            //     $status = 0;
            //     $this->createWorkFlowObject($isActivate, 'Invoice',$request);
            // }

            $routes = Route::find($request->route_id);
            $depot_id = $routes->depot_id;

            $loadheader = new SalesmanLoad;
            $loadheader->load_number = nextComingNumber('App\Model\SalesmanLoad', 'load_number', 'load_number', $request->load_number);
            $loadheader->route_id = $request->route_id;
            $loadheader->depot_id = $depot_id;
            $loadheader->salesman_id = $request->salesman_id;
            $loadheader->load_date = $request->load_date;
            $loadheader->load_type = $request->load_type;
            $loadheader->load_confirm = $request->load_confirm;
            $loadheader->status = $status;
            $loadheader->trip_id      = (!empty($request->trip_id)) ? $request->trip_id : null;
            $loadheader->save();

            $salesman_info = new SalesmanTripInfos;
            $salesman_info->trips_id       = $loadheader->trip_id;
            $salesman_info->salesman_id    = $loadheader->salesman_id;
            $salesman_info->status         = 2;
            $salesman_info->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $loaddetail = new SalesmanLoadDetails;
                    $loaddetail->salesman_load_id = $loadheader->id;
                    $loaddetail->route_id = $loadheader->route_id;
                    $loaddetail->salesman_id = $loadheader->salesman_id;
                    $loaddetail->depot_id = $depot_id;
                    $loaddetail->load_date = $loadheader->load_date;
                    $loaddetail->item_id = $item['item_id'];
                    $loaddetail->item_uom = $item['item_uom'];
                    $loaddetail->load_qty = $item['load_qty'];
                    $loaddetail->save();
                    //----------

                    $conversation = getItemDetails2($item['item_id'], $item['item_uom'], $item['load_qty']);

                    $Warehouse = Warehouse::where('depot_id', $depot_id)->first();
                    if (is_object($Warehouse)) {

                        $warehouselocation = Storagelocation::where('warehouse_id', $Warehouse->id)
                            ->where('loc_type', '1')
                            ->first();

                        if (is_object($warehouselocation)) {
                            $routelocation = Storagelocation::where('route_id', $loadheader->route_id)
                                ->where('loc_type', '1')
                                ->first();

                            if (is_object($routelocation)) {
                                $routestoragelocation_id = $routelocation->id;
                                $warehousestoragelocation_id = $warehouselocation->id;

                                $warehouselocation_detail = StoragelocationDetail::where('storage_location_id', $warehousestoragelocation_id)
                                    ->where('item_id', $item['item_id'])
                                    ->first();

                                $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                    ->where('item_id', $item['item_id'])
                                    ->where('item_uom_id', $conversation['UOM'])
                                    ->first();


                                if (is_object($warehouselocation_detail)) {

                                    //if($warehouselocation_detail->item_uom_id == $item['item_uom'])
                                    {
                                        if ($warehouselocation_detail->qty >= $conversation['Qty']) {
                                            $warehouselocation_detail->qty = ($warehouselocation_detail->qty - $conversation['Qty']);
                                            $warehouselocation_detail->save();
                                        } else {
                                            $item_detail = Item::where('id', $item['item_id'])->first();
                                            return prepareResult(false, [], ["error" => "Item is out of stock! the item name is $item_detail->item_name"], " Item is out of stock!  the item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                        }
                                    }
                                } else {
                                    //--------Item not available Error
                                    $item_detail = Item::where('id', $item['item_id'])->first();
                                    return prepareResult(false, [], ["error" => "Item not available!. the item name is $item_detail->item_name"], " Item not available! the item name is  $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                                if (is_object($routelocation_detail)) {

                                    $routelocation_detail->qty = ($routelocation_detail->qty + $conversation['Qty']);
                                    $routelocation_detail->save();
                                } else {

                                    $routestoragedetail = new StoragelocationDetail;
                                    $routestoragedetail->storage_location_id = $routelocation->id;
                                    $routestoragedetail->item_id      = $item['item_id'];
                                    $routestoragedetail->item_uom_id  = $conversation['UOM'];
                                    $routestoragedetail->qty          = $conversation['Qty'];
                                    $routestoragedetail->save();
                                }
                            } else {
                                return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        } else {

                            return prepareResult(false, [], ["error" => "Wherehouse Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    } else {
                        return prepareResult(false, [], ["error" => "Wherehouse not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                    }
                }
            }

            updateNextComingNumber('App\Model\SalesmanLoad', 'load_number');

            \DB::commit();

            $loadheader->getSaveData();

            return prepareResult(true, $loadheader, [], "Load header added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating load header.", $this->unprocessableEntity);
        }
        $loadheader = SalesmanLoad::with(
            'salesmanLoadDetails',
            'salesmanLoadDetails.item:id,item_name,item_code',
            'salesmanLoadDetails.itemUOM:id,name',
            'route:id,route_name,route_code',
            'depot:id,depot_code,depot_name',
            'trip',
            'salesman_infos.user:id,firstname,lastname'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($loadheader)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $loadheader, [], "Salesman Load header Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $loadheader = SalesmanLoad::where('uuid', $uuid)->first();

            SalesmanLoadDetails::where('salesman_load_id', $loadheader->id)->delete();

            if (!$request->depot_id) {
                $routes = Route::where('id', $request->route_id)->first();
                $depot_id = $routes->depot_id;
            } else {
                $depot_id = $request->depot_id;
            }

            $loadheader->load_number         = $request->load_number;
            $loadheader->depot_id            = $depot_id;
            $loadheader->route_id            = $request->route_id;
            $loadheader->salesman_id            = $request->salesman_id;
            $loadheader->load_date       = $request->load_date;
            $loadheader->load_type        = $request->load_type;
            $loadheader->load_confirm        = $request->load_confirm;
            $loadheader->status        = $request->status;
            $loadheader->trip_id      = (!empty($request->trip_id)) ? $request->trip_id : null;
            $loadheader->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    if ($item['id'] != 0) {
                        $loaddetail_old = SalesmanLoadDetails::find($item['id']);

                        $loaddetail = SalesmanLoadDetails::find($item['id']);
                        $loaddetail->salesman_load_id = $loadheader->id;
                        $loaddetail->route_id = $loadheader->route_id;
                        $loaddetail->salesman_id = $loadheader->salesman_id;
                        $loaddetail->depot_id = $depot_id;
                        $loaddetail->load_date = $loadheader->load_date;
                        $loaddetail->item_id = $item['item_id'];
                        $loaddetail->item_uom = $item['item_uom'];
                        $loaddetail->load_qty = $item['load_qty'];
                        $loaddetail->save();
                    } else {
                        $loaddetail = new SalesmanLoadDetails;
                        $loaddetail->salesman_load_id = $loadheader->id;
                        $loaddetail->route_id = $loadheader->route_id;
                        $loaddetail->salesman_id = $loadheader->salesman_id;
                        $loaddetail->depot_id = $depot_id;
                        $loaddetail->load_date = $loadheader->load_date;
                        $loaddetail->item_id = $item['item_id'];
                        $loaddetail->item_uom = $item['item_uom'];
                        $loaddetail->load_qty = $item['load_qty'];
                        $loaddetail->save();
                    }

                    $SalesmanInfo = SalesmanInfo::where('user_id', $request->salesman_id)->first();
                    if (is_object($SalesmanInfo)) {
                        $route_id = $SalesmanInfo->route_id;
                        $routes = Route::find($route_id);
                        if (is_object($routes)) {
                            $depot_id = $routes->depot_id;

                            $Warehouse = Warehouse::where('depot_id', $depot_id)
                                ->where('route_id', $route_id)
                                ->first();

                            if (is_object($Warehouse)) {
                                $warehouse_id = $Warehouse->id;
                                $warehouse_detail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                    ->where('item_id', $item['item_id'])
                                    ->where('item_uom_id', $item['item_uom'])
                                    ->first();

                                if (is_object($warehouse_detail)) {
                                    if (isset($loaddetail_old) && is_object($loaddetail_old)) {
                                        $warehouse_detail->qty = ($warehouse_detail->qty + $loaddetail_old->load_qty);
                                    }
                                    if ($warehouse_detail->qty > $item['load_qty']) {
                                        $warehouse_detail->qty = ($warehouse_detail->qty - $item['load_qty']);
                                        $warehouse_detail->save();
                                    } else {
                                        return prepareResult(false, [], ["error" => "Item is out of stock!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                    }
                                } else {
                                    return prepareResult(false, [], ["error" => "Item not not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                            } else {
                                return prepareResult(false, [], ["error" => "Wherehouse not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        }
                    }
                }
            }

            \DB::commit();

            $loadheader->getSaveData();

            return prepareResult(true, $loadheader, [], "Load header updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating load header.", $this->unprocessableEntity);
        }

        $loadheader = SalesmanLoad::where('uuid', $uuid)->first();

        if (is_object($loadheader)) {
            $loadheaderId = $loadheader->id;
            $loadheader->delete();
            if ($loadheader) {
                SalesmanLoadDetails::where('salesman_load_id', $loadheaderId)->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->salesman_load_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $loadheader = SalesmanLoad::where('uuid', $uuid)->first();
                if (is_object($loadheader)) {
                    $loadheaderId = $loadheader->id;
                    $loadheader->delete();
                    if ($loadheader) {
                        SalesmanLoadDetails::where('salesman_load_id', $loadheaderId)->delete();
                    }
                }
            }
            $loadheader = $this->index();
            return prepareResult(true, $loadheader, [], "Load header deleted success", $this->success);
        }
    }
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'load_number' => 'required',
                'route_id' => 'required',
                'load_date' => 'required',
                'load_type' => 'required',
                'load_confirm' => 'required',
                'status' => 'required',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'salesman_load_ids'     => 'required'
            ]);
        }

        if ($type == 'loadlist') {
            $validator = \Validator::make($input, [
                'route_id'     => 'required',
                'load_date'     => 'required'
            ]);
        }

        if ($type == 'confirm') {
            $validator = \Validator::make($input, [
                'trip_id'        => 'required|integer|exists:trips,id',
                'load_number'     => 'required',
                'route_id'     => 'required',
                'load_date'        => 'required'
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
    public function loadlist(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $input = $request->json()->all();

        $validate = $this->validations($input, "loadlist");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header", $this->unprocessableEntity);
        }

        $route_id = $request->route_id;
        $load_date = date('Y-m-d', strtotime($request->load_date));

        $loadheader = SalesmanLoad::with(
            'salesmanLoadDetails'
        )
            ->where('route_id', $route_id)
            ->where('load_date', '=', $load_date)
            ->where('status', 0)
            ->orderBy('id', 'desc')
            ->get();


        if (is_object($loadheader)) {
            foreach ($loadheader as $key => $header) {
                if (isset($loadheader[$key]->salesmanLoadDetails)) {
                    foreach ($loadheader[$key]->salesmanLoadDetails as $detail_key => $loaddetail) {

                        if (isset($loadheader[$key]->salesmanLoadDetails[$detail_key])) {
                            $item_id = $loadheader[$key]->salesmanLoadDetails[$detail_key]->item_id;

                            $uom_id = $loadheader[$key]->salesmanLoadDetails[$detail_key]->item_uom;
                            $salesman_code_id = $loadheader[$key]->salesmanLoadDetails[$detail_key]->salesman_id;

                            $item = Item::where('id', $item_id)->first();

                            $uom = ItemUom::where('id', $uom_id)->first();

                            $salesman = User::where('id', $salesman_code_id)->first();

                            if ($uom) {
                                $loadheader[$key]->salesmanLoadDetails[$detail_key]->uom_code = $uom->code;
                            }

                            if ($salesman) {
                                $loadheader[$key]->salesmanLoadDetails[$detail_key]->salesman_name = $salesman->firstname;
                            }

                            if (is_object($item)) {
                                //echo '4';	
                                $loadheader[$key]->salesmanLoadDetails[$detail_key]->item_name = $item->item_name;
                                $reults = getItemDetails2($item_id, $loadheader[$key]->salesmanLoadDetails[$detail_key]->item_uom, $loadheader[$key]->salesmanLoadDetails[$detail_key]->load_qty);
                                $loadheader[$key]->salesmanLoadDetails[$detail_key]->load_qty = $reults['Qty'];
                            }
                        }
                    }
                }
            }

            $unloadheader = SalesmanUnload::select('id', 'uuid', 'organisation_id', 'code as load_number', 'id as depo_code', 'route_id', 'transaction_date as load_date', 'unload_type as load_type', 'unload_type as load_confirm', 'status', 'created_at', 'updated_at')
                ->where('unload_type', '4')
                ->where('route_id', $route_id)
                ->where('status', 0)
                ->where('transaction_date', $load_date)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($unloadheader as $key => $trids) {
                // salesman_load_details is salesman_unload_details
                $unloadheader[$key]->salesman_load_details = DB::table('salesman_unload_details')
                    ->join('salesman_unloads', 'salesman_unloads.id', '=', 'salesman_unload_details.salesman_unload_id')
                    ->join('items', 'items.id', '=', 'salesman_unload_details.item_id', 'left')
                    ->join('item_uoms', 'item_uoms.id', '=', 'items.lower_unit_uom_id', 'left')
                    ->join('trips', 'trips.id', '=', 'salesman_unloads.trip_id', 'left')
                    ->join('users', 'trips.salesman_id', '=', 'users.id', 'left')
                    ->select('salesman_unload_details.id', 'salesman_unloads.code as load_number', 'salesman_unload_details.uuid', 'salesman_unload_details.salesman_unload_id', 'trips.route_id as route_code', 'trips.salesman_id as salesman_code', 'users.firstname as salesman_name', 'salesman_unloads.id as depo_code', 'salesman_unloads.transaction_date as load_date', 'salesman_unload_details.item_id', 'items.item_name', 'item_uoms.id as item_uom', 'item_uoms.code as item_uom_code', 'salesman_unload_details.unload_qty as load_qty', 'salesman_unload_details.status as status', 'salesman_unload_details.created_at as created_at', 'salesman_unload_details.updated_at as updated_at', 'salesman_unload_details.deleted_at as deleted_at')
                    ->where('salesman_unload_details.salesman_unload_id', $trids->id)
                    ->where('salesman_unload_details.unload_type', '4')
                    ->where('salesman_unloads.status', '0')
                    ->whereRaw('trips.id', '(select max(`id`) from trips where trip_start_date < "' . $load_date . '")')
                    ->get();

                foreach ($unloadheader[$key]->salesman_load_details as $k => $salesunloaddetail) {
                    $items = Item::where('id', $salesunloaddetail->item_id)
                        ->where('lower_unit_uom_id', $salesunloaddetail->item_uom)
                        ->first();

                    if (is_object($items)) {
                        $qty = $salesunloaddetail->load_qty;
                    } else {
                        $items = ItemMainPrice::where('item_id', $salesunloaddetail->item_id)
                            ->where('item_uom_id', $salesunloaddetail->item_uom)
                            ->first();

                        $qty = $items->item_upc * $salesunloaddetail->load_qty;
                    }
                    $unloadheader[$key]->salesman_load_details[$k]->load_qty = $qty;
                }
            }
        }


        $data = array('LoadData' => $loadheader, 'UnloadData' => $unloadheader);

        return prepareResult(true, $data, [], "Load header listing", $this->success);
    }

    public function loadConfirm(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "confirm");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load header.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            if ($request->load_type == '2') {
                $loadheader = SalesmanUnload::where('code', $request->load_number)
                    ->first();

                if (is_object($loadheader)) {
                    if ($loadheader->status != 1) {
                        $loadheader->status = 1;
                        $loadheader->trip_id = $request->trip_id;
                        $loadheader->save();

                        $salesmancode = Trip::select('salesman_id')
                            ->where('id', $request->trip_id)
                            ->first();

                        $transactionheader = Transaction::where('salesman_id', $salesmancode->salesman_id)
                            ->where('trip_id', $request->trip_id)
                            ->whereDate('transaction_date', $request->load_date)
                            ->first();

                        if (!is_object($transactionheader)) {
                            $transactionheader = new Transaction;
                            $transactionheader->trip_id = $request->trip_id;
                            $transactionheader->salesman_id = $salesmancode->salesman_id;
                            $transactionheader->route_id = $loadheader->route_id;
                            $transactionheader->transaction_type = 1;
                            $transactionheader->transaction_date = $request->load_date;
                            $transactionheader->transaction_time = date('Y-m-d H:i:s');
                            $transactionheader->reference = $request->reference;
                            $transactionheader->source = 1;
                            $transactionheader->save();
                        }

                        $loaddetails = SalesmanUnloadDetail::where('salesman_unload_id', $loadheader->id)->get();

                        if ($loaddetails) {
                            foreach ($loaddetails as $ld) {
                                $transactionheaderde = DB::table('transaction_details')
                                    ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
                                    ->join('trips', 'trips.id', '=', 'transactions.trip_id', 'left')
                                    ->join('users', 'trips.salesman_id', '=', 'users.id', 'left')
                                    ->select('transaction_details.opening_qty')
                                    ->where('item_id', $ld->item_id)
                                    ->where('transaction_details.transaction_id', $transactionheader->id)
                                    ->whereRaw('trips.id', '(select max(`id`) from trips where trip_start_date < "' . $request->load_date . '")')
                                    ->first();


                                // $transactionheaderde = TransactionDetail::with(
                                //     'Transaction',
                                //     'Transaction.trips',
                                //     'Transaction.trips.users'
                                // )
                                //     ->where('item_id', $ld->item_id)
                                //     ->where('transaction_details.transaction_id', $transactionheader->id)
                                //     ->whereRaw('trips.id', '(select max(`id`) from trips where trip_start_date < "' . $request->load_date . '")')
                                //     ->first(); //->get();  

                                //print_r($transactionheaderde);
                                //echo $transactionheaderde->opening_qty;

                                // if lower unit then add same qty
                                // else secondry then add upc -  
                                $item = Item::where('id', $ld->item_id)
                                    ->where('lower_unit_uom_id', $ld->item_uom)
                                    ->first();

                                $qty = 0;

                                if (is_object($item)) {
                                    $qty = $ld->unload_qty;
                                } else {
                                    $item = ItemMainPrice::where('item_id', $ld->item_id)
                                        ->where('item_uom_id', $ld->item_uom)
                                        ->first();

                                    // $qty = $ld->load_qty / $item->item_upc;
                                    $qty = $item->item_upc * $ld->unload_qty;
                                }

                                $transactiondetails = new TransactionDetail;
                                $transactiondetails->transaction_id = $transactionheader->id;
                                $transactiondetails->item_id = $ld->item_id;
                                $transactiondetails->load_qty = $qty;
                                // $transactiondetails->opening_qty = $transactionheaderde['opening_qty'];
                                $transactiondetails->opening_qty = $qty;
                                $transactiondetails->save();
                            }
                        }
                    }
                }
            } else {
                $loadheader = SalesmanLoad::where('load_number', $request->load_number)
                    ->where('route_id', $request->route_id)
                    ->whereDate('load_date', $request->load_date)
                    ->first();

                if (is_object($loadheader)) {
                    if ($loadheader->status != 1) {
                        $loadheader->status = 1;
                        $loadheader->trip_id = $request->trip_id;
                        $loadheader->save();

                        $salesmancode = Trip::select('salesman_id')
                            ->where('id', $request->trip_id)
                            ->first();

                        $transactionheader = Transaction::where('salesman_id', $salesmancode->salesman_id)
                            ->where('trip_id', $request->trip_id)
                            ->whereDate('transaction_date', $request->load_date)
                            ->first();

                        if (!is_object($transactionheader)) {
                            $transactionheader = new Transaction;
                            $transactionheader->trip_id = $request->trip_id;
                            $transactionheader->salesman_id = $salesmancode->salesman_id;
                            $transactionheader->route_id = $loadheader->route_id;
                            $transactionheader->transaction_type = 1;
                            $transactionheader->transaction_date = $request->load_date;
                            $transactionheader->transaction_time = date('Y-m-d H:i:s');
                            $transactionheader->reference = $request->reference;
                            $transactionheader->source = 1;
                            $transactionheader->save();
                        }


                        $loaddetails = SalesmanLoadDetails::where('salesman_load_id', $loadheader->id)->get();
                        if ($loaddetails) {
                            foreach ($loaddetails as $ld) {
                                //$transactionheader->id = 1;
                                //$transactionheaderde->opening_qty = 0;
                                $transactionheaderde = DB::table('transaction_details')
                                    ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
                                    ->join('trips', 'trips.id', '=', 'transactions.trip_id', 'left')
                                    ->join('users', 'trips.salesman_id', '=', 'users.id', 'left')
                                    ->select('transaction_details.opening_qty')
                                    ->where('item_id', $ld->item_id)
                                    ->where('transaction_details.transaction_id', $transactionheader->id)
                                    ->whereRaw('trips.id', '(select max(`id`) from trips where trip_start_date < "' . $request->load_date . '")')
                                    ->first();


                                // $transactionheaderde = TransactionDetail::with(
                                //     'Transaction',
                                //     'Transaction.trips',
                                //     'Transaction.trips.users'
                                // )
                                //     ->where('item_id', $ld->item_id)
                                //     ->where('transaction_details.transaction_id', $transactionheader->id)
                                //     ->whereRaw('trips.id', '(select max(`id`) from trips where trip_start_date < "' . $request->load_date . '")')
                                //     ->first(); //->get();  

                                //print_r($transactionheaderde);
                                //echo $transactionheaderde->opening_qty;

                                // if lower unit then add same qty
                                // else secondry then add upc -  
                                $item = Item::where('id', $ld->item_id)
                                    ->where('lower_unit_uom_id', $ld->item_uom)
                                    ->first();

                                $qty = 0;

                                if (is_object($item)) {
                                    $qty = $ld->load_qty;
                                } else {
                                    $item = ItemMainPrice::where('item_id', $ld->item_id)
                                        ->where('item_uom_id', $ld->item_uom)
                                        ->first();

                                    // $qty = $ld->load_qty / $item->item_upc;
                                    $qty = $item->item_upc * $ld->load_qty;
                                }

                                $transactiondetails = new TransactionDetail;
                                $transactiondetails->transaction_id = $transactionheader->id;
                                $transactiondetails->item_id = $ld->item_id;
                                $transactiondetails->load_qty = $qty;
                                // $transactiondetails->opening_qty = $transactionheaderde['opening_qty'];
                                $transactiondetails->opening_qty = $qty;
                                $transactiondetails->save();
                            }
                        }
                    }
                }
            }
            \DB::commit();
            return prepareResult(true, $loadheader, [], "Load header confirmed", $this->created);


            // \DB::rollback();
            // return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }
}
