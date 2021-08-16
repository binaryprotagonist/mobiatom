<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CreditNote;
use App\Model\CreditNoteDetail;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\SalesmanNumberRange;
use App\Model\WorkFlowObject;
use App\User;
use Ixudra\Curl\Facades\Curl;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Imports\CreditnoteImport;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\Transaction;
use App\Model\TransactionDetail;
use Carbon\Carbon;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;
use App\Model\WorkFlowRuleApprovalUser;

class CreditNoteController extends Controller
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

        $creditnotes_query = CreditNote::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerinfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmaninfo:id,user_id,salesman_code',
                'invoice',
                'creditNoteDetails',
                'creditNoteDetails.item:id,item_name,item_code',
                'creditNoteDetails.itemUom:id,name,code',
                'lob',
                'route:id,route_name,route_code'
            );
        //->where('order_date', date('Y-m-d'))

        if ($request->date) {
            $creditnotes_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->credit_note_number) {
            $creditnotes_query->where('credit_note_number', 'like', '%' . $request->credit_note_number . '%');
        }

        if ($request->current_stage) {
            $creditnotes_query->where('current_stage', 'like', '%' . $request->current_stage . '%');
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $creditnotes_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $creditnotes_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $creditnotes_query->whereHas('user.customerInfo', function ($q) use ($customer_code) {
                $q->where('customer_code', 'like', '%' . $customer_code . '%');
            });
        }

        if ($request->route_code) {
            $route_code = $request->route_code;
            $creditnotes_query->whereHas('route', function ($q) use ($route_code) {
                $q->where('route_code', 'like', '%' . $route_code . '%');
            });
        }

        if ($request->route_name) {
            $route_name = $request->route_name;
            $creditnotes_query->whereHas('route', function ($q) use ($route_name) {
                $q->where('route_name', 'like', '%' . $route_name . '%');
            });
        }

        $creditnotes = $creditnotes_query->orderBy('id', 'desc')->get();

        $results = GetWorkFlowRuleObject('Credit Note');
        $approve_need_creditnotes = array();
        $approve_need_creditnotes_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_creditnotes[] = $raw['object']->raw_id;
                $approve_need_creditnotes_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $creditnotes_array = array();
        if (is_object($creditnotes)) {
            foreach ($creditnotes as $key => $creditnotes1) {
                if (in_array($creditnotes[$key]->id, $approve_need_creditnotes)) {
                    $creditnotes[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_creditnotes_detail_object_id[$creditnotes[$key]->id])) {
                        $creditnotes[$key]->objectid = $approve_need_creditnotes_detail_object_id[$creditnotes[$key]->id];
                    } else {
                        $creditnotes[$key]->objectid = '';
                    }
                } else {
                    $creditnotes[$key]->need_to_approve = 'no';
                    $creditnotes[$key]->objectid = '';
                }

                if ($creditnotes[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($creditnotes[$key]->id, $approve_need_creditnotes)) {
                    $creditnotes_array[] = $creditnotes[$key];
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
                if (isset($creditnotes_array[$offset])) {
                    $data_array[] = $creditnotes_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($creditnotes_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($creditnotes_array);
        } else {
            $data_array = $creditnotes_array;
        }
        return prepareResult(true, $data_array, [], "Credit notes listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit not", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Credit Note', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Credit Note',$request);
            }

            if (!empty($request->route_id)) {
                $route_id = $request->route_id;
            } else if (!empty($request->salesman_id)) {
                $route_id = getRouteBySalesman($request->salesman_id);
            }

            $creditnote = new CreditNote;
            $creditnote->customer_id = (!empty($request->customer_id)) ? $request->customer_id : null;
            $creditnote->invoice_id = (!empty($request->invoice_id)) ? $request->invoice_id : null;
            $creditnote->salesman_id = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $creditnote->credit_note_date = date('Y-m-d', strtotime($request->credit_note_date));
            if ($request->source == 1) {
                $creditnote->credit_note_number = $request->credit_note_number;
                $creditnote->trip_id = $request->trip_id;
            } else {
                $creditnote->credit_note_number = nextComingNumber('App\Model\CreditNote', 'credit_note', 'credit_note_number', $request->credit_note_number);
            }

            $creditnote->payment_term_id = $request->payment_term_id;
            $creditnote->route_id = $route_id;

            $creditnote->total_qty = $request->total_qty;
            $creditnote->total_gross = $request->total_gross;
            $creditnote->total_discount_amount = $request->total_discount_amount;
            $creditnote->total_net = $request->total_net;
            $creditnote->total_vat = $request->total_vat;
            $creditnote->total_excise = $request->total_excise;
            $creditnote->grand_total = $request->grand_total;
            if ($request->is_exchange == 1) {
                $creditnote->pending_credit = 0;
            } else {
                $creditnote->pending_credit = $request->grand_total;
            }
            $creditnote->current_stage = $current_stage;
            $creditnote->source = $request->source;
            $creditnote->reason = $request->reason;
            $creditnote->status = $status;
            $creditnote->approval_status = "Created";
            $creditnote->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $creditnote->is_exchange = (isset($request->is_exchange)) ? 1 : 0;
            $creditnote->exchange_number = (isset($request->exchange_number)) ? $request->exchange_number : null;
            $creditnote->save();

            if ($creditnote->source == 1 && $request->route_id) {
                $transactionheader = Transaction::where('trip_id', $request->trip_id)
                    ->where('salesman_id', $request->salesman_id)
                    ->first();

                if (is_object($transactionheader)) {
                    $transactionheader->trip_id = $request->trip_id;
                    $transactionheader->salesman_id = $creditnote->salesman_id;
                    $transactionheader->route_id = $request->route_id;
                    $transactionheader->transaction_type = 6;
                    $transactionheader->transaction_date = date('Y-m-d');
                    $transactionheader->transaction_time = date('Y-m-d H:i:s');
                    $transactionheader->reference = "Credit Note Mobile";
                    $transactionheader->save();
                }
            }

            if ($isActivate = checkWorkFlowRule('Credit Note', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Credit Note', $request, $creditnote);
            }


            if (is_array($request->items)) {
                foreach ($request->items as $key => $item) {

                    $conversation = getItemDetails2($item['item_id'], $item['item_uom_id'], $item['item_qty']);
                    //-----------Deduct and add
                    if ($request->return_type == 'badReturn') {
                        $routelocation = Storagelocation::where('route_id', $request->route_id)
                            ->where('loc_type', '2')
                            ->first();

                        if (is_object($routelocation)) {

                            $routestoragelocation_id = $routelocation->id;

                            $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                ->where('item_id', $item['item_id'])
                                ->first();

                            if (is_object($routelocation_detail)) {

                                $routelocation_detail->qty = ($routelocation_detail->qty + $conversation['Qty']);
                                $routelocation_detail->save();
                            } else {

                                $routestoragedetail = new StoragelocationDetail;
                                $routestoragedetail->storage_location_id = $routelocation->id;
                                $routestoragedetail->item_id = $item['item_id'];
                                $routestoragedetail->item_uom_id = $conversation['UOM'];
                                $routestoragedetail->qty = $conversation['Qty'];
                                $routestoragedetail->save();
                            }
                        } else {
                            return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    } else {

                        $routelocation = Storagelocation::where('route_id', $request->route_id)
                            ->where('loc_type', '1')
                            ->first();
                        if (is_object($routelocation)) {

                            $routestoragelocation_id = $routelocation->id;


                            $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                ->where('item_id', $item['item_id'])
                                ->first();


                            if (is_object($routelocation_detail)) {


                                $routelocation_detail->qty = ($routelocation_detail->qty + $conversation['Qty']);
                                $routelocation_detail->save();
                            } else {
                                $routestoragedetail = new StoragelocationDetail;
                                $routestoragedetail->storage_location_id = $routelocation->id;
                                $routestoragedetail->item_id = $item['item_id'];
                                $routestoragedetail->item_uom_id = $conversation['UOM'];
                                $routestoragedetail->qty = $conversation['Qty'];
                                $routestoragedetail->save();
                            }
                        } else {
                            return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    }

                    //-----------
                    $creditnoteDetail = new CreditNoteDetail;
                    $creditnoteDetail->credit_note_id = $creditnote->id;
                    $creditnoteDetail->item_id = $item['item_id'];
                    $creditnoteDetail->item_condition = $item['item_condition'];
                    $creditnoteDetail->item_uom_id = $item['item_uom_id'];
                    $creditnoteDetail->discount_id = $item['discount_id'];
                    $creditnoteDetail->is_free = $item['is_free'];
                    $creditnoteDetail->is_item_poi = $item['is_item_poi'];
                    $creditnoteDetail->promotion_id = $item['promotion_id'];
                    $creditnoteDetail->item_qty = $item['item_qty'];
                    $creditnoteDetail->item_price = $item['item_price'];
                    $creditnoteDetail->item_gross = $item['item_gross'];
                    $creditnoteDetail->item_discount_amount = $item['item_discount_amount'];
                    $creditnoteDetail->item_net = $item['item_net'];
                    $creditnoteDetail->item_vat = $item['item_vat'];
                    $creditnoteDetail->item_excise = $item['item_excise'];
                    $creditnoteDetail->item_grand_total = $item['item_grand_total'];
                    $creditnoteDetail->batch_number = $item['batch_number'];
                    $creditnoteDetail->reason = $item['reason'];
                    $creditnoteDetail->item_expiry_date = (!empty($item['item_expiry_date'])) ? date('Y-m-d', strtotime($item['item_expiry_date'])) : null;
                    $creditnoteDetail->save();

                    if ($creditnote->source == 1) {

                        $qty = 0;
                        $items = Item::where('id', $item['item_id'])
                            ->where('lower_unit_uom_id', $item['item_uom_id'])
                            ->first();

                        if (is_object($items)) {
                            $qty = $item['item_qty'];
                        } else {
                            $items = ItemMainPrice::where('item_id', $item['item_id'])
                                ->where('item_uom_id', $item['item_uom_id'])
                                ->first();

                            $qty = $items->item_upc * $item['item_qty'];
                        }
                        if ($creditnote->source == 1 && $request->route_id) {

                            $transactiondetails = TransactionDetail::where('transaction_id', $transactionheader->id)
                                ->where('item_id', $item['item_id'])
                                ->first();
                            if (is_object($transactiondetails)) {
                                if ($request->return_type == 'badReturn') {
                                    $transactiondetails->bad_retun_qty = $transactiondetails->bad_retun_qty + $qty;
                                } else if ($request->return_type == 'goodReturn') {
                                    $transactiondetails->return_qty = $transactiondetails->return_qty + $qty;
                                }

                                $transactiondetails->item_id = $item['item_id'];
                                $transactiondetails->save();
                            } else {
                                $transactiondetail = new TransactionDetail();
                                if ($request->return_type == 'badReturn') {
                                    $transactiondetail->bad_retun_qty = $qty;
                                } else if ($request->return_type == 'goodReturn') {
                                    $transactiondetail->return_qty = $qty;
                                }
                                $transactiondetail->transaction_id = $transactionheader->id;
                                $transactiondetail->item_id  = $item['item_id'];
                                $transactiondetail->load_qty = 0;
                                $transactiondetail->transfer_in_qty  = 0;
                                $transactiondetail->transfer_out_qty = 0;
                                $transactiondetail->request_qty = 0;
                                $transactiondetail->unload_qty = 0;
                                $transactiondetail->sales_qty = 0;
                                $transactiondetail->free_qty = 0;
                                $transactiondetail->opening_qty = $qty ?: 0;
                                $transactiondetail->closing_qty = 0;
                                $transactiondetail->status = 1;
                                $transactiondetail->save();
                            }
                        }
                    }
                }
            }

            /* if($invoice)
            {
                $getOrderType = OrderType::find($request->order_type_id);
                preg_match_all('!\d+!', $getOrderType->next_available_code, $newNumber);
                $formattedNumber = sprintf("%0".strlen($getOrderType->end_range)."d", ($newNumber[0][0]+1));
                $actualNumber =  $getOrderType->prefix_code.$formattedNumber;
                $getOrderType->next_available_code = $actualNumber;
                $getOrderType->save();
            } */

            if (is_object($creditnote) && $creditnote->source == 1) {
                $user = User::find($request->user()->id);
                if (is_object($user)) {
                    $salesmanInfo = $user->salesmanInfo;
                    $smr = SalesmanNumberRange::where('salesman_id', $salesmanInfo->id)->first();
                    $smr->credit_note_from = $request->credit_note_number;
                    $smr->save();
                }
            }

            create_action_history("CreditNote", $creditnote->id, auth()->user()->id, "create", "Credit Note created by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            \DB::commit();

            if ($request->source != 1) {
                updateNextComingNumber('App\Model\DebitNote', 'debit_note');
            }
            
            // send to odoo
            $this->creditNotePostOdoo($creditnote->uuid);

            return prepareResult(true, $creditnote, [], "Credit note added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /*
    *   Direct post odoo data
    */
    public function creditNotePostOdoo($uuid)
    {
        // Get Data edit api
        $creditNotes = $this->editData($uuid);

        $response = Curl::to('http://rfctest.dyndns.org:11214/api/create/creditnote')
            ->withData(array('params' => $creditNotes))
            ->asJson(true)
            ->post();

        if (isset($response['result'])) {
            $data = json_decode($response['result']);
            if ($data->response[0]->state == "success") {
                $creditNotes->oddo_credit_id = $data->response[0]->inv_id;
            } else {
                $creditNotes->odoo_failed_response = $response['error'];
            }
        }

        if (isset($response['error'])) {
            $creditNotes->odoo_failed_response = $response['error'];
        }

        $creditNotes->save();

        if (!empty($creditNotes->oddo_credit_id)) {
            return prepareResult(true, $creditNotes, [], "Credit Note posted sucessfully", $this->success);
        }

        return prepareResult(false, $creditNotes, [], "Credit note not posted", $this->unprocessableEntity);
    }

    private function editData($uuid)
    {
        $creditnote = CreditNote::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerinfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmaninfo:id,user_id,salesman_code',
                'invoice',
                'creditNoteDetails',
                'creditNoteDetails.item:id,item_name,item_code',
                'creditNoteDetails.itemUom:id,name,code',
                'lob',
                'route:id,route_name,route_code'
            )
            ->where('uuid', $uuid)
            ->first();
        return $creditnote;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating credit notes.", $this->unauthorized);
        }

        $creditnote = $this->editData($uuid);

        if (!is_object($creditnote)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $creditnote, [], "Credit Note Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit notes.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Credit Note', 'edit', $current_organisation_id)) {
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Credit Note',$request);
            }

            if (!empty($request->route_id)) {
                $route_id = $request->route_id;
            } else if (!empty($request->salesman_id)) {
                $route_id = getRouteBySalesman($request->salesman_id);
            }

            $creditnote = CreditNote::where('uuid', $uuid)->first();

            //Delete old record
            CreditNoteDetail::where('credit_note_id', $creditnote->id)->delete();

            $creditnote->customer_id = (!empty($request->customer_id)) ? $request->customer_id : null;
            $creditnote->invoice_id = (!empty($request->invoice_id)) ? $request->invoice_id : null;
            $creditnote->salesman_id = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $creditnote->credit_note_date = date('Y-m-d', strtotime($request->credit_note_date));
            $creditnote->credit_note_number = $request->credit_note_number;

            $creditnote->payment_term_id = $request->payment_term_id;
            $creditnote->route_id = $route_id;

            $creditnote->total_qty = $request->total_qty;
            $creditnote->total_gross = $request->total_gross;
            $creditnote->total_discount_amount = $request->total_discount_amount;
            $creditnote->total_net = $request->total_net;
            $creditnote->total_vat = $request->total_vat;
            $creditnote->total_excise = $request->total_excise;
            $creditnote->grand_total = $request->grand_total;
            $creditnote->pending_credit = $request->grand_total;
            $creditnote->current_stage = $current_stage;
            $creditnote->source = $request->source;
            $creditnote->reason = $request->reason;
            $creditnote->status = $status;
            $creditnote->approval_status = "Updated";
            $creditnote->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            // $creditnote->is_exchange         = (!empty($request->is_exchange)) ? 1 : 0;
            // $creditnote->exchange_number     = (!empty($request->exchange_number)) ? $request->exchange_number : null;
            $creditnote->save();

            if ($isActivate = checkWorkFlowRule('Credit Note', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Credit Note', $request, $creditnote);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $creditnoteDetail = new CreditNoteDetail;
                    $creditnoteDetail->credit_note_id = $creditnote->id;
                    $creditnoteDetail->item_id = $item['item_id'];
                    $creditnoteDetail->item_condition = $item['item_condition'];
                    $creditnoteDetail->item_uom_id = $item['item_uom_id'];
                    $creditnoteDetail->discount_id = $item['discount_id'];
                    $creditnoteDetail->is_free = $item['is_free'];
                    $creditnoteDetail->is_item_poi = $item['is_item_poi'];
                    $creditnoteDetail->promotion_id = $item['promotion_id'];
                    $creditnoteDetail->item_qty = $item['item_qty'];
                    $creditnoteDetail->item_price = $item['item_price'];
                    $creditnoteDetail->item_gross = $item['item_gross'];
                    $creditnoteDetail->item_discount_amount = $item['item_discount_amount'];
                    $creditnoteDetail->item_net = $item['item_net'];
                    $creditnoteDetail->item_vat = $item['item_vat'];
                    $creditnoteDetail->item_excise = $item['item_excise'];
                    $creditnoteDetail->item_grand_total = $item['item_grand_total'];
                    $creditnoteDetail->batch_number = $item['batch_number'];
                    $creditnoteDetail->reason = $item['reason'];
                    $creditnoteDetail->item_expiry_date = (!empty($item['item_expiry_date'])) ? date('Y-m-d', strtotime($item['item_expiry_date'])) : null;
                    $creditnoteDetail->save();
                }
            }

            create_action_history("CreditNote", $creditnote->id, auth()->user()->id, "update", "Credit Note updated by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            \DB::commit();

            $creditnote->getSaveData();

            return prepareResult(true, $creditnote, [], "Credit note updated successfully", $this->created);
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
     * @param int $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating credit note.", $this->unauthorized);
        }

        $creditnote = CreditNote::where('uuid', $uuid)
            ->first();

        if (is_object($creditnote)) {
            $invoiceId = $creditnote->id;
            $creditnote->delete();
            if ($creditnote) {
                CreditNoteDetail::where('credit_note_id', $invoiceId)->delete();
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
     * @param \Illuminate\Http\Request $request
     * @param array int  $uuid
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
        $uuids = $request->credit_note_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                CreditNote::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $creditnote = $this->index();
            return prepareResult(true, $creditnote, [], "Credit note status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $creditnote = CreditNote::where('uuid', $uuid)
                    ->first();
                $creditnoteId = $creditnote->id;
                $creditnote->delete();
                if ($creditnote) {
                    CreditNoteDetail::where('credit_note_id', $creditnoteId)->delete();
                }
            }
            $creditnote = $this->index();
            return prepareResult(true, $creditnote, [], "Credit note deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'customer_id' => 'required|integer|exists:users,id',
                'credit_note_date' => 'required|date',
                // 'payment_term_id' => 'required|integer|exists:payment_terms,id',
                'credit_note_number' => 'required',
                'total_qty' => 'required',
                'total_vat' => 'required',
                'total_net' => 'required',
                'total_excise' => 'required',
                'grand_total' => 'required',

            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'credit_note_ids' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function getcustomerinvoice($user_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$user_id) {
            return prepareResult(false, [], [], "Error while validating customer invoice.", $this->unauthorized);
        }
        $invoices = Invoice::where('customer_id', $user_id)
            ->orderBy('id', 'desc')
            ->get();
        return prepareResult(true, $invoices, [], "Invoices listing", $this->success);
    }

    public function getinvoiceitem($invoice_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$invoice_id) {
            return prepareResult(false, [], [], "Error while validating invoice item.", $this->unauthorized);
        }
        $invoicedetail = InvoiceDetail::with(
            'item:id,item_name,item_code',
            'itemUom:id,name,code'
        )
            ->where('invoice_id', $invoice_id)
            ->orderBy('id', 'desc')
            ->get();
        return prepareResult(true, $invoicedetail, [], "Invoice detail listing", $this->success);
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
            'creditnote_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate delivery import", $this->unauthorized);
        }

        Excel::import(new CreditnoteImport, request()->file('creditnote_file'));
        return prepareResult(true, [], [], "Credit note successfully imported", $this->success);
    }

    /**
     * Display load quenty count listing of the resource. based on the current and route id
     *
     * @return \Illuminate\Http\Response
     */
    public function getLoadquantity(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $ListingFee = Transaction::select('transactions.id', 'transactions.route_id', 'transactions.transaction_date')
            ->with(['loadquantity'])
            ->where('route_id', $request->route_id)
            ->whereDate('transaction_date', Carbon::today())
            ->orderBy('transactions.id', 'desc')
            ->get();

        $ListingFee_array = array();
        if (is_object($ListingFee)) {
            foreach ($ListingFee as $key => $ListingFee1) {
                $ListingFee_array[] = $ListingFee[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($ListingFee_array[$offset])) {
                    $data_array[] = $ListingFee_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($ListingFee_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($ListingFee_array);
        } else {
            $data_array = $ListingFee_array;
        }

        return prepareResult(true, $data_array, [], "listing fee details", $this->success, $pagination);
    }
}
