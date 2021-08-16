<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Delivery;
use App\Model\DeliveryDetail;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\CustomerInfo;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\Route;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Imports\DeliveryImport;
use App\Imports\DeliveryUpdateImport;
use App\Model\CodeSetting;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\SalesmanInfo;
use App\Model\SalesmanLoad;
use App\Model\SalesmanLoadDetails;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;
use App\Model\WorkFlowRuleApprovalUser;

class DeliveryController extends Controller
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

        $delivery = Delivery::with(
            'order',
            // 'customerInfo',
            // 'customerInfo.user',
            // 'salesmanInfo',
            // 'salesmanInfo.user',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'route:id,route_name,route_code',
            'invoice',
            'paymentTerm',
            'deliveryDetails',
            'deliveryDetails.item',
            'deliveryDetails.itemUom',
            'lob'
        );

        if ($request->customer_name) {
            $customer_name = $request->customer_name;
            $exploded_name = explode(" ", $customer_name);
            if (count($exploded_name) < 2) {
                $delivery->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $delivery->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $delivery->whereHas('customer.customerInfo', function ($q) use ($customer_code) {
                $q->where('customer_code', 'like', '%' . $customer_code . '%');
            });
        }

        if ($request->route_name) {
            $route_name = $request->route_name;
            $delivery->whereHas('route', function ($q) use ($route_name) {
                $q->where('route_name', 'like',  '%' . $route_name . '%');
            });
        }

        if ($request->route_code) {
            $route_code = $request->route_code;
            $delivery->whereHas('route', function ($q) use ($route_code) {
                $q->where('route_code', 'like',  '%' . $route_code . '%');
            });
        }

        if ($request->date) {
            $delivery->whereDate('created_at', $request->date);
        }

        if ($request->delivery_date) {
            $delivery->whereDate('delivery_date', $request->delivery_date);
        }

        if ($request->status) {
            $delivery->where('current_stage', 'like', '%' . $request->status . '%');
        }

        if ($request->code) {
            $delivery->where('delivery_number', 'like', '%' . $request->code . '%');
        }

        $delivery_detail = $delivery->orderBy('id', 'desc')
            ->get();

        // approval
        $results = GetWorkFlowRuleObject('Deliviery');
        $approve_need_delivery_detail = array();
        $approve_need_delivery_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_delivery_detail[] = $raw['object']->raw_id;
                $approve_need_delivery_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $delivery_detail_array = array();
        if (is_object($delivery_detail)) {
            foreach ($delivery_detail as $key => $delivery_detail1) {
                if (in_array($delivery_detail[$key]->id, $approve_need_delivery_detail)) {
                    $delivery_detail[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_delivery_detail_object_id[$delivery_detail[$key]->id])) {
                        $delivery_detail[$key]->objectid = $approve_need_delivery_detail_object_id[$delivery_detail[$key]->id];
                    } else {
                        $delivery_detail[$key]->objectid = '';
                    }
                } else {
                    $delivery_detail[$key]->need_to_approve = 'no';
                    $delivery_detail[$key]->objectid = '';
                }

                if ($delivery_detail[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($delivery_detail[$key]->id, $approve_need_delivery_detail)) {
                    $delivery_detail_array[] = $delivery_detail[$key];
                }
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($delivery_detail_array[$offset])) {
                    $data_array[] = $delivery_detail_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($delivery_detail_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($delivery_detail_array);
        } else {
            $data_array = $delivery_detail_array;
        }

        return prepareResult(true, $data_array, [], "Delivery listing", $this->success, $pagination);
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

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating delivery", $this->unprocessableEntity);
        }

        $direct_delivery = $request->delivery_type_source;
        if (isset($direct_delivery) && $direct_delivery == 1) {
            if (isset($request->order_id) && $request->order_id) {
                return prepareResult(false, [], 'Order Id is required', "Error while validating delivery", $this->unprocessableEntity);
            }
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        if (!empty($request->route_id)) {
            $route_id = $request->route_id;
        } else if (!empty($request->salesman_id)) {
            $route_id = getRouteBySalesman($request->salesman_id);
        }

        \DB::beginTransaction();
        try {

            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Deliviery', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Deliviery);
            }

            //item check before deduct from warehouse
            if (is_array($request->items)) {
                foreach ($request->items as $deliveryItem) {

                    $customerInfo = CustomerInfo::where('id', $request->customer_id)->first();

                    if (is_object($customerInfo)) {
                        $route_id = $customerInfo->route_id;
                        $routes = Route::find($route_id);

                        if (is_object($routes)) {
                            $depot_id = $routes->depot_id;
                            $Warehouse = Warehouse::where('depot_id', $depot_id)->first();

                            if (is_object($Warehouse)) {
                                $warehouse_id = $Warehouse->id;
                                // only Item Check
                                $warehouse_detail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                    ->where('item_id', $deliveryItem['item_id'])
                                    ->first();

                                if (!is_object($warehouse_detail)) {
                                    $item_detail = Item::where('id', $deliveryItem['item_id'])->first();
                                    return prepareResult(false, [], ["error" => "Item not available! the item name is $item_detail->item_name"], " Item not available! the item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }

                                // if get item in warehouse
                                $warehouse_detail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                    ->where('item_id', $deliveryItem['item_id'])
                                    ->where('item_uom_id', $deliveryItem['item_uom_id'])
                                    ->first();

                                if (is_object($warehouse_detail)) {

                                    if (!($warehouse_detail->qty > $deliveryItem['item_qty'])) {
                                        $item_detail = Item::where('id', $deliveryItem['item_id'])->first();
                                        return prepareResult(false, [], ["error" => "Item is out of stock! item name is $item_detail->item_name"], "Item is out of stock! item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                    }
                                } else {
                                    // lower unit
                                    $item_table = Item::where('id', $deliveryItem['item_id'])->where('lower_unit_uom_id', $deliveryItem['item_uom'])->first();

                                    if (is_object($item_table)) {
                                        $upc = $item_table->lower_unit_item_upc;
                                        $total_qty = $deliveryItem['item_qty'] / $upc;

                                        $warehouse_detail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                            ->where('item_id', $deliveryItem['item_id'])
                                            ->first();

                                        if (!($warehouse_detail->qty > $total_qty)) {
                                            $item_detail = Item::where('id', $deliveryItem['item_id'])->first();
                                            return prepareResult(false, [], ["error" => "Item is out of stock! item name is $item_detail->item_name"], " Item is out of stock! item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                        }
                                    } else {
                                        // main price - secondry uom
                                        $ItemMainPrice_result = ItemMainPrice::where('item_id', $deliveryItem['item_id'])->where('item_uom_id', $deliveryItem['item_uom'])->first();
                                        $upc = $ItemMainPrice_result->item_upc;
                                        $total_qty = $deliveryItem['item_qty'] / $upc;

                                        $warehouse_detail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                            ->where('item_id', $deliveryItem['item_id'])
                                            ->first();

                                        if ($warehouse_detail->qty > $total_qty) {
                                            $item_detail = Item::where('id', $deliveryItem['item_id'])->first();
                                            return prepareResult(false, [], ["error" => "Item is out of stock! item name is $item_detail->item_name"], "Item is out of stock! item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                        }
                                    }
                                }
                            } else {
                                return prepareResult(false, [], ["error" => "Wherehouse not available!"], " Wherehouse not available! Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        }
                    }
                } //for loop end        
            } else {
                return prepareResult(false, [], ["error" => "There is no item for delivery"], "There is no item for delivery Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            }  //item check before deduct from warehouse end


            $delivery = new Delivery;
            $delivery->customer_id   = $request->customer_id;
            $delivery->order_id = $request->order_id;
            $delivery->salesman_id = $request->salesman_id;
            $delivery->route_id = (!empty($route_id)) ? $route_id : null;
            if ($request->source == 1) {
                $delivery->delivery_number = $request->delivery_number;
            } else {
                $delivery->delivery_number = nextComingNumber('App\Model\Delivery', 'delivery', 'delivery_number', $request->delivery_number);
            }
            $delivery->delivery_type = $request->delivery_type;
            $delivery->delivery_date = $request->delivery_date;
            $delivery->delivery_time = (isset($request->delivery_time)) ? $request->delivery_time : date('H:m:s');
            $delivery->delivery_weight = $request->delivery_weight;
            $delivery->payment_term_id = $request->payment_term_id;
            $delivery->total_qty = $request->total_qty;
            $delivery->total_gross = $request->total_gross;
            $delivery->total_discount_amount = $request->total_discount_amount;
            $delivery->total_net = $request->total_net;
            $delivery->total_vat = $request->total_vat;
            $delivery->total_excise = $request->total_excise;
            $delivery->grand_total = $request->grand_total;
            $delivery->current_stage_comment = $request->current_stage_comment;
            $delivery->delivery_due_date = date('Y-m-d', strtotime($request->delivery_due_date));
            $delivery->source = $request->source;
            $delivery->status = $status;
            $delivery->current_stage       = $current_stage;
            $delivery->approval_status       = "Created";
            $delivery->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $delivery->save();

            if (is_array($request->items)) {
                foreach ($request->items as $deliveryItem) {
                    //save DeliveryDetail

                    if (!empty($request->order_id)) {
                        $order_details = OrderDetail::find($deliveryItem['id']);
                    }
                    $deliveryDetail = new DeliveryDetail;
                    $deliveryDetail->delivery_id = $delivery->id;
                    $deliveryDetail->item_id = $deliveryItem['item_id'];
                    $deliveryDetail->item_uom_id = $deliveryItem['item_uom_id'];
                    $deliveryDetail->discount_id = $deliveryItem['discount_id'];
                    $deliveryDetail->is_free = $deliveryItem['is_free'];
                    $deliveryDetail->is_item_poi = $deliveryItem['is_item_poi'];
                    $deliveryDetail->promotion_id = $deliveryItem['promotion_id'];
                    $deliveryDetail->item_qty = $deliveryItem['item_qty'];
                    $deliveryDetail->item_price = $deliveryItem['item_price'];
                    $deliveryDetail->item_gross = $deliveryItem['item_gross'];
                    $deliveryDetail->item_discount_amount = $deliveryItem['item_discount_amount'];
                    $deliveryDetail->item_net = $deliveryItem['item_net'];
                    $deliveryDetail->item_vat = $deliveryItem['item_vat'];
                    $deliveryDetail->item_excise = $deliveryItem['item_excise'];
                    $deliveryDetail->item_grand_total = $deliveryItem['item_grand_total'];
                    $deliveryDetail->batch_number = $deliveryItem['batch_number'];
                    $deliveryDetail->save();

                    if ($request->order_id != null) {
                        if ($order_details->item_qty == $deliveryItem['item_qty']) {
                            $open_qty = 0.00;
                            $item_qty = $deliveryItem['item_qty'];
                            if (
                                $order_details->order_status == "Pending" ||
                                $order_details->order_status == "Approved"
                            ) {
                                $order_details->order_status = "Delivered";
                                $order_details->open_qty = $open_qty;
                                $order_details->delivered_qty = $item_qty;
                                $order_details->item_qty = $item_qty;
                                $order_details->save();
                            }
                        } else {

                            // if ($order_details->open_qty != 0) {
                            //     $order_status = 'Partial-Delivered';
                            //     $open_qty = $order_details->open_qty - $deliveryItem['item_qty'];
                            //     $delivered_qty = $order_details->delivered_qty + $deliveryItem['item_qty'];
                            //     $item_qty = $deliveryItem['item_qty'] + $order_details->item_qty;
                            // } else {
                            //     $open_qty = $order_details->item_qty - $deliveryItem['item_qty'];
                            //     $delivered_qty = $order_details->delivered_qty + $deliveryItem['item_qty'];
                            //     $item_qty = $deliveryItem['item_qty'];
                            //     $order_status = 'Partial-Delivered';
                            // }

                            if ($order_details->open_qty == 0) {
                                $order_details->open_qty = $order_details->item_qty - $deliveryItem['item_qty'];
                            } else {
                                $order_details->open_qty = $order_details->open_qty - $deliveryItem['item_qty'];
                            }

                            if ($order_details->delivered_qty == 0) {
                                $order_details->delivered_qty = $deliveryItem['item_qty'];
                            } else {
                                $order_details->delivered_qty = $order_details->delivered_qty + $deliveryItem['item_qty'];
                            }

                            if ($order_details->delivered_qty == $order_details->item_qty) {
                                $order_details->order_status = 'Delivered';
                            } else {
                                $order_details->order_status = 'Partial-Delivered';
                            }

                            $order_details->save();
                        }
                    }
                }

                if ($request->order_id != null) {
                    $order = Order::find($request->order_id);

                    $orderDetails = OrderDetail::where('order_id', $order->id)
                        ->whereIn('order_status', ['Partial-Delivered', 'Pending'])
                        ->where('open_qty', '!=', 0)
                        ->get();

                    if (!count($orderDetails)) {
                        $order->approval_status = "Delivered";
                    } else {
                        $order->approval_status = "Partial-Delivered";
                    }

                    $order->save();
                }
            }

            if ($isActivate = checkWorkFlowRule('Deliviery', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Deliviery', $request, $delivery);
            }

            //slesman -> route -> depot
            $salesmanLoad = $this->createSalesmanLoad($request, $delivery);

            if (!isset($salesmanLoad->id)) {
                $load_encode = json_decode(json_encode($salesmanLoad, true));
                if (isset($load_encode->original->errors) && $load_encode->original->errors) {
                    return prepareResult(false, [], [], $load_encode->original->errors, $this->internal_server_error);
                } else if (isset($load_encode->original->errors->error) && $load_encode->original->errors->error) {
                    return prepareResult(false, [], [], $load_encode->original->errors->error, $this->internal_server_error);
                }
            }


            \DB::commit();

            updateNextComingNumber('App\Model\Delivery', 'delivery');

            $delivery->getSaveData();

            return prepareResult(true, $delivery, [], "Delivery added successfully.", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function createSalesmanLoad($request, $delivery)
    {
        try {

            $salesmanInfo = SalesmanInfo::where('user_id', $request->salesman_id)->first();

            $routes = Route::find($salesmanInfo->route_id);

            if (!is_object($routes)) {
                return prepareResult(false, [], ["error" => "Route is not atteched with salesman"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            }

            $depot_id = $routes->depot_id;

            $loadheader = new SalesmanLoad;
            // $loadheader->load_number    = nextComingNumber('App\Model\SalesmanLoad', 'load_number', 'load_number', $code['number_is']);
            $loadheader->load_number    = $delivery->delivery_number;
            $loadheader->route_id       = $routes->id;
            $loadheader->depot_id       = $depot_id;
            $loadheader->salesman_id    = $request->salesman_id;
            $loadheader->load_date      = $request->delivery_date;
            $loadheader->load_type      = 1;
            $loadheader->load_confirm   = 0;
            $loadheader->status         = 0;
            $loadheader->trip_id        = null;
            $loadheader->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $loaddetail = new SalesmanLoadDetails;
                    $loaddetail->salesman_load_id = $loadheader->id;
                    $loaddetail->route_id = $loadheader->route_id;
                    $loaddetail->salesman_id = $loadheader->salesman_id;
                    $loaddetail->depot_id = $depot_id;
                    $loaddetail->load_date = $loadheader->load_date;
                    $loaddetail->item_id = $item['item_id'];
                    $loaddetail->item_uom = $item['item_uom_id'];
                    $loaddetail->load_qty = $item['item_qty'];
                    $loaddetail->save();

                    //----------
                    $conversation = getItemDetails2($item['item_id'], $item['item_uom_id'], $item['item_qty']);


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

            return $loadheader;
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

        $delivery = Delivery::with(
            'order',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'invoice',
            'paymentTerm',
            'deliveryDetails.item:id,item_name,item_code,lower_unit_uom_id',
            'deliveryDetails.itemUom:id,name,code',
            'deliveryDetails.item.itemMainPrice',
            'deliveryDetails.item.itemMainPrice.itemUom:id,name',
            'deliveryDetails.item.itemUomLowerUnit:id,name',
            'lob'
        )->where('uuid', $uuid)
            ->first();

        if (!is_object($delivery)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $delivery, [], "Delivery Edit", $this->success);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating depots", $this->unprocessableEntity);
        }

        if (!empty($request->route_id)) {
            $route_id = $request->route_id;
        } else if (!empty($request->salesman_id)) {
            $route_id = getRouteBySalesman($request->salesman_id);
        }

        \DB::beginTransaction();
        try {
            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Deliviery', 'create', $current_organisation_id)) {
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Deliviery);
            }

            $delivery = Delivery::where('uuid', $uuid)->first();

            if (!is_object($delivery)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
            }

            DeliveryDetail::where('delivery_id', $delivery->id)->delete();

            $delivery->customer_id   = $request->customer_id;
            $delivery->order_id = $request->order_id;
            $delivery->salesman_id = $request->salesman_id;
            $delivery->delivery_number = $request->delivery_number;
            $delivery->route_id = (!empty($route_id)) ? $route_id : null;
            $delivery->delivery_date = date('Y-m-d');
            $delivery->delivery_time = (isset($request->delivery_time)) ? $request->delivery_time : date('H:m:s');
            $delivery->delivery_weight = $request->delivery_weight;
            $delivery->payment_term_id = $request->payment_term_id;
            $delivery->total_qty = $request->total_qty;
            $delivery->total_gross = $request->total_gross;
            $delivery->total_discount_amount = $request->total_discount_amount;
            $delivery->total_net = $request->total_net;
            $delivery->total_vat = $request->total_vat;
            $delivery->total_excise = $request->total_excise;
            $delivery->grand_total = $request->grand_total;
            $delivery->current_stage_comment = $request->current_stage_comment;
            $delivery->source = $request->source;
            $delivery->status = $status;
            $delivery->current_stage = $current_stage;
            $delivery->approval_status       = "Updated";
            $delivery->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $delivery->save();

            if ($isActivate = checkWorkFlowRule('Deliviery', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Deliviery', $request, $delivery);
            }

            if (is_array($request->items) && sizeof($request->items) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
            }


            if (is_array($request->items)) {
                $order_detail_ids = array();
                foreach ($request->items as $deliveryItem) {
                    //save DeliveryDetail
                    $deliveryDetail = new DeliveryDetail;

                    $order_details = OrderDetail::find($deliveryItem['id']);
                    if ($order_details->order_status == "Pending") {
                        $order_details->order_status = "Delivered";
                        $order_details->save();
                    }

                    $order_detail_ids[] = $deliveryItem['id'];
                    $deliveryDetail->delivery_id = $delivery->id;
                    $deliveryDetail->item_id = $deliveryItem['item_id'];
                    $deliveryDetail->item_uom_id = $deliveryItem['item_uom_id'];
                    $deliveryDetail->discount_id = $deliveryItem['discount_id'];
                    $deliveryDetail->is_free = $deliveryItem['is_free'];
                    $deliveryDetail->is_item_poi = $deliveryItem['is_item_poi'];
                    $deliveryDetail->promotion_id = $deliveryItem['promotion_id'];
                    $deliveryDetail->item_qty = $deliveryItem['item_qty'];
                    $deliveryDetail->item_price = $deliveryItem['item_price'];
                    $deliveryDetail->item_gross = $deliveryItem['item_gross'];
                    $deliveryDetail->item_discount_amount = $deliveryItem['item_discount_amount'];
                    $deliveryDetail->item_net = $deliveryItem['item_net'];
                    $deliveryDetail->item_vat = $deliveryItem['item_vat'];
                    $deliveryDetail->item_excise = $deliveryItem['item_excise'];
                    $deliveryDetail->item_grand_total = $deliveryItem['item_grand_total'];
                    $deliveryDetail->batch_number = $deliveryItem['batch_number'];
                    $deliveryDetail->save();
                }

                $order = Order::find($request->order_id);
                $orderDetails = OrderDetail::where('order_id', $order->id)
                    ->whereIn('order_status', ['Partial-Delivered', 'Pending'])
                    ->where('open_qty', 0)
                    ->get();

                if (count($orderDetails)) {
                    $order->approval_status = "Delivered";
                } else {
                    $order->approval_status = "Partial-Delivered";
                }
                $order->save();
            }

            \DB::commit();

            $delivery->getSaveData();

            return prepareResult(true, $delivery, [], "Delivery update successfully.", $this->success);
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
            return prepareResult(false, [], [], "Error while validating depots", $this->unauthorized);
        }

        $delivery = Delivery::where('uuid', $uuid)->first();

        if (is_object($delivery)) {
            $deliveryID = $delivery->id;
            $delivery->delete();
            $delivery_detail = DeliveryDetail::where('delivery_id', $deliveryID)->get();
            if (is_object($delivery_detail)) {
                foreach ($delivery_detail as $raw) {
                    //update in warehouse
                    $customerInfo = CustomerInfo::find($delivery->customer_id);
                    if (is_object($customerInfo)) {
                        $route_id = $customerInfo->route_id;
                        $routes = Route::find($route_id);
                        if (is_object($routes)) {
                            $depot_id = $routes->depot_id;
                            $Warehouse = Warehouse::where('depot_id', $depot_id)->where('route_id', $route_id)->first();
                            if (is_object($Warehouse)) {
                                $warehouse_id = $Warehouse->id;
                                $warehouse_detail = WarehouseDetail::where('warehouse_id', $warehouse_id)
                                    ->where('item_id', $raw->item_id)
                                    ->where('item_uom_id', $raw->item_uom_id)->first();
                                if (is_object($warehouse_detail)) {
                                    $warehouse_detail->qty = ($warehouse_detail->qty + $raw->item_qty);
                                    $warehouse_detail->save();
                                }
                            }
                        }
                    }
                    $delivery_detail_delete = DeliveryDetail::find($raw->id);
                    //update in warehouse
                }
            }

            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Validations
     **/
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {

            $validator = \Validator::make($input, [
                'customer_id' => 'required|numeric',
                'salesman_id' => 'required|numeric',
                'delivery_number' => 'required',
                'delivery_weight' => 'required',
                'payment_term_id' => 'required|numeric',
                'total_qty' => 'required|numeric',
                'total_gross' => 'required|numeric',
                'total_discount_amount' => 'required|numeric',
                'total_net' => 'required|numeric',
                'total_vat' => 'required|numeric',
                'total_excise' => 'required|numeric',
                'grand_total' => 'required|numeric',
                'total_qty' => 'required|numeric',
                'current_stage_comment' => 'required',
                'source' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request, $obj)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id   = $work_flow_rule_id;
        $createObj->module_name         = $module_name;
        $createObj->raw_id              = $obj->id;
        $createObj->request_object      = $request->all();
        $createObj->save();

        $wfrau = WorkFlowRuleApprovalUser::where('work_flow_rule_id', $work_flow_rule_id)->first();

        $data = array(
            'uuid' => (is_object($obj)) ? $obj->uuid : 0,
            'user_id' => $wfrau->user_id,
            'type' => $module_name,
            'message' => "Approve the New " . $module_name,
            'status' => 1,
        );
        saveNotificaiton($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getdeliveries($salesman_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$salesman_id) {
            return prepareResult(false, [], [], "Error while validating deliveries", $this->unauthorized);
        }

        $deliveries = Delivery::with(array('salesman' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'deliveryDetails',
                'deliveryDetails.item:id,item_name,item_code',
                'deliveryDetails.itemUom:id,name,code'
            )
            ->where('salesman_id', $salesman_id)
            ->where('current_stage', '!=', 'Completed')
            ->whereDate('delivery_date', date('Y-m-d'))
            ->get();

        $deliveries_array = array();
        if (is_object($deliveries)) {
            foreach ($deliveries as $key => $deliveries1) {
                $deliveries_array[] = $deliveries[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($deliveries_array[$offset])) {
                    $data_array[] = $deliveries_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($deliveries_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($deliveries_array);
        } else {
            $data_array = $deliveries_array;
        }

        return prepareResult(true, $data_array, [], "Delivery plan listing", $this->success, $pagination);

        // return prepareResult(true, $deliveries, [], "Deliveries listing", $this->success);
    }

    public function cancel($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating depots", $this->unauthorized);
        }

        $delivery = Delivery::where('uuid', $uuid)->first();

        if (is_object($delivery)) {
            $delivery->current_stage = "Cancel";
            $delivery->save();
            return prepareResult(true, [], [], "Delivery Canceled", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }


    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'delivery_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate delivery import", $this->unauthorized);
        }

        Excel::import(new DeliveryImport, request()->file('delivery_file'));
        return prepareResult(true, [], [], "delivery successfully imported", $this->success);
    }

    /*
    *   This function used only delivey update
    *   Created By Hardik Solanki
    */

    public function updateImport(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // $validator = \Validator::make($request->all(), [
        //     'delivery_update_file' => 'required|mimes:csv,xlsx,xls'
        // ]);

        // if ($validator->fails()) {
        //     $error = $validator->messages()->first();
        //     return prepareResult(false, [], $error, "Failed to validate delivery import", $this->unauthorized);
        // }

        Excel::import(new DeliveryUpdateImport, request()->file('delivery_update_file'));

        return prepareResult(true, [], [], "delivery successfully imported", $this->success);
    }
}
