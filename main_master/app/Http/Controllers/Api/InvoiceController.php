<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\InvoiceImport;
use App\Model\CodeSetting;
use App\Model\CollectionDetails;
use App\Model\CustomerInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\Order;
use App\Model\Delivery;
use App\Model\DeliveryDetail;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\OrderDetail;
use App\Model\Route;
use App\Model\OrderType;
use App\Model\SalesmanNumberRange;
use App\Model\Transaction;
use App\Model\TransactionDetail;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\User;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;
use App\Model\Warehouse;
use App\Model\CustomerLob;
use App\Model\WorkFlowRuleApprovalUser;
use Ixudra\Curl\Facades\Curl;

class InvoiceController extends Controller
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

        $invoices_query = Invoice::with(array('user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'user:id,parent_id,firstname,lastname,email',
                'user.customerInfo:id,user_id,customer_code',
                'salesmanUser:id,parent_id,firstname,lastname,email',
                'salesmanUser.salesmanInfo:id,user_id,salesman_code',
                'route:id,route_name,route_code',
                'depot',
                'order',
                'order.orderDetails',
                'invoices',
                'invoices.item:id,item_name,item_code,lower_unit_uom_id',
                'invoices.itemUom:id,name,code',
                'orderType:id,name,description',
                'invoiceReminder:id,uuid,is_automatically,message,invoice_id',
                'invoiceReminder.invoiceReminderDetails',
                'lob'
            );


        if ($request->invoice_date) {
            $invoices_query->whereDate('created_at',  $request->invoice_date);
        }

        if ($request->invoice_number) {
            $invoices_query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        if ($request->status) {
            $invoices_query->where('current_stage', 'like', '%' . $request->status . '%');
        }

        if ($request->invoice_due_date) {
            $invoices_query->whereDate('invoice_due_date', $request->invoice_due_date);
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $invoices_query->whereHas('user', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $invoices_query->whereHas('user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $invoices_query->whereHas('user.customerInfo', function ($q) use ($customer_code) {
                $q->where('customer_code', 'like', '%' . $customer_code . '%');
            });
        }

        if ($request->route_name) {
            $route_name = $request->route_name;
            $invoices_query->whereHas('route', function ($q) use ($route_name) {
                $q->where('route_name', 'like',  '%' . $route_name . '%');
            });
        }

        if ($request->route_code) {
            $route_code = $request->route_code;
            $invoices_query->whereHas('route', function ($q) use ($route_code) {
                $q->where('route_code', 'like',  '%' . $route_code . '%');
            });
        }

        $invoices = $invoices_query->orderBy('id', 'desc')
            ->get();

        // approval
        $results = GetWorkFlowRuleObject('Invoice');
        $approve_need_invoice = array();
        $approve_need_invoice_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_invoice[] = $raw['object']->raw_id;
                $approve_need_invoice_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        //  approval
        $invoices_array = array();
        if (is_object($invoices)) {
            foreach ($invoices as $key => $invoices1) {
                if (in_array($invoices[$key]->id, $approve_need_invoice)) {
                    $invoices[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_invoice_detail_object_id[$invoices[$key]->id])) {
                        $invoices[$key]->objectid = $approve_need_invoice_detail_object_id[$invoices[$key]->id];
                    } else {
                        $invoices[$key]->objectid = '';
                    }
                } else {
                    $invoices[$key]->need_to_approve = 'no';
                    $invoices[$key]->objectid = '';
                }

                if ($invoices[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($invoices[$key]->id, $approve_need_invoice)) {
                    $invoices_array[] = $invoices[$key];
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
                if (isset($invoices_array[$offset])) {
                    $data_array[] = $invoices_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($invoices_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($invoices_array);
        } else {
            $data_array = $invoices_array;
        }

        return prepareResult(true, $data_array, [], "Invoices listing", $this->success, $pagination);
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

        if ($request->source == 1 && !$request->salesman_id) {
            return prepareResult(false, [], "Error Please add Salesman", "Error while validating invoice", $this->unprocessableEntity);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }

        // if ($request->invoice_type == 1 && $request->depot_id == NULL) {
        //     return prepareResult(false, [], [], "Error Please add depot.", $this->unprocessableEntity);
        // }

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
            if ($isActivate = checkWorkFlowRule('Invoice', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Deliviery',$request);
            }

            $order_type_id = $request->order_type_id;
            $order_id = $request->order_id;
            $delivery_id = $request->delivery_id;

            if ($request->invoice_type == 1) {
                $order_id = null;
                $delivery_id = null;
            }

            $invoice = new Invoice;
            $invoice->customer_id         = $request->customer_id;
            $invoice->order_id            = $order_id;
            $invoice->order_type_id       = $order_type_id;
            $invoice->delivery_id         = $delivery_id;
            $invoice->depot_id            = $request->depot_id;
            $invoice->trip_id             = $request->trip_id;
            $invoice->salesman_id         = $request->salesman_id;
            $invoice->route_id            = (!empty($route_id)) ? $route_id : null;
            $invoice->invoice_type        = $request->invoice_type;
            if ($request->source == 1) {
                $invoice->invoice_number  = $request->invoice_number;
            } else {
                $invoice->invoice_number  = nextComingNumber('App\Model\Invoice', 'invoice', 'invoice_number', $request->invoice_number);
            }
            $invoice->invoice_date        = date('Y-m-d', strtotime($request->invoice_date));
            $invoice->payment_term_id     = $request->payment_term_id;
            $invoice->invoice_due_date    = $request->invoice_due_date;
            $invoice->total_qty           = $request->total_qty;
            $invoice->total_gross         = $request->total_gross;
            $invoice->total_discount_amount   = $request->total_discount_amount;
            $invoice->total_net           = $request->total_net;
            $invoice->total_vat           = $request->total_vat;
            $invoice->total_excise        = $request->total_excise;
            $invoice->grand_total         = $request->grand_total;
            if ($request->is_exchange == 1) {
                $invoice->pending_credit  = $request->pending_credit;
            } else {
                $invoice->pending_credit  = $request->grand_total;
            }
            $invoice->current_stage       = $current_stage;
            $invoice->current_stage_comment         = $request->current_stage_comment;
            $invoice->source              = $request->source;
            $invoice->status              = $status;
            $invoice->approval_status     = "Created";
            $invoice->is_premium_invoice  = (!empty($request->is_premium_invoice)) ? $request->is_premium_invoice = 1 : null;
            $invoice->lob_id              = (!empty($request->lob_id)) ? $request->lob_id : null;
            $invoice->customer_lpo        = (!empty($request->customer_lpo)) ? $request->customer_lpo : null;
            $invoice->is_exchange         = (isset($request->is_exchange)) ? 1 : 0;
            $invoice->exchange_number     = (isset($request->exchange_number)) ? $request->exchange_number : null;
            $invoice->save();

            if ($invoice->source == 1) {
                $transactionheader = Transaction::where('trip_id', $request->trip_id)
                    ->where('salesman_id', $request->salesman_id)
                    ->first();

                $transactionheader->trip_id = $request->trip_id;
                $transactionheader->salesman_id = $invoice->salesman_id;
                $transactionheader->route_id = $request->route_id;
                $transactionheader->transaction_type = 5;
                $transactionheader->transaction_date = date('Y-m-d');
                $transactionheader->transaction_time = date('Y-m-d H:i:s');
                $transactionheader->reference = "Invoice Mobile";
                $transactionheader->save();
            }

            $invoice_id = $invoice->id;

            if ($isActivate = checkWorkFlowRule('Invoice', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Invoice', $request, $invoice);
            }

            $delivery_details_ids = array();
            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //-----------------------Deduct from Route Storage Loaction
                    $conversation = getItemDetails2($item['item_id'], $item['item_uom_id'], $item['item_qty']);
                    if ($request->source == 1) {
                        $routelocation = Storagelocation::where('route_id', $request->route_id)
                            ->where('loc_type', '1')
                            ->first();
                        if (is_object($routelocation)) {

                            $routestoragelocation_id = $routelocation->id;


                            $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                ->where('item_id', $item['item_id'])
                                ->first();


                            if (is_object($routelocation_detail)) {


                                if ($routelocation_detail->qty >= $conversation['Qty']) {
                                    $routelocation_detail->qty = ($routelocation_detail->qty - $conversation['Qty']);
                                    $routelocation_detail->save();
                                } else {
                                    $item_detail = Item::where('id', $item['item_id'])->first();
                                    return prepareResult(false, [], ["error" => "Item is out of stock! the item name is $item_detail->item_name"], " Item is out of stock!  the item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                            } else {
                                //--------Item not available Error
                                $item_detail = Item::where('id', $item['item_id'])->first();
                                return prepareResult(false, [], ["error" => "Item not available!. the item name is $item_detail->item_name"], " Item not available! the item name is  $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        } else {
                            return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    } else {

                        $customer = CustomerInfo::where('user_id', $request->customer_id)->first();
                        $customer_id = $customer->id;

                        if ($customer->is_lob == 1) { //lob customer
                            $customerlob = CustomerLob::where('customer_info_id', $customer_id)->first();
                            $route_id = $customerlob->route_id;
                        } elseif ($customer->is_lob == 0) { // Central
                            $customer = CustomerInfo::where('user_id', $request->customer_id)->first();
                            $route_id = $customer->route_id;
                        }

                        /*   $customerlob = CustomerLob::where('customer_info_id', $customer_id)->first();
                            $route_id = $customerlob->route_id; */

                        $routes = Route::find($route_id);
                        $depot_id = $routes->depot_id;

                        $Warehouse = Warehouse::where('depot_id', $depot_id)->first();

                        if (is_object($Warehouse)) {
                            $routelocation = Storagelocation::where('warehouse_id', $Warehouse->id)
                                ->where('loc_type', '1')
                                ->first();

                            if (is_object($routelocation)) {

                                $routestoragelocation_id = $routelocation->id;

                                $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                    ->where('item_id', $item['item_id'])
                                    ->first();


                                if (is_object($routelocation_detail)) {

                                    if ($routelocation_detail->qty >= $conversation['Qty']) {
                                        $routelocation_detail->qty = ($routelocation_detail->qty - $conversation['Qty']);
                                        $routelocation_detail->save();
                                    } else {
                                        $item_detail = Item::where('id', $item['item_id'])->first();
                                        return prepareResult(false, [], ["error" => "Item is out of stock! the item name is $item_detail->item_name"], " Item is out of stock!  the item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                    }
                                } else {
                                    //--------Item not available Error
                                    $item_detail = Item::where('id', $item['item_id'])->first();
                                    return prepareResult(false, [], ["error" => "Item not available!. the item name is $item_detail->item_name"], " Item not available! the item name is  $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                            } else {
                                return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        } else {

                            return prepareResult(false, [], ["error" => "Wherehouse not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    }

                    //-----------------------
                    if ($request->invoice_type == 2 || $request->invoice_type == 3) {
                        $delivery_details = DeliveryDetail::where('id', $item['id'])->first();

                        if ($delivery_details->item_qty == $item['item_qty']) {
                            $open_qty = 0.00;
                            $invoiced_qty = $item['item_qty'];
                            if ($delivery_details->delivery_status == "Pending") {
                                $delivery_details->delivery_status = "Invoiced";
                                $delivery_details->open_qty = $open_qty;
                                $delivery_details->invoiced_qty = $invoiced_qty;
                                $delivery_details->save();
                            }
                        } else {
                            if ($delivery_details->open_qty != 0) {
                                $open_qty = $delivery_details->open_qty - $item['item_qty'];
                                $invoiced_qty = $item['item_qty'] + $delivery_details->invoiced_qty;
                            } else {
                                $open_qty = $delivery_details->item_qty - $item['item_qty'];
                                $invoiced_qty = $item['item_qty'];
                            }

                            $delivery_details->open_qty = $open_qty;
                            $delivery_details->invoiced_qty = $invoiced_qty;
                            $delivery_details->delivery_status = 'Partial-Invoiced';
                            $delivery_details->save();
                        }

                        if ($delivery_details->item_qty == $delivery_details->invoiced_qty) {
                            $delivery_details->delivery_status = 'Invoiced';
                            $delivery_details->save();
                        }
                    }

                    $invoiceDetail = new InvoiceDetail;
                    if (isset($item['id']) && $item['id']) {
                        $delivery_details_ids[] = $item['id'];
                    }
                    $invoiceDetail->invoice_id      = $invoice_id;
                    $invoiceDetail->item_id       = $item['item_id'];
                    $invoiceDetail->item_uom_id   = $item['item_uom_id'];
                    $invoiceDetail->discount_id   = $item['discount_id'];
                    $invoiceDetail->is_free       = $item['is_free'];
                    $invoiceDetail->is_item_poi   = $item['is_item_poi'];
                    $invoiceDetail->promotion_id  = $item['promotion_id'];
                    $invoiceDetail->item_qty      = $item['item_qty'];
                    $invoiceDetail->item_price    = $item['item_price'];
                    $invoiceDetail->item_gross    = $item['item_gross'];
                    $invoiceDetail->item_discount_amount = $item['item_discount_amount'];
                    $invoiceDetail->item_net      = $item['item_net'];
                    $invoiceDetail->item_vat      = $item['item_vat'];
                    $invoiceDetail->item_excise   = $item['item_excise'];
                    $invoiceDetail->item_grand_total = $item['item_grand_total'];
                    $invoiceDetail->save();

                    if ($invoice->source == 1) {

                        $items = Item::where('id', $item['item_id'])
                            ->where('lower_unit_uom_id', $item['item_uom_id'])
                            ->first();

                        $qty = 0;

                        if (is_object($items)) {
                            $qty = $item['item_qty'];
                        } else {
                            $items = ItemMainPrice::where('item_id', $item['item_id'])
                                ->where('item_uom_id', $item['item_uom_id'])
                                ->first();

                            $qty = $items->item_upc * $item['item_qty'];
                        }
                        $transactiondetails = TransactionDetail::where('transaction_id', $transactionheader->id)
                            ->where('item_id', $item['item_id'])
                            ->first();

                        $newqty = $transactiondetails->sales_qty + $qty;
                        $transactiondetails->item_id = $item['item_id'];
                        $transactiondetails->sales_qty = $newqty;
                        $transactiondetails->save();
                    }
                }

                if ($request->invoice_type == 2 || $request->invoice_type == 3) {
                    if (isset($delivery_id) && $delivery_id) {

                        $deliveryDetails = DeliveryDetail::where('delivery_id', $delivery_id)
                            ->whereIn('delivery_status', ['Pending', 'Partial-Invoiced'])
                            ->get();

                        $delivery = Delivery::where('id', $delivery_id)->first();
                        if (count($deliveryDetails) < 1) {
                            $delivery->approval_status = "Completed";
                        } else {
                            $delivery->approval_status = "Partial-Invoiced";
                        }

                        $delivery->save();

                        if ($request->invoice_type == 2) {
                            $deliveryData = Delivery::where('order_id', $order_id)
                                ->whereIn('approval_status', ['Deleted', 'Created', 'Updated', 'In-Process', 'Partial-Invoiced'])
                                ->orderBy('id', 'desc')
                                ->get();

                            if (count($deliveryData) < 1) {
                                $order = Order::find($order_id);
                                $order->approval_status = 'Completed';
                                $order->save();
                            }
                        }
                    }
                }
            }

            create_action_history("Invoice", $invoice->id, auth()->user()->id, "create", "Invoice created by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            if (is_object($invoice) && $invoice->source == 1) {
                $user = User::find($request->user()->id);
                if (is_object($user)) {
                    $salesmanInfo = $user->salesmanInfo;
                    $smr = SalesmanNumberRange::where('salesman_id', $salesmanInfo->id)->first();
                    $smr->invoice_from = $request->invoice_number;
                    $smr->save();
                }

                // mobile data
                // $getOrderType = OrderType::find($request->order_type_id);
                // preg_match_all('!\d+!', $getOrderType->next_available_code, $newNumber);
                // $formattedNumber = sprintf("%0".strlen($getOrderType->end_range)."d", ($newNumber[0][0]+1));
                // $actualNumber =  $getOrderType->prefix_code.$formattedNumber;
                // $getOrderType->next_available_code = $actualNumber;
                // $getOrderType->save();
            }

            if ($invoice->source != 1) {
                updateNextComingNumber('App\Model\Invoice', 'invoice');
            }

            \DB::commit();

            // $invoice->getSaveData();

            $this->postInvoiceOdoo($invoice->id);

            return prepareResult(true, $invoice, [], "Invoice added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating invoice.", $this->unauthorized);
        }

        $invoice = Invoice::with(array('user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'user:id,parent_id,firstname,lastname',
                'depot',
                'order',
                'order.orderDetails',
                'invoices',
                'invoices.item:id,item_name,item_code,lower_unit_uom_id',
                'invoices.itemUom:id,name,code',
                'invoices.item.itemMainPrice',
                'invoices.item.itemMainPrice.itemUom:id,name',
                'invoices.item.itemUomLowerUnit:id,name',
                'orderType:id,name,description',
                'invoiceReminder',
                'lob'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($invoice)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        $html = view('html.invoice', compact('invoice'))->render();
        $invoice->html_string = $html;

        return prepareResult(true, $invoice, [], "Invoice Edit", $this->success);
    }


    public function postInvoiceOdoo($invoice_id)
    {
        $invoice_data = Invoice::with(
            'user:id,parent_id,firstname,lastname,email',
            'user.customerInfo:id,user_id,customer_code',
            'salesmanUser:id,parent_id,firstname,lastname,email',
            'salesmanUser.salesmanInfo:id,user_id,salesman_code',
            'route:id,route_name,route_code',
            'depot',
            'order',
            'order.orderDetails',
            'invoices',
            'invoices.item:id,item_name,item_code,lower_unit_uom_id',
            'invoices.itemUom:id,name,code',
            'orderType:id,name,description',
            'invoiceReminder:id,uuid,is_automatically,message,invoice_id',
            'invoiceReminder.invoiceReminderDetails',
            'lob'
        )->find($invoice_id);

        $response = Curl::to('http://rfctest.dyndns.org:11214/api/create/invoice')
            ->withData(array('params' => $invoice_data))
            ->asJson(true)
            ->post();

        if (isset($response['result'])) {
            $data = json_decode($response['result']);
            if ($data->response[0]->state == "success") {
                $invoice_data->oddo_post_id = $data->response[0]->inv_id;
            } else {
                $invoice_data->odoo_failed_response = $response['result'];
            }
        }

        if (isset($response['error'])) {
            $invoice_data->odoo_failed_response = $response['error'];
        }
        $invoice_data->save();

        if (!empty($invoice_data->oddo_post_id)) {
            return prepareResult(true, $invoice_data, [], "Invoice post sucessfully", $this->success);
        }

        return prepareResult(false, $invoice_data, [], "Invoice not posted", $this->unprocessableEntity);
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

        if ($request->source == 1 && !$request->salesman_id) {
            return prepareResult(false, [], "Error Please add Salesman", "Error while validating salesman", $this->unprocessableEntity);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice.", $this->unprocessableEntity);
        }

        // if ($request->invoice_type == 1 && $request->depot_id == NULL) {
        //     return prepareResult(false, [], [], "Error Please add depot.", $this->unprocessableEntity);
        // }

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
            $order_type_id = $request->order_type_id;
            $order_id = $request->order_id;
            $delivery_id = $request->delivery_id;
            if ($request->invoice_type == "2") {
                $order_id = null;
                $delivery_id = null;
            }
            if ($order_id != '' || $order_id != null) {
                $order = Order::find($order_id);
                if ($order) {
                    $order_type_id = $order->order_type_id;
                }
            }

            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Invoice', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Deliviery',$request);
            }

            $invoice = Invoice::where('uuid', $uuid)->first();

            //Delete old record
            InvoiceDetail::where('invoice_id', $invoice->id)->delete();

            $invoice->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $invoice->order_id            = $order_id;
            $invoice->order_type_id       = $order_type_id;
            $invoice->delivery_id         = $delivery_id;
            $invoice->depot_id            = $request->depot_id;
            $invoice->invoice_type        = $request->invoice_type;
            $invoice->invoice_number      = $request->invoice_number;
            $invoice->route_id            = (!empty($route_id)) ? $route_id : null;
            $invoice->invoice_date        = date('Y-m-d', strtotime($request->invoice_date));
            $invoice->payment_term_id     = $request->payment_term_id;
            $invoice->invoice_due_date    = $request->invoice_due_date;
            $invoice->total_qty           = $request->total_qty;
            $invoice->total_gross         = $request->total_gross;
            $invoice->total_discount_amount   = $request->total_discount_amount;
            $invoice->total_net           = $request->total_net;
            $invoice->total_vat           = $request->total_vat;
            $invoice->total_excise        = $request->total_excise;
            $invoice->grand_total         = $request->grand_total;
            $invoice->pending_credit      = $request->grand_total;
            $invoice->current_stage       = $current_stage;
            $invoice->current_stage_comment         = $request->current_stage_comment;
            $invoice->source              = $request->source;
            $invoice->status              = $status;
            $invoice->approval_status     = "Created";
            $invoice->lob_id              = (!empty($request->lob_id)) ? $request->lob_id : null;
            $invoice->customer_lpo        = (!empty($request->customer_lpo)) ? $request->customer_lpo : null;
            // $invoice->is_exchange         = (!empty($request->is_exchange)) ? 1 : 0;
            // $invoice->exchange_number     = (!empty($request->exchange_number)) ? $request->exchange_number : null;
            $invoice->save();

            if ($isActivate = checkWorkFlowRule('Invoice', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Invoice', $request, $invoice);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    if ($request->invoice_type == 2 || $request->invoice_type == 3) {

                        $delivery_details = DeliveryDetail::where('id', $item['id'])->first();

                        if ($delivery_details->item_qty == $item['item_qty']) {
                            $open_qty = 0.00;
                            $invoiced_qty = $item['item_qty'];
                            if ($delivery_details->delivery_status == "Pending") {
                                $delivery_details->delivery_status = "Invoiced";
                                $delivery_details->open_qty = $open_qty;
                                $delivery_details->invoiced_qty = $invoiced_qty;
                                $delivery_details->save();
                            }
                        } else {

                            if ($delivery_details->open_qty != 0) {
                                $open_qty = $delivery_details->open_qty - $item['item_qty'];
                                $invoiced_qty = $item['item_qty'] + $delivery_details->invoiced_qty;
                            } else {
                                $open_qty = $delivery_details->item_qty - $item['item_qty'];
                                $invoiced_qty = $item['item_qty'];
                            }

                            $delivery_details->open_qty = $open_qty;
                            $delivery_details->invoiced_qty = $invoiced_qty;
                            $delivery_details->delivery_status = 'Partial-Invoiced';
                            $delivery_details->save();
                        }

                        if ($delivery_details->item_qty == $delivery_details->invoiced_qty) {
                            $delivery_details->delivery_status = 'Invoiced';
                            $delivery_details->save();
                        }
                    }

                    $invoiceDetail = new InvoiceDetail;
                    $invoiceDetail->invoice_id      = $invoice->id;
                    $invoiceDetail->item_id       = $item['item_id'];
                    $invoiceDetail->item_uom_id   = $item['item_uom_id'];
                    $invoiceDetail->discount_id   = $item['discount_id'];
                    $invoiceDetail->is_free       = $item['is_free'];
                    $invoiceDetail->is_item_poi   = $item['is_item_poi'];
                    $invoiceDetail->promotion_id  = $item['promotion_id'];
                    $invoiceDetail->item_qty      = $item['item_qty'];
                    $invoiceDetail->item_price    = $item['item_price'];
                    $invoiceDetail->item_gross    = $item['item_gross'];
                    $invoiceDetail->item_discount_amount = $item['item_discount_amount'];
                    $invoiceDetail->item_net      = $item['item_net'];
                    $invoiceDetail->item_vat      = $item['item_vat'];
                    $invoiceDetail->item_excise   = $item['item_excise'];
                    $invoiceDetail->item_grand_total = $item['item_grand_total'];
                    $invoiceDetail->save();
                }

                if ($request->invoice_type == 2 || $request->invoice_type == 3) {
                    if (isset($delivery_id) && $delivery_id) {

                        $deliveryDetails = DeliveryDetail::where('delivery_id', $delivery_id)
                            ->whereIn('delivery_status', ['Pending', 'Partial-Invoiced'])
                            ->orderBy('id', 'desc')
                            ->get();

                        $delivery = Delivery::where('id', $delivery_id)->first();
                        if (count($deliveryDetails) < 1) {
                            $delivery->approval_status = "Completed";
                        } else {
                            $delivery->approval_status = "Partial-Invoiced";
                        }

                        $delivery->save();

                        if ($request->invoice_type == 2) {
                            $deliveryData = Delivery::where('order_id', $order_id)
                                ->whereIn('approval_status', ['Deleted', 'Created', 'Updated', 'In-Process', 'Partial-Invoiced'])
                                ->orderBy('id', 'desc')
                                ->get();

                            if (count($deliveryData) < 1) {
                                $order = Order::find($order_id);
                                $order->approval_status = 'Completed';
                                $order->save();
                            }
                        }
                    }
                }
            }

            create_action_history("Invoice", $invoice->id, auth()->user()->id, "update", "Invoice updated by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            \DB::commit();
            $invoice->getSaveData();
            return prepareResult(true, $invoice, [], "Invoice updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating invoice.", $this->unauthorized);
        }

        $invoice = Invoice::where('uuid', $uuid)
            ->first();

        if (is_object($invoice)) {
            $invoiceId = $invoice->id;
            $invoice->delete();
            if ($invoice) {
                InvoiceDetail::where('invoice_id', $invoiceId)->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->invoice_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                Invoice::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $invoice = $this->index();
            return prepareResult(true, $invoice, [], "Invoice status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $invoice = Invoice::where('uuid', $uuid)
                    ->first();
                $invoiceId = $invoice->id;
                $invoice->delete();
                if ($invoice) {
                    InvoiceDetail::where('invoice_id', $invoiceId)->delete();
                }
            }
            $invoice = $this->index();
            return prepareResult(true, $invoice, [], "Invoice deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'order_type_id' => 'required|integer|exists:order_types,id',
                // 'customer_id' => 'required',
                'invoice_date' => 'required|date',
                'invoice_due_date' => 'required|date',
                // 'payment_term_id' => 'required|integer|exists:payment_terms,id',
                'invoice_type' => 'required',
                'invoice_number' => 'required',
                'total_qty' => 'required',
                'total_vat' => 'required',
                'total_net' => 'required',
                'total_excise' => 'required',
                'grand_total' => 'required',
                'source' => 'required|integer'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'invoice_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pendingInvoice($route_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$route_id) {
            return prepareResult(false, [], [], "Error while validating pending invoice.", $this->unauthorized);
        }
        $invoices_array = array();
        $customers = CustomerInfo::where('route_id', $route_id)->orderBy('id', 'desc')->get();

        if (is_object($customers)) {
            foreach ($customers as $customer) {
                $invoices = Invoice::where('payment_received', 0)
                    ->where('customer_id', $customer->user_id)
                    ->orderBy('id', 'desc')
                    ->get();

                if (is_object($invoices)) {
                    foreach ($invoices as $invoice) {
                        $collectiondetails = CollectionDetails::where('invoice_id', $invoice->id)->orderBy('id', 'desc')->get();
                        $total_paid = 0;
                        if (is_object($collectiondetails)) {
                            foreach ($collectiondetails as $collectiondetail) {
                                $total_paid = $total_paid + $collectiondetail->amount;
                            }
                        }
                        $invoice->pending_amount = ($invoice->grand_total - $total_paid);
                        $invoice->customer_name = $customer->firstname . ' ' . $customer->lastname;
                        $invoices_array[] = $invoice;
                    }
                }
            }
        }

        return prepareResult(true, $invoices_array, [], "Pending Invoices listing", $this->success);
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

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'invoice_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate Invoice import", $this->unauthorized);
        }

        Excel::import(new InvoiceImport, request()->file('invoice_file'));
        return prepareResult(true, [], [], "Invoice successfully imported", $this->success);
    }

    public function sendinvoice(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required',
            'to_email' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate send invoice", $this->unauthorized);
        }

        $subject = $request->subject;
        $to = $request->to_email;
        $from_email = 'admin@gmail.com';
        $from_name = 'Admin';
        $data['content'] = $request->message;


        Mail::send('emails.invoice', ['content' => $request->message, 'logo' => '', ' title' => '', 'branch_name' => ''], function ($message) use ($subject, $to, $from_email, $from_name) {
            $message->from($from_email, $from_name);
            $message->to($to);
            $message->subject($subject);
        });

        return prepareResult(true, [], [], "Mail sent successfully", $this->success);
    }

    public function getInvocieByID($id)
    {
        // if (!$this->isAuthorized) {
        //     return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        // }

        $invoices = Invoice::with(array('user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'user:id,parent_id,firstname,lastname,email',
                'user.customerInfo:id,user_id,customer_code',
                'salesmanUser:id,parent_id,firstname,lastname,email',
                'salesmanUser.salesmanInfo:id,user_id,salesman_code',
                'route:id,route_name,route_code',
                'depot',
                'order',
                'order.orderDetails',
                'invoices',
                'invoices.item:id,item_name',
                'invoices.itemUom:id,name,code',
                'orderType:id,name,description',
                'invoiceReminder:id,uuid,is_automatically,message,invoice_id',
                'invoiceReminder.invoiceReminderDetails',
                'lob'
            )->find($id);

        return prepareResult(true, $invoices, [], "Invoices Show", $this->success);
    }

    public function invoiceReason($id, Request $request)
    {

        $invoice = Invoice::where('id', $id)->first();
        $invoice->reason = $request->reason;
        $invoice->save();
        return prepareResult(true, [], [], "Invoice reason Inserted", $this->success);
    }

    public function invoiceCancel($id, Request $request)
    {

        $invoice = Invoice::where('id', $id)->first();
        $invoice->current_stage = 'Canceled';
        $invoice->save();
        $this->oddoCancelInvoice($invoice);
        return prepareResult(true, [], [], "Invoice status updated", $this->success);
    }

    public function oddoCancelInvoice($invoice)
    {
        $params = [
            'name' => $invoice->invoice_number
        ];
        return Curl::to('http://rfctest.dyndns.org:11214/api/cancel/invoice')
            ->withData(array('params' => $params))
            ->asJson(true)
            ->post();
    }
}
