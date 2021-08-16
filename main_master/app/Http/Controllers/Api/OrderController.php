<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\ItemMainPrice;
use App\Model\Item;
use App\Model\PriceDiscoPromoPlan;
use App\Model\CustomerInfo;
use App\Model\Delivery;
use App\Model\Route;
use App\Model\PDPDiscountSlab;
use App\Model\PDPItem;
use App\Model\PDPPromotionItem;
use App\Model\SalesmanNumberRange;
use App\Model\WorkFlowObject;
use App\User;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Imports\OrderImport;
use App\Model\CodeSetting;
use App\Model\DeviceDetail;
use App\Model\WorkFlowRuleApprovalUser;

class OrderController extends Controller
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

        $orders_query = Order::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'orderType:id,name,description',
                'paymentTerm:id,name,number_of_days',
                'route:id,route_name,route_code',
                'orderDetails',
                'orderDetails.item:id,item_name,item_code,lower_unit_uom_id',
                'orderDetails.itemUom:id,name,code',
                'orderDetails.itemMainPrice',
                'orderDetails.item.itemUomLowerUnit',
                'depot:id,depot_name',
                'lob'
            );
        // ->where('order_date', date('Y-m-d'));

        if ($request->date) {
            $orders_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->order_number) {
            $orders_query->where('order_number', 'like', '%' . $request->order_number . '%');
        }

        if ($request->due_date) {
            $orders_query->where('due_date', date('Y-m-d', strtotime($request->due_date)));
        }

        if ($request->current_stage) {
            $orders_query->where('current_stage', 'like', '%' . $request->current_stage . '%');
        }

        if ($request->customer_name) {
            $customer_name = $request->customer_name;
            $exploded_name = explode(" ", $customer_name);
            if (count($exploded_name) < 2) {
                $orders_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $orders_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $orders_query->whereHas('customer.customerInfo', function ($q) use ($customer_code) {
                $q->where('customer_code', 'like', '%' . $customer_code . '%');
            });
        }

        if ($request->route_name) {
            $route_name = $request->route_name;
            $orders_query->whereHas('route', function ($q) use ($route_name) {
                $q->where('route_name', 'like',  '%' . $route_name . '%');
            });
        }

        if ($request->route_code) {
            $route_code = $request->route_code;
            $orders_query->whereHas('route', function ($q) use ($route_code) {
                $q->where('route_code', 'like',  '%' . $route_code . '%');
            });
        }

        $orders = $orders_query->orderBy('id', 'desc')
            ->get();

        // approval
        $results = GetWorkFlowRuleObject('Order');
        $approve_need_order = array();
        $approve_need_order_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_order[] = $raw['object']->raw_id;
                $approve_need_order_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $orders_array = array();
        if (is_object($orders)) {
            foreach ($orders as $key => $order1) {
                if (in_array($orders[$key]->id, $approve_need_order)) {
                    $orders[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_order_object_id[$orders[$key]->id])) {
                        $orders[$key]->objectid = $approve_need_order_object_id[$orders[$key]->id];
                    } else {
                        $orders[$key]->objectid = '';
                    }
                } else {
                    $orders[$key]->need_to_approve = 'no';
                    $orders[$key]->objectid = '';
                }

                if ($orders[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($orders[$key]->id, $approve_need_order)) {
                    $orders_array[] = $orders[$key];
                }
            }
        }
        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($orders_array[$offset])) {
                    $data_array[] = $orders_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($orders_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($orders_array);
        } else {
            $data_array = $orders_array;
        }
        return prepareResult(true, $data_array, [], "Todays Orders listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if ($request->source == 1 && !$request->salesman_id) {
            return prepareResult(false, [], "Error Please add Salesman", "Error while validating order", $this->unprocessableEntity);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
        }

        if ($request->source == 1 && $request->payment_term_id != "") {
            $validate = $this->validations($input, "addPayment");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
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

            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Order', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Order);
            }

            $order = new Order;
            $order->customer_id = (!empty($request->customer_id)) ? $request->customer_id : null;
            $order->depot_id = (!empty($request->depot_id)) ? $request->depot_id : null;
            $order->order_type_id = $request->order_type_id;
            if ($request->source == 1) {
                $order->order_number = $request->order_number;
            } else {
                $order->order_number = nextComingNumber('App\Model\Order', 'order', 'order_number', $request->order_number);
            }
            $order->order_date = date('Y-m-d');
            $order->delivery_date = $request->delivery_date;
            $order->salesman_id = $request->salesman_id;
            $order->route_id            = (!empty($route_id)) ? $route_id : null;
            $order->customer_lop        = (!empty($request->customer_lop)) ? $request->customer_lop : null;
            $order->payment_term_id = $request->payment_term_id;
            $order->due_date = $request->due_date;
            $order->total_qty = $request->total_qty;
            $order->total_gross = $request->total_gross;
            $order->total_discount_amount = $request->total_discount_amount;
            $order->total_net = $request->total_net;
            $order->total_vat = $request->total_vat;
            $order->total_excise = $request->total_excise;
            $order->grand_total = $request->grand_total;
            $order->any_comment = $request->any_comment;
            $order->source = $request->source;
            $order->status = $status;
            $order->current_stage = $current_stage;
            $order->current_stage_comment = $request->current_stage_comment;
            $order->approval_status = "Created";
            $order->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $order->save();
            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $orderDetail = new OrderDetail;
                    $orderDetail->order_id = $order->id;
                    $orderDetail->item_id = $item['item_id'];
                    $orderDetail->item_uom_id = $item['item_uom_id'];
                    $orderDetail->discount_id = $item['discount_id'];
                    $orderDetail->is_free = $item['is_free'];
                    $orderDetail->is_item_poi = $item['is_item_poi'];
                    $orderDetail->promotion_id = $item['promotion_id'];
                    $orderDetail->item_qty = $item['item_qty'];
                    $orderDetail->item_price = $item['item_price'];
                    $orderDetail->item_gross = $item['item_gross'];
                    $orderDetail->item_discount_amount = $item['item_discount_amount'];
                    $orderDetail->item_net = $item['item_net'];
                    $orderDetail->item_vat = $item['item_vat'];
                    $orderDetail->item_excise = $item['item_excise'];
                    $orderDetail->item_grand_total = $item['item_grand_total'];
                    $orderDetail->save();
                }
            }

            if ($isActivate = checkWorkFlowRule('Order', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Order', $request, $order);
            }

            // if mobile order
            if (is_object($order) && $order->source == 1) {
                $user = User::find($request->user()->id);
                if (is_object($user)) {
                    $salesmanInfo = $user->salesmanInfo;
                    $smr = SalesmanNumberRange::where('salesman_id', $salesmanInfo->id)->first();
                    $smr->order_from = $request->order_number;
                    $smr->save();
                }
            }

            create_action_history("Order", $order->id, auth()->user()->id, "create", "Customer created by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            // backend
            if ($request->source != 1) {
                updateNextComingNumber('App\Model\Order', 'order');
            }

            \DB::commit();
            $order->getSaveData();
            return prepareResult(true, $order, [], "Order added successfully", $this->success);
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
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating order.", $this->unauthorized);
        }

        $order = Order::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'orderType:id,name,description',
                'paymentTerm:id,name,number_of_days',
                'orderDetails',
                'orderDetails.item:id,item_name,item_code,lower_unit_uom_id',
                'orderDetails.itemUom:id,name,code',
                'orderDetails.item.itemMainPrice',
                'orderDetails.item.itemMainPrice.itemUom:id,name',
                'orderDetails.item.itemUomLowerUnit:id,name',
                'depot:id,depot_name',
                'lob'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($order)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }
        return prepareResult(true, $order, [], "Order Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if ($request->source == 1 && !$request->salesman_id) {
            return prepareResult(false, [], "Error Please add Salesman", "Error while validating salesman", $this->unprocessableEntity);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order.", $this->unprocessableEntity);
        }

        if ($request->source == 1 && $request->payment_term_id != "") {
            $validate = $this->validations($input, "addPayment");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
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
            if ($isActivate = checkWorkFlowRule('Order', 'create', $current_organisation_id)) {
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Order);
            }

            $order = Order::where('uuid', $uuid)->first();

            //Delete old record
            OrderDetail::where('order_id', $order->id)->delete();

            $order->customer_id             = (!empty($request->customer_id)) ? $request->customer_id : null;
            $order->depot_id                = (!empty($request->depot_id)) ? $request->depot_id : null;
            $order->order_type_id           = $request->order_type_id;
            $order->order_number            = $request->order_number;
            $order->order_date              = date('Y-m-d');
            $order->delivery_date           = $request->delivery_date;
            $order->salesman_id             = $request->salesman_id;
            $order->route_id                = (!empty($route_id)) ? $route_id : null;
            $order->customer_lop            = (!empty($request->customer_lop)) ? $request->customer_lop : null;
            $order->payment_term_id         = $request->payment_term_id;
            $order->due_date                = $request->due_date;
            $order->total_qty               = $request->total_qty;
            $order->total_gross             = $request->total_gross;
            $order->total_discount_amount   = $request->total_discount_amount;
            $order->total_net               = $request->total_net;
            $order->total_vat               = $request->total_vat;
            $order->total_excise            = $request->total_excise;
            $order->grand_total             = $request->grand_total;
            $order->any_comment             = $request->any_comment;
            $order->source                  = $request->source;
            $order->status                  = $status;
            $order->current_stage           = $current_stage;
            $order->lob_id                  = (!empty($request->lob_id)) ? $request->lob_id : null;
            $order->approval_status         = "Updated";
            $order->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $orderDetail = new OrderDetail;
                    $orderDetail->order_id = $order->id;
                    $orderDetail->item_id = $item['item_id'];
                    $orderDetail->item_uom_id = $item['item_uom_id'];
                    $orderDetail->discount_id = $item['discount_id'];
                    $orderDetail->is_free = $item['is_free'];
                    $orderDetail->is_item_poi = $item['is_item_poi'];
                    $orderDetail->promotion_id = $item['promotion_id'];
                    $orderDetail->item_qty = $item['item_qty'];
                    $orderDetail->item_price = $item['item_price'];
                    $orderDetail->item_gross = $item['item_gross'];
                    $orderDetail->item_discount_amount = $item['item_discount_amount'];
                    $orderDetail->item_net = $item['item_net'];
                    $orderDetail->item_vat = $item['item_vat'];
                    $orderDetail->item_excise = $item['item_excise'];
                    $orderDetail->item_grand_total = $item['item_grand_total'];
                    $orderDetail->save();
                }
            }

            if ($isActivate = checkWorkFlowRule('Order', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Order', $request, $order);
            }

            create_action_history("Order", $order->id, auth()->user()->id, "update", "Customer update by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            if ($order->salesman_id) {
                if (is_object($order) && $order->source == 1) {
                    $user = User::find($request->user()->id);
                    if (is_object($user)) {

                        $dataNofi = array(
                            'message' => "Your order " . $order->order_number . " is approved by " . $request->user()->firstname,
                            'title' => "Order",
                            'noti_type' => "order",
                            "uuid" => $order->uuid
                        );

                        $device_detail = DeviceDetail::where('user_id', $order->salesman_id)->get();
                        $device_detail->each(function ($token, $key) use ($dataNofi) {
                            $t = $token->device_token;
                            sendNotificationAndroid($dataNofi, $t);
                        });

                        $d = array(
                            $order->salesman_id,
                            null,
                            'Order',
                            "Your order " . $order->order_number . " is approved by " . $request->user()->firstname,
                            1
                        );
                        saveNotificaiton($d);
                    }
                }
            }

            \DB::commit();
            $order->getSaveData();
            return prepareResult(true, $order, [], "Order updated successfully", $this->success);
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
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating order.", $this->unauthorized);
        }

        $order = Order::where('uuid', $uuid)
            ->first();

        if (is_object($order)) {
            $orderId = $order->id;
            $order->delete();
            if ($order) {
                OrderDetail::where('order_id', $orderId)->delete();
                Delivery::where('order_id', $orderId)->delete();
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
     * @param  \Illuminate\Http\Request $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->order_ids;
        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                Order::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $order = $this->index();
            return prepareResult(true, $order, [], "Region status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $order = Order::where('uuid', $uuid)
                    ->first();
                $orderId = $order->id;
                $order->delete();
                if ($order) {
                    OrderDetail::where('order_id', $orderId)->delete();
                    Delivery::where('order_id', $orderId)->delete();
                }
            }
            $order = $this->index();
            return prepareResult(true, $order, [], "Region deleted success", $this->success);
        }
    }

    /**
     * Get price specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $item_id , $item_uom_id, $item_qty
     * @return \Illuminate\Http\Response
     */
    public function itemApplyPrice(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "item-apply-price");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $itemPriceInfo = [];
            $lower_uom = false;

            $itemPrice = ItemMainPrice::where('item_id', $request->item_id)
                ->where('item_uom_id', $request->item_uom_id)
                ->first();

            if (!$itemPrice) {
                $itemPrice = Item::where('id', $request->item_id)
                    ->where('lower_unit_uom_id', $request->item_uom_id)
                    ->first();
                $lower_uom = true;
            }

            if ($itemPrice) {
                $item_vat_percentage = 0;
                $item_excise = 0;
                $getTotal = 0;
                $discount = 0;
                $discount_id = 0;
                $discount_per = 0;

                $getItemInfo = Item::find($request->item_id);

                if ($getItemInfo) {
                    if ($getItemInfo->is_tax_apply == 1) {
                        $item_vat_percentage = $getItemInfo->item_vat_percentage;
                        $item_net = $getItemInfo->item_net;
                        $item_excise = $getItemInfo->item_excise;
                    }
                }

                if ($request->customer_id) {
                    //Get Customer Info
                    $getCustomerInfo = CustomerInfo::find($request->customer_id);
                    //Location
                    $customerCountry = $getCustomerInfo->user->country_id; //1
                    $customerRegion = $getCustomerInfo->region_id; //2
                    $customerRoute = $getCustomerInfo->route_id; //4

                    //Customer
                    $getAreaFromRoute = Route::find($customerRoute);
                    $customerArea = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                    $customerSalesOrganisation = $getCustomerInfo->sales_organisation_id; //5
                    $customerChannel = $getCustomerInfo->channel_id; //6
                    $customerCustomerCategory = $getCustomerInfo->customer_category_id; //7
                    $customerCustomer = $getCustomerInfo->id; //8
                }

                //Item
                $itemMajorCategory = $getItemInfo->item_major_category_id; //9
                $itemItemGroup = $getItemInfo->item_group_id; //10
                $item = $getItemInfo->id; //11

                if ($request->customer_id) {

                    $getPricingList = PDPItem::select('p_d_p_items.id as p_d_p_item_id', 'price', 'combination_plan_key_id', 'price_disco_promo_plan_id', 'combination_key_name', 'combination_key', 'combination_key_code', 'price_disco_promo_plans.priority_sequence', 'price_disco_promo_plans.use_for', 'price_disco_promo_plans.discount_main_type')
                        ->join('price_disco_promo_plans', function ($join) {
                            $join->on('p_d_p_items.price_disco_promo_plan_id', '=', 'price_disco_promo_plans.id');
                        })
                        ->join('combination_plan_keys', function ($join) {
                            $join->on('price_disco_promo_plans.combination_plan_key_id', '=', 'combination_plan_keys.id');
                        })
                        ->where('item_id', $request->item_id)
                        ->where('item_uom_id', $request->item_uom_id)
                        ->where('price_disco_promo_plans.organisation_id', auth()->user()->organisation_id)
                        ->where('start_date', '<=', date('Y-m-d'))
                        ->where('end_date', '>=', date('Y-m-d'))
                        ->where('price_disco_promo_plans.status', 1)
                        ->where('combination_plan_keys.status', 1)
                        ->whereNull('price_disco_promo_plans.deleted_at')
                        ->orderBy('priority_sequence', 'ASC')
                        ->orderBy('combination_key_code', 'DESC')
                        ->get();

                    if ($getPricingList->count() <= 0) {
                        // for Discount Header Level
                        $getPricingList = \DB::table('price_disco_promo_plans')->select('combination_plan_key_id', 'price_disco_promo_plans.id as price_disco_promo_plan_id', 'combination_key_name', 'combination_key', 'combination_key_code', 'price_disco_promo_plans.priority_sequence', 'price_disco_promo_plans.use_for')
                            ->join('combination_plan_keys', function ($join) {
                                $join->on('price_disco_promo_plans.combination_plan_key_id', '=', 'combination_plan_keys.id');
                            })
                            ->where('combination_plan_keys.organisation_id', auth()->user()->organisation_id)
                            ->where('start_date', '<=', date('Y-m-d'))
                            ->where('end_date', '>=', date('Y-m-d'))
                            ->where('use_for', 'Discount')
                            ->where('price_disco_promo_plans.status', 1)
                            ->where('combination_plan_keys.status', 1)
                            ->whereNull('price_disco_promo_plans.deleted_at')
                            ->orderBy('priority_sequence', 'ASC')
                            ->orderBy('combination_key_code', 'DESC')
                            ->get();
                    }

                    if ($getPricingList->count() > 0) {
                        $getKey = [];
                        $getDiscountKey = [];

                        foreach ($getPricingList as $key => $filterPrice) {
                            if ($filterPrice->use_for == 'Pricing') {
                                $getKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, $filterPrice->p_d_p_item_id, $filterPrice->price, $filterPrice->priority_sequence);
                            } else if (
                                isset($filterPrice->p_d_p_item_id) &&
                                isset($filterPrice->price)
                            ) {
                                $getDiscountKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, $filterPrice->p_d_p_item_id, $filterPrice->price, $filterPrice->priority_sequence);
                            } else {
                                $getDiscountKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, null, null, $filterPrice->priority_sequence);
                            }
                        }

                        $useThisPrice = '';
                        foreach ($getKey as $checking) {
                            $usePrice = false;
                            foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                                $combination_actual_id = explode('/', $checking['combination_actual_id']);
                                $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);
                                if ($isFind) {
                                    $usePrice = true;
                                } else {
                                    $usePrice = false;
                                    break;
                                }
                            }

                            if ($usePrice) {
                                $useThisPrice = $checking['price'];
                                break;
                            }
                        }

                        $useThisType = '';
                        $useThisDiscountPercentage = '';
                        $useThisDiscountType = '';
                        $useThisDiscount = '';
                        $useThisDiscountQty = '';
                        $useThisDiscountApply = '';

                        foreach ($getDiscountKey as $checking) {
                            $useDiscount = false;
                            foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                                $combination_actual_id = explode('/', $checking['combination_actual_id']);

                                $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);

                                if ($isFind) {
                                    $useDiscount = true;
                                } else {
                                    $useDiscount = false;
                                    break;
                                }
                            }

                            if ($useDiscount) {
                                $is_discount = false;
                                $useThisType = $checking['type'];
                                $useThisDiscountType = $checking['discount_type'];
                                if ($checking['discount_type'] == 1) {
                                    $useThisDiscount = $checking['discount_value'];
                                }
                                if ($checking['discount_type'] == 2) {
                                    $useThisDiscountPercentage = $checking['discount_percentage'];
                                }
                                $useThisDiscountID = $checking['price_disco_promo_plan_id'];
                                $useThisDiscountQty = $checking['qty_to'];
                                $useThisDiscountApply = $checking['discount_apply_on'];
                                $is_discount = true;
                                break;
                            }
                        }

                        //return prepareResult(true, $checkKeyForPrice, [], "Item price.", $this->created);
                    }

                    $item_qty = $request->item_qty;
                    if ($lower_uom) {
                        $item_price = $itemPrice->lower_unit_item_price;
                    } else {
                        $item_price = $itemPrice->item_price;
                    }

                    if (isset($usePrice) && $usePrice) {
                        $item_price = $useThisPrice;
                    }

                    if (isset($useDiscount) && $useDiscount) {
                        // Slab

                        if ($useThisType == 2) {
                            $discount_slab = PDPDiscountSlab::where('price_disco_promo_plan_id', $useThisDiscountID)->get();
                            $slab_obj = '';
                            foreach ($discount_slab as $slab) {
                                if ($useThisDiscountApply == 1) {
                                    if (!$slab->max_slab) {
                                        if ($item_qty >= $slab->min_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    } else {
                                        if ($item_qty >= $slab->min_slab && $item_qty <= $slab->max_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    }
                                }
                                if ($useThisDiscountApply == 2) {
                                    $item_gross = $item_qty * $item_price;
                                    if (!$slab->max_slab) {
                                        if ($item_gross >= $slab->min_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    } else {
                                        if ($item_gross >= $slab->min_slab && $item_gross <= $slab->max_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    }
                                }
                            }
                            // slab value
                            if ($useThisDiscountType == 1) {
                                $discount = $slab_obj->value;
                                $discount_id = $useThisDiscountID;
                            }
                            // slab percentage
                            if ($useThisDiscountType == 2) {
                                $discount_id = $useThisDiscountID;
                                $item_gross = $item_qty * $item_price;
                                $discount = $item_gross * $slab_obj->percentage / 100;
                                $discount_per = $slab_obj->percentage;
                            }
                        } else {
                            // 1 is qty
                            if ($useThisDiscountApply == 1) {
                                if ($request->item_qty >= $checking['qty_to']) {
                                    // 1: Fixed 2 Percentage
                                    if ($useThisDiscountType == 1) {
                                        $discount = $useThisDiscount;
                                        $discount_id = $useThisDiscountID;
                                    }
                                    if ($useThisDiscountType == 2) {
                                        $discount_id = $useThisDiscountID;
                                        $item_gross = $item_qty * $item_price;
                                        $discount = $item_gross * $useThisDiscountPercentage / 100;
                                        $discount_per = $useThisDiscountPercentage;
                                    }
                                }
                            }

                            // 2 is value
                            if ($useThisDiscountApply == 2) {
                                $item_gross = $item_qty * $item_price;
                                if ($item_gross >= $checking['qty_to']) {
                                    // 1: Fixed 2 Percentage
                                    if ($useThisDiscountType == 1) {
                                        $discount = $useThisDiscount;
                                        $discount_id = $useThisDiscountID;
                                    }
                                    if ($useThisDiscountType == 2) {
                                        $discount_id = $useThisDiscountID;
                                        $item_gross = $item_qty * $item_price;
                                        $discount = $item_gross * $useThisDiscountPercentage / 100;
                                        $discount_per = $useThisDiscountPercentage;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$request->customer_id) {
                    $item_qty = $request->item_qty;
                    $item_price = $itemPrice->item_price;
                }

                $item_gross = $item_qty * $item_price;
                $total_net = $item_gross - $discount;
                $item_excise = ($total_net * $item_excise) / 100;
                $item_vat = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                $total = $total_net + $item_excise + $item_vat;

                $itemPriceInfo = [
                    'item_qty' => $item_qty,
                    'item_price' => $item_price,
                    'item_gross' => $item_gross,
                    'discount' => $discount,
                    'discount_percentage' => $discount_per,
                    'discount_id' => $discount_id,
                    'total_net' => $total_net,
                    'is_free' => false,
                    'is_item_poi' => false,
                    'promotion_id' => null,
                    'total_excise' => $item_excise,
                    'total_vat' => $item_vat,
                    'total' => $total,
                ];
            }

            \DB::commit();
            return prepareResult(true, $itemPriceInfo, [], "Item price.", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function itemApplyPriceMultiple(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        if (empty($input))
            return prepareResult(false, [], [], "Error while validating empty data array", $this->unprocessableEntity);

        $totalItems = count($input);
        $itemPriceInfo = array();
        for ($i = 0; $i < $totalItems; $i++) {
            $validate = $this->validations($input[$i], "item-apply-price");
            if ($validate["error"])
                return prepareResult(false, [], [], "Error while validating data array", $this->unprocessableEntity);

            try {
                $retData = $this->singleItemApplyPrice((object)$input[$i]);
                if ($retData['status']) {
                    if (!empty($retData['itemPriceInfo']))
                        $itemPriceInfo[] = $retData['itemPriceInfo'];
                } else {
                    return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                }
            } catch (Throwable $exception) {
                return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            }
        }

        return prepareResult(true, $itemPriceInfo, [], "Item prices.", $this->created);
    }

    private function singleItemApplyPrice($request)
    {

        \DB::beginTransaction();
        try {
            $itemPriceInfo = [];
            $lower_uom = false;

            $itemPrice = ItemMainPrice::where('item_id', $request->item_id)
                ->where('item_uom_id', $request->item_uom_id)
                ->first();

            if (!$itemPrice) {
                $itemPrice = Item::where('id', $request->item_id)
                    ->where('lower_unit_uom_id', $request->item_uom_id)
                    ->first();
                $lower_uom = true;
            }

            if ($itemPrice) {
                $item_vat_percentage = 0;
                $item_excise = 0;
                $getTotal = 0;
                $discount = 0;
                $discount_id = 0;
                $discount_per = 0;

                //////////Default Price
                $getItemInfo = Item::where('id', $request->item_id)
                    ->first();

                if ($getItemInfo) {
                    if ($getItemInfo->is_tax_apply == 1) {
                        $item_vat_percentage = $getItemInfo->item_vat_percentage;
                        $item_net = $getItemInfo->item_net;
                        $item_excise = $getItemInfo->item_excise;
                    }
                }

                if ($request->customer_id) {
                    //Get Customer Info
                    $getCustomerInfo = CustomerInfo::find($request->customer_id);
                    //Location
                    $customerCountry = $getCustomerInfo->user->country_id; //1
                    $customerRegion = $getCustomerInfo->region_id; //2
                    $customerRoute = $getCustomerInfo->route_id; //4

                    //Customer
                    $getAreaFromRoute = Route::find($customerRoute);
                    $customerArea = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                    $customerSalesOrganisation = $getCustomerInfo->sales_organisation_id; //5
                    $customerChannel = $getCustomerInfo->channel_id; //6
                    $customerCustomerCategory = $getCustomerInfo->customer_category_id; //7
                    $customerCustomer = $getCustomerInfo->id; //8
                }

                ////Item
                $itemMajorCategory = $getItemInfo->item_major_category_id; //9
                $itemItemGroup = $getItemInfo->item_group_id; //10
                $item = $getItemInfo->id; //11

                if ($request->customer_id) {

                    //////////Check Price : different level
                    $getPricingList = PDPItem::select('p_d_p_items.id as p_d_p_item_id', 'price', 'combination_plan_key_id', 'price_disco_promo_plan_id', 'combination_key_name', 'combination_key', 'combination_key_code', 'price_disco_promo_plans.priority_sequence', 'price_disco_promo_plans.use_for')
                        ->join('price_disco_promo_plans', function ($join) {
                            $join->on('p_d_p_items.price_disco_promo_plan_id', '=', 'price_disco_promo_plans.id');
                        })
                        ->join('combination_plan_keys', function ($join) {
                            $join->on('price_disco_promo_plans.combination_plan_key_id', '=', 'combination_plan_keys.id');
                        })
                        ->where('item_id', $request->item_id)
                        ->where('item_uom_id', $request->item_uom_id)
                        ->where('price_disco_promo_plans.organisation_id', auth()->user()->organisation_id)
                        ->where('start_date', '<=', date('Y-m-d'))
                        ->where('end_date', '>=', date('Y-m-d'))
                        ->where('price_disco_promo_plans.status', 1)
                        ->where('combination_plan_keys.status', 1)
                        ->whereNull('price_disco_promo_plans.deleted_at')
                        ->orderBy('priority_sequence', 'ASC')
                        ->orderBy('combination_key_code', 'DESC')
                        ->get();


                    if ($getPricingList->count() > 0) {
                        $getKey = [];
                        $getDiscountKey = [];
                        foreach ($getPricingList as $key => $filterPrice) {
                            if ($filterPrice->use_for == 'Pricing') {
                                $getKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, $filterPrice->p_d_p_item_id, $filterPrice->price, $filterPrice->priority_sequence);
                            } else {
                                $getDiscountKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, $filterPrice->p_d_p_item_id, $filterPrice->price, $filterPrice->priority_sequence);
                            }
                        }

                        // $checkKeyForPrice = $this->arrayOrderDesc($getKey, 'hierarchyNumber');

                        $useThisPrice = '';
                        foreach ($getKey as $checking) {
                            $usePrice = false;
                            foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                                $combination_actual_id = explode('/', $checking['combination_actual_id']);
                                $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);
                                if ($isFind) {
                                    $usePrice = true;
                                } else {
                                    $usePrice = false;
                                    break;
                                }
                            }

                            if ($usePrice) {
                                $useThisPrice = $checking['price'];
                                break;
                            }
                        }

                        $useThisType = '';
                        $useThisDiscountPercentage = '';
                        $useThisDiscountType = '';
                        $useThisDiscount = '';
                        $useThisDiscountQty = '';
                        $useThisDiscountApply = '';

                        foreach ($getDiscountKey as $checking) {
                            $useDiscount = false;
                            foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                                $combination_actual_id = explode('/', $checking['combination_actual_id']);
                                $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);
                                if ($isFind) {
                                    $useDiscount = true;
                                } else {
                                    $useDiscount = false;
                                    break;
                                }
                            }

                            if ($useDiscount) {
                                $is_discount = false;
                                $useThisType = $checking['type'];
                                $useThisDiscountType = $checking['discount_type'];
                                if ($checking['discount_type'] == 1) {
                                    $useThisDiscount = $checking['discount_value'];
                                }
                                if ($checking['discount_type'] == 2) {
                                    $useThisDiscountPercentage = $checking['discount_percentage'];
                                }
                                $useThisDiscountID = $checking['price_disco_promo_plan_id'];
                                $useThisDiscountQty = $checking['qty_to'];
                                $useThisDiscountApply = $checking['discount_apply_on'];
                                $is_discount = true;
                                break;
                            }
                        }

                        //return prepareResult(true, $checkKeyForPrice, [], "Item price.", $this->created);
                    }

                    $item_qty = $request->item_qty;
                    if ($lower_uom) {
                        $item_price = $itemPrice->lower_unit_item_price;
                    } else {
                        $item_price = $itemPrice->item_price;
                    }

                    if (isset($usePrice) && $usePrice) {
                        $item_price = $useThisPrice;
                    }

                    if (isset($useDiscount) && $useDiscount) {
                        // Slab

                        if ($useThisType == 2) {
                            $discount_slab = PDPDiscountSlab::where('price_disco_promo_plan_id', $useThisDiscountID)->get();
                            $slab_obj = '';
                            foreach ($discount_slab as $slab) {
                                if ($useThisDiscountApply == 1) {
                                    if (!$slab->max_slab) {
                                        if ($item_qty >= $slab->min_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    } else {
                                        if ($item_qty >= $slab->min_slab && $item_qty <= $slab->max_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    }
                                }
                                if ($useThisDiscountApply == 2) {
                                    $item_gross = $item_qty * $item_price;
                                    if (!$slab->max_slab) {
                                        if ($item_gross >= $slab->min_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    } else {
                                        if ($item_gross >= $slab->min_slab && $item_gross <= $slab->max_slab) {
                                            $slab_obj = $slab;
                                            break;
                                        }
                                    }
                                }
                            }
                            // slab value
                            if ($useThisDiscountType == 1) {
                                $discount = $slab_obj->value;
                                $discount_id = $useThisDiscountID;
                            }
                            // slab percentage
                            if ($useThisDiscountType == 2) {
                                $discount_id = $useThisDiscountID;
                                $item_gross = $item_qty * $item_price;
                                $discount = $item_gross * $slab_obj->percentage / 100;
                                $discount_per = $slab_obj->percentage;
                            }
                        } else {
                            // 1 is qty
                            if ($useThisDiscountApply == 1) {
                                if ($request->item_qty >= $checking['qty_to']) {
                                    // 1: Fixed 2 Percentage
                                    if ($useThisDiscountType == 1) {
                                        $discount = $useThisDiscount;
                                        $discount_id = $useThisDiscountID;
                                    }
                                    if ($useThisDiscountType == 2) {
                                        $discount_id = $useThisDiscountID;
                                        $item_gross = $item_qty * $item_price;
                                        $discount = $item_gross * $useThisDiscountPercentage / 100;
                                        $discount_per = $useThisDiscountPercentage;
                                    }
                                }
                            }

                            // 2 is value
                            if ($useThisDiscountApply == 2) {
                                $item_gross = $item_qty * $item_price;
                                if ($item_gross >= $checking['qty_to']) {
                                    // 1: Fixed 2 Percentage
                                    if ($useThisDiscountType == 1) {
                                        $discount = $useThisDiscount;
                                        $discount_id = $useThisDiscountID;
                                    }
                                    if ($useThisDiscountType == 2) {
                                        $discount_id = $useThisDiscountID;
                                        $item_gross = $item_qty * $item_price;
                                        $discount = $item_gross * $useThisDiscountPercentage / 100;
                                        $discount_per = $useThisDiscountPercentage;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$request->customer_id) {
                    $item_qty = $request->item_qty;
                    $item_price = $itemPrice->item_price;
                }

                $item_gross = $item_qty * $item_price;
                $total_net = $item_gross - $discount;
                $item_excise = ($total_net * $item_excise) / 100;
                $item_vat = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                $total = $total_net + $item_excise + $item_vat;

                $itemPriceInfo = [
                    'item_qty' => $item_qty,
                    'item_price' => $item_price,
                    'item_gross' => $item_gross,
                    'discount' => $discount,
                    'discount_percentage' => $discount_per,
                    'discount_id' => $discount_id,
                    'total_net' => $total_net,
                    'is_free' => false,
                    'is_item_poi' => false,
                    'promotion_id' => null,
                    'total_excise' => $item_excise,
                    'total_vat' => $item_vat,
                    'total' => $total,
                ];
            }

            \DB::commit();
            $retArray['status'] = true;
            $retArray['itemPriceInfo'] = $itemPriceInfo;
        } catch (\Exception $exception) {
            $retArray['status'] = false;
        } catch (\Throwable $exception) {
            $retArray['status'] = false;
        }

        return $retArray;
    }

    private function makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $combination_key_code, $combination_key, $price_disco_promo_plan_id, $p_d_p_item_id, $price, $priority_sequence)
    {
        $keyCodes = '';
        $combination_actual_id = '';
        foreach (explode('/', $combination_key_code) as $hierarchyNumber) {
            switch ($hierarchyNumber) {
                case '1':
                    if (empty($add)) {
                        $add = $customerCountry;
                    } else {
                        $add = '/' . $customerCountry;
                    }
                    // $add  = $customerCountry;
                    break;
                case '2':
                    if (empty($add)) {
                        $add = $customerRegion;
                    } else {
                        $add = '/' . $customerRegion;
                    }
                    // $add  = '/' . $customerRegion;
                    break;
                case '3':
                    if (empty($add)) {
                        $add = $customerArea;
                    } else {
                        $add = '/' . $customerArea;
                    }
                    // $add  = '/' . $customerArea;
                    break;
                case '4':
                    if (empty($add)) {
                        $add = $customerRoute;
                    } else {
                        $add = '/' . $customerRoute;
                    }
                    // $add  = '/' . $customerRoute;
                    break;
                case '5':
                    if (empty($add)) {
                        $add = $customerSalesOrganisation;
                    } else {
                        $add = '/' . $customerSalesOrganisation;
                    }
                    break;
                case '6':
                    if (empty($add)) {
                        $add = $customerChannel;
                    } else {
                        $add = '/' . $customerChannel;
                    }
                    // $add  = '/' . $customerChannel;
                    break;
                case '7':
                    if (empty($add)) {
                        $add = $customerCustomerCategory;
                    } else {
                        $add = '/' . $customerCustomerCategory;
                    }
                    // $add  = '/' . $customerCustomerCategory;
                    break;
                case '8':
                    if (empty($add)) {
                        $add = $customerCustomer;
                    } else {
                        $add = '/' . $customerCustomer;
                    }
                    // $add  = '/' . $customerCustomer;
                    break;
                case '9':
                    if (empty($add)) {
                        $add = $itemMajorCategory;
                    } else {
                        $add = '/' . $itemMajorCategory;
                    }
                    // $add  = '/' . $itemMajorCategory;
                    break;
                case '10':
                    if (empty($add)) {
                        $add = $itemItemGroup;
                    } else {
                        $add = '/' . $itemItemGroup;
                    }
                    // $add  = '/' . $itemItemGroup;
                    break;
                case '11':
                    if (empty($add)) {
                        $add = $item;
                    } else {
                        $add = '/' . $item;
                    }
                    // $add  = '/' . $item;
                    break;
                default:
                    # code...
                    break;
            }
            $keyCodes .= $hierarchyNumber;
            $combination_actual_id .= $add;
        }

        $getIdentify = PriceDiscoPromoPlan::find($price_disco_promo_plan_id);
        $discount = array();
        // $returnData = array();

        if ($getIdentify->use_for == 'Promotion') {
            return array(
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key' => $combination_key,
                'combination_key_code' => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                'p_d_p_promotion_items' => $p_d_p_item_id,
                'priority_sequence' => $priority_sequence,
                'price' => $price,
                'use_for' => $getIdentify->use_for
            );
        }

        if ($getIdentify->use_for == 'Discount') {
            return array(
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key' => $combination_key,
                'combination_key_code' => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                'p_d_p_item_id' => $p_d_p_item_id,
                'priority_sequence' => $priority_sequence,
                'price' => $price,
                'use_for' => $getIdentify->use_for,
                'type' => $getIdentify->type,
                'qty_from' => $getIdentify->qty_from,
                'qty_to' => $getIdentify->qty_to,
                'discount_type' => $getIdentify->discount_type,
                'discount_value' => $getIdentify->discount_value,
                'discount_percentage' => $getIdentify->discount_percentage,
                'discount_apply_on' => $getIdentify->discount_apply_on
            );
        }

        $returnData = [
            'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
            'combination_key' => $combination_key,
            'combination_key_code' => $combination_key_code,
            'combination_actual_id' => $combination_actual_id,
            // 'auto_sequence_by_depth' => explode('/', $combination_key_code),
            // 'auto_sequence_by_depth_count' => count(explode('/', $combination_key_code)),
            'auto_sequence_by_code' => $hierarchyNumber,
            'hierarchyNumber' => $keyCodes,
            'p_d_p_item_id' => $p_d_p_item_id,
            'priority_sequence' => $priority_sequence,
            'price' => $price,
            'use_for' => $getIdentify->use_for
        ];

        return $returnData;
    }

    private function arrayOrderDesc()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            // if (is_string($field)) {
            //     $tmp = array();
            //     foreach ($data as $key => $row)
            //         $tmp[$key] = $row[$field];
            //     $args[$n] = $tmp;
            // }
            foreach ($data as $key => $row) {
                $return_fare[$n] = $row[$field];
                $one_way_fare[$n] = $row['priority_sequence'];
            }
        }
        $sorted = array_multisort(
            array_column($data, 'hierarchyNumber'),
            SORT_ASC,
            array_column($data, 'priority_sequence'),
            SORT_DESC,
            $data
        );

        return $data;
        // $sorted = array_multisort($data, 'one_way_fare', SORT_ASC, 'return_fare', SORT_DESC);
        // $args[] = &$data;
        // call_user_func_array('array_multisort', $args);
        // return array_pop($args);
    }

    private function checkDataExistOrNot(
        $combination_key_number,
        $combination_actual_id,
        $price_disco_promo_plan_id
    ) {
        switch ($combination_key_number) {
            case '1':
                $model = 'App\Model\PDPCountry';
                $field = 'country_id';
                break;
            case '2':
                $model = 'App\Model\PDPRegion';
                $field = 'region_id';
                break;
            case '3':
                $model = 'App\Model\PDPArea';
                $field = 'area_id';
                break;
            case '4':
                $model = 'App\Model\PDPRoute';
                $field = 'route_id';
                break;
            case '5':
                $model = 'App\Model\PDPSalesOrganisation';
                $field = 'sales_organisation_id';
                break;
            case '6':
                $model = 'App\Model\PDPChannel';
                $field = 'channel_id';
                break;
            case '7':
                $model = 'App\Model\PDPCustomerCategory';
                $field = 'customer_category_id';
                break;
            case '8':
                $model = 'App\Model\PDPCustomer';
                $field = 'customer_id';
                break;
            case '9':
                $model = 'App\Model\PDPItemMajorCategory';
                $field = 'item_major_category_id';
                break;
            case '10':
                $model = 'App\Model\PDPItemGroup';
                $field = 'item_group_id';
                break;
            case '11':
                $model = 'App\Model\PDPItem';
                $field = 'item_id';
                break;
            default:
                $model = '';
                $field = '';
                break;
        }

        $checkExistOrNot = $model::where('price_disco_promo_plan_id', $price_disco_promo_plan_id)->where($field, $combination_actual_id)->first();

        if ($checkExistOrNot) {
            return true;
        }

        return false;
    }

    private function getListByParam($obj, $param)
    {
        $object = $obj;
        $array = [];
        $get = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($object), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($get as $key => $value) {
            if ($key === $param) {
                $array = array_merge($array, $value);
            }
        }
        return $array;
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'order_type_id' => 'required|integer|exists:order_types,id',
                'order_number' => 'required',
                'due_date' => 'required|date',
                'delivery_date' => 'required|date',
                'total_qty' => 'required',
                'total_discount_amount' => 'required',
                'total_vat' => 'required',
                'total_net' => 'required',
                'total_excise' => 'required',
                'grand_total' => 'required',
                'source' => 'required|integer',
            ]);
        }

        if ($type == "addPayment") {
            $validator = \Validator::make($input, [
                // 'payment_term_id' => 'required|integer|exists:payment_terms,id'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'order_ids' => 'required'
            ]);
        }

        if ($type == 'item-apply-price') {
            $validator = \Validator::make($input, [
                'item_id' => 'required|integer|exists:items,id',
                'item_uom_id' => 'required|integer|exists:item_uoms,id',
                'item_qty' => 'required|numeric',
            ]);
        }

        if ($type == 'normal-item-apply-price') {
            $validator = \Validator::make($input, [
                'item_id' => 'required|integer|exists:items,id',
                'item_uom_id' => 'required|integer|exists:item_uoms,id',
                'item_qty' => 'required|numeric',
            ]);
        }

        if ($type == 'applyPDP') {
            $validator = \Validator::make($input, [
                'item_id' => 'required|integer|exists:items,id'
                // 'item_uom_id'   => 'required|integer|exists:item_uoms,id',
                // 'item_qty'      => 'required|numeric',
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Get price specified item and item UOM.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $item_id , $item_uom_id, $item_qty
     * @return \Illuminate\Http\Response
     */
    public function normalItemApplyPrice(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "normal-item-apply-price");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $itemPriceInfo = [];
            $lower_uom = false;

            $itemPrice = ItemMainPrice::where('item_id', $request->item_id)
                ->where('item_uom_id', $request->item_uom_id)
                ->first();

            if (!$itemPrice) {
                $itemPrice = Item::where('id', $request->item_id)
                    ->where('lower_unit_uom_id', $request->item_uom_id)
                    ->first();
                $lower_uom = true;
            }

            if ($itemPrice) {
                $item_vat_percentage = 0;
                $item_excise = 0;
                $getTotal = 0;
                $discount = 0;
                $discount_id = 0;
                $discount_per = 0;

                //////////Default Price
                $getItemInfo = Item::where('id', $request->item_id)
                    ->first();

                if ($getItemInfo) {
                    if ($getItemInfo->is_tax_apply == 1) {
                        $item_vat_percentage = $getItemInfo->item_vat_percentage;
                        $item_net = $getItemInfo->item_net;
                        $item_excise = $getItemInfo->item_excise;
                    }
                }

                if ($request->customer_id) {
                    //Get Customer Info
                    $getCustomerInfo = CustomerInfo::find($request->customer_id);
                    //Location
                    $customerCountry = $getCustomerInfo->user->country_id; //1
                    $customerRegion = $getCustomerInfo->region_id; //2
                    $customerRoute = $getCustomerInfo->route_id; //4

                    //Customer
                    $getAreaFromRoute = Route::find($customerRoute);
                    $customerArea = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                    $customerSalesOrganisation = $getCustomerInfo->sales_organisation_id; //5
                    $customerChannel = $getCustomerInfo->channel_id; //6
                    $customerCustomerCategory = $getCustomerInfo->customer_category_id; //7
                    $customerCustomer = $getCustomerInfo->id; //8
                }

                ////Item
                $itemMajorCategory = $getItemInfo->item_major_category_id; //9
                $itemItemGroup = $getItemInfo->item_group_id; //10
                $item = $getItemInfo->id; //11

                // if (!$request->customer_id) {
                if ($lower_uom) {
                    $item_price = $itemPrice->lower_unit_item_price;
                } else {
                    $item_price = $itemPrice->item_price;
                }
                $item_qty = $request->item_qty;
                // $item_price     = $itemPrice->item_price;
                // }

                $item_gross = $item_qty * $item_price;
                $total_net = $item_gross - $discount;
                $item_excise = ($total_net * $item_excise) / 100;
                $item_vat = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                $total = $total_net + $item_excise + $item_vat;

                $itemPriceInfo = [
                    'item_qty' => $item_qty,
                    'item_price' => $item_price,
                    'item_gross' => $item_gross,
                    'discount' => $discount,
                    'discount_percentage' => $discount_per,
                    'discount_id' => $discount_id,
                    'total_net' => $total_net,
                    'is_free' => false,
                    'is_item_poi' => false,
                    'promotion_id' => null,
                    'total_excise' => $item_excise,
                    'total_vat' => $item_vat,
                    'total' => $total,
                ];
            }

            \DB::commit();
            return prepareResult(true, $itemPriceInfo, [], "Item price.", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Get price specified item and item UOM.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $item_id , $item_uom_id, $item_qty
     * @return \Illuminate\Http\Response
     */
    public function itemApplyPromotion(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->item_id) && sizeof($request->item_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        if (is_array($request->item_uom_id) && sizeof($request->item_uom_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items UOM.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $itemPromotionInfo = [];
            $offerItems = [];
            $item_vat_percentage = 0;
            $item_excise = 0;
            $getTotal = 0;
            $discount = 0;
            $discount_id = 0;
            $discount_per = 0;

            $itemPrice = ItemMainPrice::whereIn('item_id', $request->item_id)
                ->whereIn('item_uom_id', $request->item_uom_id)
                ->get();

            if (count($itemPrice)) {
                $getItemInfo = Item::whereIn('id', $request->item_id)
                    ->get();
            }

            if ($request->customer_id) {
                //Get Customer Info
                $getCustomerInfo = CustomerInfo::find($request->customer_id);
                //Location
                $customerCountry = $getCustomerInfo->user->country_id; //1
                $customerRegion = $getCustomerInfo->region_id; //2
                $customerRoute = $getCustomerInfo->route_id; //4

                //Customer
                $getAreaFromRoute = Route::find($customerRoute);
                $customerArea = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                $customerSalesOrganisation = $getCustomerInfo->sales_organisation_id; //5
                $customerChannel = $getCustomerInfo->channel_id; //6
                $customerCustomerCategory = $getCustomerInfo->customer_category_id; //7
                $customerCustomer = $getCustomerInfo->id; //8
            }

            if ($request->customer_id) {

                $getPricingList = PDPPromotionItem::select('p_d_p_promotion_items.id as p_d_p_promotion_items_id', 'p_d_p_promotion_items.price_disco_promo_plan_id', 'p_d_p_promotion_items.item_id', 'p_d_p_promotion_items.item_uom_id', 'p_d_p_promotion_items.item_qty', 'p_d_p_promotion_items.price', 'combination_plan_key_id', 'combination_key_name', 'combination_key', 'combination_key_code', 'priority_sequence', 'use_for')
                    ->join('price_disco_promo_plans', function ($join) {
                        $join->on('p_d_p_promotion_items.price_disco_promo_plan_id', '=', 'price_disco_promo_plans.id');
                    })
                    ->join('combination_plan_keys', function ($join) {
                        $join->on('price_disco_promo_plans.combination_plan_key_id', '=', 'combination_plan_keys.id');
                    })
                    ->whereIn('item_id', $request->item_id)
                    ->whereIn('item_uom_id', $request->item_uom_id)
                    // ->whereIn('item_qty', $request->item_qty)
                    ->where('price_disco_promo_plans.organisation_id', auth()->user()->organisation_id)
                    ->where('price_disco_promo_plans.start_date', '<=', date('Y-m-d'))
                    ->where('price_disco_promo_plans.end_date', '>=', date('Y-m-d'))
                    ->where('price_disco_promo_plans.status', 1)
                    ->where('combination_plan_keys.status', 1)
                    ->orderBy('price_disco_promo_plans.priority_sequence', 'ASC')
                    ->orderBy('combination_plan_keys.combination_key_code', 'DESC')
                    ->get();

                if ($getPricingList->count() > 0) {
                    $getKey = [];
                    $getDiscountKey = [];
                    foreach ($getPricingList as $key => $filterPrice) {
                        $items = Item::where('id', $filterPrice->item_id)->first();
                        $itemMajorCategory = $items->item_major_category_id; //9
                        $itemItemGroup = $items->item_group_id; //10
                        $item = $items->id; //11
                        if (empty($request->item_qty[$key])) {
                            continue;
                        }

                        if ($filterPrice->item_qty > $request->item_qty[$key]) {
                            continue;
                        }

                        $getKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, $filterPrice->p_d_p_promotion_items_id, $filterPrice->price, $filterPrice->priority_sequence);
                    }

                    $result = array();
                    $price_disco_promo_plan_id = '';
                    foreach ($getKey as $element) {
                        if ($price_disco_promo_plan_id != $element['price_disco_promo_plan_id']) {
                            $price_disco_promo_plan_id = $element['price_disco_promo_plan_id'];
                            $result[] = $element;
                        }
                    }

                    // Check order item and offer item
                    foreach ($result as $checking) {
                        $usePromotion = false;
                        foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                            $combination_actual_id = explode('/', $checking['combination_actual_id']);
                            $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);
                            if ($isFind) {
                                $usePromotion = true;
                            } else {
                                $usePromotion = false;
                                break;
                            }
                        }
                        if ($checking['price_disco_promo_plan_id']) {

                            $price_disco_promo_plan = PriceDiscoPromoPlan::where('id', $checking['price_disco_promo_plan_id'])
                                ->with('PDPPromotionItems', 'PDPPromotionItems.item', 'PDPPromotionItems.itemUom')
                                ->first();

                            $is_promotion = false;
                            $promotion_item = array();
                            $PDPPromotionItems = $price_disco_promo_plan->PDPPromotionItems;

                            $price_disco_promo_plan_offer = PriceDiscoPromoPlan::where('id', $checking['price_disco_promo_plan_id'])
                                ->with('PDPPromotionOfferItems', 'PDPPromotionOfferItems.item', 'PDPPromotionOfferItems.itemUom:id,name')
                                ->first();

                            foreach ($PDPPromotionItems as $key => $item) {
                                if (!empty($request->item_qty[$key])) {
                                    $qty = $request->item_qty[$key];

                                    if ($item->item_qty <= $qty) {
                                        $is_promotion = true;
                                        $offerItems = $price_disco_promo_plan_offer->PDPPromotionOfferItems;
                                        $item_price = $item->price;
                                        $item_qty = $qty;
                                        $item_gross = $item_qty * $item_price;
                                        $total_net = $item_gross - $discount;
                                        $item_excise = ($total_net * $item_excise) / 100;
                                        $item_vat = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                                        $total = $total_net + $item_excise + $item_vat;

                                        $itemPromotionInfo[] = [
                                            'item_price' => $item_price,
                                            'item_gross' => $item_gross,
                                            'discount' => $discount,
                                            'total_net' => $total_net,
                                            'is_free' => true,
                                            'is_item_poi' => false,
                                            'order_item_type' => $price_disco_promo_plan->order_item_type,
                                            'offer_item_type' => $price_disco_promo_plan->offer_item_type,
                                            'promotion_id' => $item->id,
                                            'total_excise' => $item_excise,
                                            'total_vat' => $item_vat,
                                            'total' => $total,
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    if (is_array($offerItems) && sizeof($offerItems) > 1) {
                        $offerItems = $offerItems->pluck('item')->toArray();
                    }
                }
            }

            $offerData = array('offer_items' => $offerItems, 'itemPromotionInfo' => $itemPromotionInfo);

            \DB::commit();
            return prepareResult(true, $offerData, [], "Item price.", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request, $obj)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id = $work_flow_rule_id;
        $createObj->module_name = $module_name;
        $createObj->raw_id = $obj->id;
        $createObj->request_object = $request->all();
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

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'order_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate order import", $this->unauthorized);
        }

        Excel::import(new OrderImport, request()->file('order_file'));
        return prepareResult(true, [], [], "Order successfully imported", $this->success);
    }
}
