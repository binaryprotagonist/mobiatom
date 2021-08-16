<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\ItemMainPrice;
use App\Model\Item;
use App\Model\CombinationPlanKey;
use App\Model\PriceDiscoPromoPlan;
use App\Model\CustomerInfo;
use App\Model\Delivery;
use App\Model\Route;
use App\Model\OrderType;

use App\Model\PDPCountry;
use App\Model\PDPRegion;
use App\Model\PDPArea;
use App\Model\PDPSubArea;
use App\Model\PDPRoute;
use App\Model\PDPSalesOrganisation;
use App\Model\PDPChannel;
use App\Model\PDPSubChannel;
use App\Model\PDPCustomerCategory;
use App\Model\PDPCustomer;
use App\Model\PDPDiscountSlab;
use App\Model\PDPItemMajorCategory;
use App\Model\PDPItemSubCategory;
use App\Model\PDPItemGroup;
use App\Model\PDPItem;
use App\Model\PDPPromotionItem;
use App\Model\PDPPromotionOfferItem;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleApprovalRole;

class OrderController2 extends Controller
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

        $orders = Order::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'orderType:id,name,description',
                'paymentTerm:id,name,number_of_days',
                'orderDetails',
                'orderDetails.item:id,item_name',
                'orderDetails.itemUom:id,name,code',
                'customer:id,firstname,lastname',
                'depot:id,depot_name'
            )
            ->where('order_date', date('Y-m-d'))
            ->get();

        // approval
        $workFlowRules = WorkFlowObject::select(
            'work_flow_objects.id as id',
            'work_flow_objects.uuid as uuid',
            'work_flow_objects.work_flow_rule_id',
            'work_flow_objects.module_name',
            'work_flow_objects.request_object',
            'work_flow_objects.currently_approved_stage',
            'work_flow_objects.raw_id',
            'work_flow_rules.work_flow_rule_name',
            'work_flow_rules.description',
            'work_flow_rules.event_trigger'
        )
            ->withoutGlobalScope('organisation_id')
            ->join('work_flow_rules', function ($join) {
                $join->on('work_flow_objects.work_flow_rule_id', '=', 'work_flow_rules.id');
            })
            ->where('work_flow_objects.organisation_id', auth()->user()->organisation_id)
            ->where('status', '1')
            ->where('is_approved_all', '0')
            ->where('is_anyone_reject', '0')
            ->where('work_flow_objects.module_name', 'Order')
            //->where('work_flow_objects.raw_id',$users[$key]->id)
            ->get();

        $results = [];
        foreach ($workFlowRules as $key => $obj) {
            $checkCondition = WorkFlowRuleApprovalRole::query();
            if ($obj->currently_approved_stage > 0) {
                $checkCondition->skip($obj->currently_approved_stage);
            }
            $getResult = $checkCondition->where('work_flow_rule_id', $obj->work_flow_rule_id)
                ->orderBy('id', 'ASC')
                ->first();
            $userIds = [];
            if (is_object($getResult) && $getResult->workFlowRuleApprovalUsers->count() > 0) {
                //User based approval
                foreach ($getResult->workFlowRuleApprovalUsers as $prepareUserId) {
                    $WorkFlowObjectAction = WorkFlowObjectAction::where('work_flow_object_id', $obj->id)->get();
                    if (is_object($WorkFlowObjectAction)) {
                        $id_arr = [];
                        foreach ($WorkFlowObjectAction as $action) {
                            $id_arr[] = $action->user_id;
                        }
                        if (!in_array($prepareUserId->user_id, $id_arr)) {
                            $userIds[] = $prepareUserId->user_id;
                        }
                    } else {
                        $userIds[] = $prepareUserId->user_id;
                    }
                }

                if (in_array(auth()->id(), $userIds)) {
                    $results[] = [
                        'object'    => $obj,
                        'Action'    => 'User'
                    ];
                }
            } else {
                //Roles based approval
                if (is_object($getResult) && $getResult->organisation_role_id == auth()->user()->role_id)
                    $results[] = [
                        'object'    => $obj,
                        'Action'    => 'Role'
                    ];
            }
        }

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
            foreach ($orders as $key => $user1) {
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

        return prepareResult(true, $orders, [], "Todays Orders listing", $this->success);
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

        $exist = Order::where('order_number', $request->order_number)->first();
        if (is_object($exist)) {
            return prepareResult(false, [], 'Order Code is already added.', "Error while validating salesman", $this->unprocessableEntity);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors'], "Error while validating order", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $status = 1;
            if ($isActivate = checkWorkFlowRule('Order', 'create')) {
                $status = 0;
                //$this->createWorkFlowObject($isActivate, 'Customer',$request);
            }

            $order = new Order;
            $order->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $order->depot_id            = (!empty($request->depot_id)) ? $request->depot_id : null;
            $order->order_type_id       = $request->order_type_id;
            $order->order_number        = $request->order_number;
            $order->order_date          = date('Y-m-d');
            $order->delivery_date       = $request->delivery_date;
            $order->payment_term_id     = $request->payment_term_id;
            $order->due_date            = $request->due_date;
            $order->total_qty           = $request->total_qty;
            $order->total_gross         = $request->total_gross;
            $order->total_discount_amount   = $request->total_discount_amount;
            $order->total_net           = $request->total_net;
            $order->total_vat           = $request->total_vat;
            $order->total_excise        = $request->total_excise;
            $order->grand_total         = $request->grand_total;
            $order->any_comment         = $request->any_comment;
            $order->source              = $request->source;
            $order->status = $status;
            $order->save();

            if ($isActivate = checkWorkFlowRule('Order', 'create')) {
                $this->createWorkFlowObject($isActivate, 'Order', $request, $order->id);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $orderDetail = new OrderDetail;
                    $orderDetail->order_id      = $order->id;
                    $orderDetail->item_id       = $item['item_id'];
                    $orderDetail->item_uom_id   = $item['item_uom_id'];
                    $orderDetail->discount_id   = $item['discount_id'];
                    $orderDetail->is_free       = $item['is_free'];
                    $orderDetail->is_item_poi   = $item['is_item_poi'];
                    $orderDetail->promotion_id  = $item['promotion_id'];
                    $orderDetail->item_qty      = $item['item_qty'];
                    $orderDetail->item_price    = $item['item_price'];
                    $orderDetail->item_gross    = $item['item_gross'];
                    $orderDetail->item_discount_amount = $item['item_discount_amount'];
                    $orderDetail->item_net      = $item['item_net'];
                    $orderDetail->item_vat      = $item['item_vat'];
                    $orderDetail->item_excise   = $item['item_excise'];
                    $orderDetail->item_grand_total = $item['item_grand_total'];
                    $orderDetail->order_status = "Pending";
                    $orderDetail->save();
                }
            }

            if ($order) {
                // We have to add code here for salesman next order number increse
                // $getOrderType = OrderType::find($request->order_type_id);
                // preg_match_all('!\d+!', $getOrderType->next_available_code, $newNumber);
                // $formattedNumber = sprintf("%0" . strlen($getOrderType->end_range) . "d", ($newNumber[0][0] + 1));
                // $actualNumber =  $getOrderType->prefix_code . $formattedNumber;
                // $getOrderType->next_available_code = $actualNumber;
                // $getOrderType->save();
            }

            // if mobile order

            // if ($order->source == 1) {

            // } else if () {

            // }

            // backend
            updateNextComingNumber('App\Model\Order', 'order');

            \DB::commit();
            $order->orderDetails;
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
     * @param  int  $uuid
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
                'orderType:id,name,description',
                'paymentTerm:id,name,number_of_days',
                'orderDetails',
                'orderDetails.item:id,item_name',
                'orderDetails.itemUom:id,name,code',
                'customer:id,firstname,lastname',
                'depot:id,depot_name'
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
            return prepareResult(false, [], $validate['errors'], "Error while validating order.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $order = Order::where('uuid', $uuid)->first();

            //Delete old record
            OrderDetail::where('order_id', $order->id)->delete();

            $order->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $order->depot_id            = (!empty($request->depot_id)) ? $request->depot_id : null;
            $order->order_type_id       = $request->order_type_id;
            $order->order_number        = $request->order_number;
            $order->order_date          = date('Y-m-d');
            $order->delivery_date       = $request->delivery_date;
            $order->payment_term_id     = $request->payment_term_id;
            $order->due_date            = $request->due_date;
            $order->total_qty           = $request->total_qty;
            $order->total_gross         = $request->total_gross;
            $order->total_discount_amount   = $request->total_discount_amount;
            $order->total_net           = $request->total_net;
            $order->total_vat           = $request->total_vat;
            $order->total_excise        = $request->total_excise;
            $order->grand_total         = $request->grand_total;
            $order->any_comment         = $request->any_comment;
            $order->source              = $request->source;
            $order->save();

            if ($isActivate = checkWorkFlowRule('Order', 'edit')) {
                $this->createWorkFlowObject($isActivate, 'Order', $request, $order->id);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $orderDetail = new OrderDetail;
                    $orderDetail->order_id      = $order->id;
                    $orderDetail->item_id       = $item['item_id'];
                    $orderDetail->item_uom_id   = $item['item_uom_id'];
                    $orderDetail->discount_id   = $item['discount_id'];
                    $orderDetail->is_free       = $item['is_free'];
                    $orderDetail->is_item_poi   = $item['is_item_poi'];
                    $orderDetail->promotion_id  = $item['promotion_id'];
                    $orderDetail->item_qty      = $item['item_qty'];
                    $orderDetail->item_price    = $item['item_price'];
                    $orderDetail->item_gross    = $item['item_gross'];
                    $orderDetail->item_discount_amount = $item['item_discount_amount'];
                    $orderDetail->item_net      = $item['item_net'];
                    $orderDetail->item_vat      = $item['item_vat'];
                    $orderDetail->item_excise   = $item['item_excise'];
                    $orderDetail->item_grand_total = $item['item_grand_total'];
                    $orderDetail->save();
                }
            }

            \DB::commit();
            $order->orderDetails;
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
     * @param  int  $uuid
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
            return prepareResult(false, [], $validate['errors'], "Error while validating order", $this->unprocessableEntity);
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $item_id, $item_uom_id, $item_qty
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
            return prepareResult(false, [], $validate['errors'], "Error while validating order", $this->unprocessableEntity);
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
                $item_vat_percentage    = 0;
                $item_excise            = 0;
                $getTotal               = 0;
                $discount               = 0;
                $discount_id            = 0;
                $discount_per           = 0;

                //////////Default Price
                $getItemInfo = Item::where('id', $request->item_id)
                    ->first();

                if ($getItemInfo) {
                    if ($getItemInfo->is_tax_apply == 1) {
                        $item_vat_percentage    = $getItemInfo->item_vat_percentage;
                        $item_net               = $getItemInfo->item_net;
                        $item_excise            = $getItemInfo->item_excise;
                    }
                }

                if ($request->customer_id) {
                    //Get Customer Info
                    $getCustomerInfo = CustomerInfo::find($request->customer_id);
                    //Location
                    $customerCountry    = $getCustomerInfo->user->country_id; //1
                    $customerRegion     = $getCustomerInfo->region_id; //2
                    $customerRoute      = $getCustomerInfo->route_id; //4

                    //Customer
                    $getAreaFromRoute   = Route::find($customerRoute);
                    $customerArea       = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                    $customerSalesOrganisation  = $getCustomerInfo->sales_organisation_id; //5
                    $customerChannel    = $getCustomerInfo->channel_id; //6
                    $customerCustomerCategory   = $getCustomerInfo->customer_category_id; //7
                    $customerCustomer   = $getCustomerInfo->id; //8
                }

                ////Item
                $itemMajorCategory  = $getItemInfo->item_major_category_id; //9
                $itemItemGroup      = $getItemInfo->item_group_id; //10
                $item               = $getItemInfo->id; //11

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
                        ->orderBy('priority_sequence', 'ASC')
                        ->orderBy('combination_key_code', 'DESC')
                        ->get();

                    // pre($getPricingList);
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

                        // pre($getKey);
                        // pre($getDiscountKey);

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

                    $item_qty       = $request->item_qty;
                    if ($lower_uom) {
                        $item_price     = $itemPrice->lower_unit_item_price;
                    } else {
                        $item_price     = $itemPrice->item_price;
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
                                        if ($item_qty >= $slab->min_slab  && $item_qty <= $slab->max_slab) {
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
                                        if ($item_gross >= $slab->min_slab  && $item_gross <= $slab->max_slab) {
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
                    $item_qty       = $request->item_qty;
                    $item_price     = $itemPrice->item_price;
                }

                $item_gross     = $item_qty * $item_price;
                $total_net      = $item_gross - $discount;
                $item_excise    = ($total_net * $item_excise) / 100;
                $item_vat       = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                $total = $total_net + $item_excise + $item_vat;

                $itemPriceInfo = [
                    'item_qty'        => $item_qty,
                    'item_price'      => number_format($item_price, 2, ".", ""),
                    'item_gross'      => number_format($item_gross, 2, ".", ""),
                    'discount'        => $discount,
                    'discount_percentage'        => $discount_per,
                    'discount_id'     => $discount_id,
                    'total_net'       => number_format($total_net, 2, ".", ""),
                    'is_free'         => false,
                    'is_item_poi'     => false,
                    'promotion_id'    => null,
                    'total_excise'    => number_format($item_excise, 2, ".", ""),
                    'total_vat'       => number_format($item_vat, 2, ".", ""),
                    'total'           => number_format(($total), 2, ".", ""),
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

    private function makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $itemMajorCategory, $itemItemGroup, $item, $combination_key_code, $combination_key, $price_disco_promo_plan_id, $p_d_p_item_id, $price, $priority_sequence)
    {
        $keyCodes = '';
        $combination_actual_id = '';
        foreach (explode('/', $combination_key_code) as $hierarchyNumber) {
            switch ($hierarchyNumber) {
                case '1':
                    if (empty($add)) {
                        $add  = $customerCountry;
                    } else {
                        $add  = '/' . $customerCountry;
                    }
                    // $add  = $customerCountry;
                    break;
                case '2':
                    if (empty($add)) {
                        $add  = $customerRegion;
                    } else {
                        $add  = '/' . $customerRegion;
                    }
                    // $add  = '/' . $customerRegion;
                    break;
                case '3':
                    if (empty($add)) {
                        $add  = $customerArea;
                    } else {
                        $add  = '/' . $customerArea;
                    }
                    // $add  = '/' . $customerArea;
                    break;
                case '4':
                    if (empty($add)) {
                        $add  = $customerRoute;
                    } else {
                        $add  = '/' . $customerRoute;
                    }
                    // $add  = '/' . $customerRoute;
                    break;
                case '5':
                    if (empty($add)) {
                        $add  = $customerSalesOrganisation;
                    } else {
                        $add  = '/' . $customerSalesOrganisation;
                    }
                    break;
                case '6':
                    if (empty($add)) {
                        $add  = $customerChannel;
                    } else {
                        $add  = '/' . $customerChannel;
                    }
                    // $add  = '/' . $customerChannel;
                    break;
                case '7':
                    if (empty($add)) {
                        $add  = $customerCustomerCategory;
                    } else {
                        $add  = '/' . $customerCustomerCategory;
                    }
                    // $add  = '/' . $customerCustomerCategory;
                    break;
                case '8':
                    if (empty($add)) {
                        $add  = $customerCustomer;
                    } else {
                        $add  = '/' . $customerCustomer;
                    }
                    // $add  = '/' . $customerCustomer;
                    break;
                case '9':
                    if (empty($add)) {
                        $add  = $itemMajorCategory;
                    } else {
                        $add  = '/' . $itemMajorCategory;
                    }
                    // $add  = '/' . $itemMajorCategory;
                    break;
                case '10':
                    if (empty($add)) {
                        $add  = $itemItemGroup;
                    } else {
                        $add  = '/' . $itemItemGroup;
                    }
                    // $add  = '/' . $itemItemGroup;
                    break;
                case '11':
                    if (empty($add)) {
                        $add  = $item;
                    } else {
                        $add  = '/' . $item;
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
        if ($getIdentify->use_for == 'Promotion') {
            return array(
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key'       => $combination_key,
                'combination_key_code'  => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                'p_d_p_promotion_items'         => $p_d_p_item_id,
                'priority_sequence'         => $priority_sequence,
                'price'                 => $price,
                'use_for'                 => $getIdentify->use_for
            );
        }

        if ($getIdentify->use_for == 'Discount') {
            return array(
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key'       => $combination_key,
                'combination_key_code'  => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                // 'auto_sequence_by_depth' => explode('/', $combination_key_code),
                // 'auto_sequence_by_depth_count' => count(explode('/', $combination_key_code)),
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                'p_d_p_item_id'         => $p_d_p_item_id,
                'priority_sequence'         => $priority_sequence,
                'price'                 => $price,
                'use_for'                 => $getIdentify->use_for,
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
            'combination_key'       => $combination_key,
            'combination_key_code'  => $combination_key_code,
            'combination_actual_id' => $combination_actual_id,
            // 'auto_sequence_by_depth' => explode('/', $combination_key_code),
            // 'auto_sequence_by_depth_count' => count(explode('/', $combination_key_code)),
            'auto_sequence_by_code' => $hierarchyNumber,
            'hierarchyNumber' => $keyCodes,
            'p_d_p_item_id'         => $p_d_p_item_id,
            'priority_sequence'         => $priority_sequence,
            'price'                 => $price,
            'use_for'                 => $getIdentify->use_for
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
                $return_fare[$n]  = $row[$field];
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

    private function checkDataExistOrNot($combination_key_number, $combination_actual_id, $price_disco_promo_plan_id)
    {
        switch ($combination_key_number) {
            case '1':
                $model  = 'App\Model\PDPCountry';
                $field  = 'country_id';
                break;
            case '2':
                $model  = 'App\Model\PDPRegion';
                $field  = 'region_id';
                break;
            case '3':
                $model  = 'App\Model\PDPArea';
                $field  = 'area_id';
                break;
            case '4':
                $model  = 'App\Model\PDPRoute';
                $field  = 'route_id';
                break;
            case '5':
                $model  = 'App\Model\PDPSalesOrganisation';
                $field  = 'sales_organisation_id';
                break;
            case '6':
                $model  = 'App\Model\PDPChannel';
                $field  = 'channel_id';
                break;
            case '7':
                $model  = 'App\Model\PDPCustomerCategory';
                $field  = 'customer_category_id';
                break;
            case '8':
                $model  = 'App\Model\PDPCustomer';
                $field  = 'customer_id';
                break;
            case '9':
                $model  = 'App\Model\PDPItemMajorCategory';
                $field  = 'item_major_category_id';
                break;
            case '10':
                $model  = 'App\Model\PDPItemGroup';
                $field  = 'item_group_id';
                break;
            case '11':
                $model  = 'App\Model\PDPItem';
                $field  = 'item_id';
                break;
            default:
                $model  = '';
                $field  = '';
                break;
        }

        $checkExistOrNot = $model::where('price_disco_promo_plan_id', $price_disco_promo_plan_id)->where($field, $combination_actual_id)->first();

        // pre($checkExistOrNot);

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
                'payment_term_id' => 'required|integer|exists:payment_terms,id',
                'total_qty' => 'required',
                'total_discount_amount' => 'required',
                'total_vat' => 'required',
                'total_net' => 'required',
                'total_excise' => 'required',
                'grand_total' => 'required',
                'source' => 'required|integer',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'order_ids'     => 'required'
            ]);
        }

        if ($type == 'item-apply-price') {
            $validator = \Validator::make($input, [
                'item_id'       => 'required|integer|exists:items,id',
                'item_uom_id'   => 'required|integer|exists:item_uoms,id',
                'item_qty'      => 'required|numeric',
            ]);
        }

        if ($type == 'normal-item-apply-price') {
            $validator = \Validator::make($input, [
                'item_id'       => 'required|integer|exists:items,id',
                'item_uom_id'   => 'required|integer|exists:item_uoms,id',
                'item_qty'      => 'required|numeric',
            ]);
        }

        if ($type == 'applyPDP') {
            $validator = \Validator::make($input, [
                'item_id'       => 'required|integer|exists:items,id'
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $item_id, $item_uom_id, $item_qty
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
            return prepareResult(false, [], $validate['errors'], "Error while validating order", $this->unprocessableEntity);
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
                $item_vat_percentage    = 0;
                $item_excise            = 0;
                $getTotal               = 0;
                $discount               = 0;
                $discount_id            = 0;
                $discount_per           = 0;

                //////////Default Price
                $getItemInfo = Item::where('id', $request->item_id)
                    ->first();

                if ($getItemInfo) {
                    if ($getItemInfo->is_tax_apply == 1) {
                        $item_vat_percentage    = $getItemInfo->item_vat_percentage;
                        $item_net               = $getItemInfo->item_net;
                        $item_excise            = $getItemInfo->item_excise;
                    }
                }

                if ($request->customer_id) {
                    //Get Customer Info
                    $getCustomerInfo = CustomerInfo::find($request->customer_id);
                    //Location
                    $customerCountry    = $getCustomerInfo->user->country_id; //1
                    $customerRegion     = $getCustomerInfo->region_id; //2
                    $customerRoute      = $getCustomerInfo->route_id; //4

                    //Customer
                    $getAreaFromRoute   = Route::find($customerRoute);
                    $customerArea       = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                    $customerSalesOrganisation  = $getCustomerInfo->sales_organisation_id; //5
                    $customerChannel    = $getCustomerInfo->channel_id; //6
                    $customerCustomerCategory   = $getCustomerInfo->customer_category_id; //7
                    $customerCustomer   = $getCustomerInfo->id; //8
                }

                ////Item
                $itemMajorCategory  = $getItemInfo->item_major_category_id; //9
                $itemItemGroup      = $getItemInfo->item_group_id; //10
                $item               = $getItemInfo->id; //11

                // if (!$request->customer_id) {
                if ($lower_uom) {
                    $item_price     = $itemPrice->lower_unit_item_price;
                } else {
                    $item_price     = $itemPrice->item_price;
                }
                $item_qty       = $request->item_qty;
                // $item_price     = $itemPrice->item_price;
                // }

                $item_gross     = $item_qty * $item_price;
                $total_net      = $item_gross - $discount;
                $item_excise    = ($total_net * $item_excise) / 100;
                $item_vat       = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                $total = $total_net + $item_excise + $item_vat;

                $itemPriceInfo = [
                    'item_qty'        => $item_qty,
                    'item_price'      => number_format($item_price, 2, ".", ""),
                    'item_gross'      => number_format($item_gross, 2, ".", ""),
                    'discount'        => $discount,
                    'discount_percentage'        => $discount_per,
                    'discount_id'     => $discount_id,
                    'total_net'       => number_format($total_net, 2, ".", ""),
                    'is_free'         => false,
                    'is_item_poi'     => false,
                    'promotion_id'    => null,
                    'total_excise'    => number_format($item_excise, 2, ".", ""),
                    'total_vat'       => number_format($item_vat, 2, ".", ""),
                    'total'           => number_format(($total), 2, ".", ""),
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $item_id, $item_uom_id, $item_qty
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
            $item_vat_percentage    = 0;
            $item_excise            = 0;
            $getTotal               = 0;
            $discount               = 0;
            $discount_id            = 0;
            $discount_per           = 0;

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
                $customerCountry    = $getCustomerInfo->user->country_id; //1
                $customerRegion     = $getCustomerInfo->region_id; //2
                $customerRoute      = $getCustomerInfo->route_id; //4

                //Customer
                $getAreaFromRoute   = Route::find($customerRoute);
                $customerArea       = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                $customerSalesOrganisation  = $getCustomerInfo->sales_organisation_id; //5
                $customerChannel    = $getCustomerInfo->channel_id; //6
                $customerCustomerCategory   = $getCustomerInfo->customer_category_id; //7
                $customerCustomer   = $getCustomerInfo->id; //8
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
                    ->whereIn('item_qty', $request->item_qty)
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
                        $itemMajorCategory  = $items->item_major_category_id; //9
                        $itemItemGroup      = $items->item_group_id; //10
                        $item               = $items->id; //11

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
                    // CHeck order item and offer item
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
                                ->with('PDPPromotionOfferItems', 'PDPPromotionOfferItems.item:id,item_name', 'PDPPromotionOfferItems.itemUom:id,name')
                                ->first();

                            foreach ($PDPPromotionItems as $key => $item) {
                                $qty = $request->item_qty[$key];
                                if ($item->item_qty == $request->item_qty[$key]) {
                                    $is_promotion = true;
                                    $promotion_item = $price_disco_promo_plan_offer->PDPPromotionOfferItems;
                                    $item_price     = $item->price;
                                    $item_qty       = $qty;
                                    $item_gross     = $item_qty * $item_price;
                                    $total_net      = $item_gross - $discount;
                                    $item_excise    = ($total_net * $item_excise) / 100;
                                    $item_vat       = (($total_net + $item_excise) * $item_vat_percentage) / 100;

                                    $total = $total_net + $item_excise + $item_vat;

                                    $itemPromotionInfo[] = [
                                        'item_price'      => number_format($item_price, 2, ".", ""),
                                        'item_gross'      => number_format($item_gross, 2, ".", ""),
                                        'discount'        => $discount,
                                        'total_net'       => number_format($total_net, 2, ".", ""),
                                        'is_free'         => true,
                                        'is_item_poi'     => false,
                                        'order_item_type'     => $price_disco_promo_plan->order_item_type,
                                        'promotion_id'    => $item->id,
                                        'promotion_item'    => $promotion_item,
                                        'total_excise'    => number_format($item_excise, 2, ".", ""),
                                        'total_vat'       => number_format($item_vat, 2, ".", ""),
                                        'total'           => number_format(($total), 2, ".", ""),
                                    ];
                                }
                            }
                        }
                    }
                }
            }


            // pre($getItemInfo);

            \DB::commit();
            return prepareResult(true, $itemPromotionInfo, [], "Item price.", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request, $raw_id)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id   = $work_flow_rule_id;
        $createObj->module_name         = $module_name;
        $createObj->raw_id                 = $raw_id;
        $createObj->request_object      = $request->all();
        $createObj->save();
    }
}
