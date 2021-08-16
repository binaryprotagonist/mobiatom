<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\DebitNote;
use App\Model\DebitNoteDetail;
use App\Model\DebitNoteListingfeeShelfrentRebatediscountDetail;
use App\Model\ListingFee;
use App\Model\ShelfRent;
use App\Model\RebateDiscount;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowRuleApprovalUser;

class DebitNoteController extends Controller
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

        $debitnotes_query = DebitNote::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customer:id,firstname,lastname',
                'customer.customerinfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmaninfo:id,user_id,salesman_code',
                'invoice',
                'debitNoteDetails',
                'debitNoteDetails.item:id,item_name,item_code',
                'debitNoteDetails.itemUom:id,name,code',
                'debitNoteListingfeeShelfrentRebatediscountDetails',
                'lob'
            );

        if ($request->date) {
            $debitnotes_query->whereDate('debit_note_date', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->debit_note_number) {
            $debitnotes_query->where('debit_note_number', 'like', '%' . $request->debit_note_number . '%');
        }

        if ($request->current_stage) {
            $debitnotes_query->where('current_stage', 'like', '%' . $request->current_stage . '%');
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $debitnotes_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $debitnotes_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $debitnotes_query->whereHas('user.customerInfo', function ($q) use ($customer_code) {
                $q->where('customer_code', 'like', '%' . $customer_code . '%');
            });
        }


        $debitnotes = $debitnotes_query->orderBy('id', 'desc')->get();

        $results = GetWorkFlowRuleObject('Debit Note');
        $approve_need_debitnotes = array();
        $approve_need_debitnotes_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_debitnotes[] = $raw['object']->raw_id;
                $approve_need_debitnotes_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $debitnotes_array = array();
        if (is_object($debitnotes)) {
            foreach ($debitnotes as $key => $debitnotes1) {
                if (in_array($debitnotes[$key]->id, $approve_need_debitnotes)) {
                    $debitnotes[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_debitnotes_detail_object_id[$debitnotes[$key]->id])) {
                        $debitnotes[$key]->objectid = $approve_need_debitnotes_detail_object_id[$debitnotes[$key]->id];
                    } else {
                        $debitnotes[$key]->objectid = '';
                    }
                } else {
                    $debitnotes[$key]->need_to_approve = 'no';
                    $debitnotes[$key]->objectid = '';
                }

                if ($debitnotes[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($debitnotes[$key]->id, $approve_need_debitnotes)) {
                    $debitnotes_array[] = $debitnotes[$key];
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
                if (isset($debitnotes_array[$offset])) {
                    $data_array[] = $debitnotes_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($debitnotes_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($debitnotes_array);
        } else {
            $data_array = $debitnotes_array;
        }
        return prepareResult(true, $data_array, [], "Debit notes listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!isset($request->is_debit_note) || $request->is_debit_note ==  null || $request->is_debit_note ==  '') {
            $is_debit_note = 1;
        } else {
            if ($request->is_debit_note == 1   || $request->is_debit_note ==  0) {
                $is_debit_note  = $request->is_debit_note;
            } else {
                return prepareResult(false, [], "is_debit_note filed is should be 0 or 1", "Error while validating debit not", $this->unprocessableEntity);
            }
        }

        if ($is_debit_note == 0) {

            if (!isset($request->date) || $request->date == '') {
                return prepareResult(false, [], "date filed is require", "Error while validating debit not", $this->unprocessableEntity);
            }
            $given_date =   date('Y-m-d', strtotime($request->date));
            $last_Date_of_Previouse_Month =    \Carbon\Carbon::parse($given_date)->subMonth()->endOfMonth()->toDateString();

            $debitnote_list_shelf_rebate = DebitNoteListingfeeShelfrentRebatediscountDetail::where('date', $last_Date_of_Previouse_Month)
                ->where('customer_id', $request->customer_id)
                ->where('type', $request->type)->first();
            if (is_object($debitnote_list_shelf_rebate)) {
                return prepareResult(false, [], [], "Record already present for given date of previous month, choose any other month date and type", $this->unauthorized);
            }
        }

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $input = $request->json()->all();

        $validate = $this->validations($input, "add", $request, $is_debit_note);
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating debit not", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Debit Note', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
            }

            $debitnote = new DebitNote;
            $debitnote->customer_id = (!empty($request->customer_id)) ? $request->customer_id : null;
            // $debitnote->invoice_id = (!empty($request->invoice_id)) ? $request->invoice_id : null;
            $debitnote->salesman_id = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $debitnote->reason = (!empty($request->reason)) ? $request->reason : null;
            $debitnote->debit_note_date = date('Y-m-d', strtotime($request->debit_note_date));
            if ($request->source == 1) {
                $debitnote->debit_note_number = $request->debit_note_number;
            } else {
                $debitnote->debit_note_number = nextComingNumber('App\Model\DebitNote', 'debit_note', 'debit_note_number', $request->debit_note_number);
            }
            // $debitnote->payment_term_id = $request->payment_term_id;
            $debitnote->total_qty = $request->total_qty;
            $debitnote->total_gross = $request->total_gross;
            $debitnote->total_discount_amount = $request->total_discount_amount;
            $debitnote->total_net = $request->total_net;
            $debitnote->total_vat = $request->total_vat;
            $debitnote->total_excise = $request->total_excise;
            $debitnote->grand_total = $request->grand_total;
            $debitnote->pending_credit = $request->grand_total;
            $debitnote->debit_note_comment = $request->debit_note_comment;
            $debitnote->source = $request->source;
            $debitnote->status = $request->status;
            $debitnote->approval_status = "Created";
            $debitnote->is_debit_note = $is_debit_note;
            $debitnote->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $debitnote->save();

            if ($isActivate = checkWorkFlowRule('Debit Note', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Debit Note', $request, $debitnote);
            }

            if ($is_debit_note == 1) {
                if (is_array($request->items)) {
                    foreach ($request->items as $item) {
                        $debitnoteDetail = new DebitNoteDetail;
                        $debitnoteDetail->debit_note_id = $debitnote->id;
                        $debitnoteDetail->item_id = $item['item_id'];
                        $debitnoteDetail->item_condition = $item['item_condition'];
                        $debitnoteDetail->item_uom_id = $item['item_uom_id'];
                        $debitnoteDetail->discount_id = $item['discount_id'];
                        $debitnoteDetail->is_free = $item['is_free'];
                        $debitnoteDetail->is_item_poi = $item['is_item_poi'];
                        $debitnoteDetail->promotion_id = $item['promotion_id'];
                        $debitnoteDetail->item_qty = $item['item_qty'];
                        $debitnoteDetail->item_price = $item['item_price'];
                        $debitnoteDetail->item_gross = $item['item_gross'];
                        $debitnoteDetail->item_discount_amount = $item['item_discount_amount'];
                        $debitnoteDetail->item_net = $item['item_net'];
                        $debitnoteDetail->item_vat = $item['item_vat'];
                        $debitnoteDetail->item_excise = $item['item_excise'];
                        $debitnoteDetail->item_grand_total = $item['item_grand_total'];
                        $debitnoteDetail->batch_number = $item['batch_number'];
                        $debitnoteDetail->reason = $item['reason'];
                        $debitnoteDetail->save();
                    }
                }
            }

            if ($is_debit_note == 0) {
                $amount = 0;
                $item_name = 'test'; // test is dummy data
                if ($request->type == 'listing_fees') {
                    $list_fees = ListingFee::where('to_date', $last_Date_of_Previouse_Month)->where('user_id', $request->customer_id)->first();
                    $amount =  $list_fees->amount;
                    $item_name = 'Listing fees debit note';
                }
                if ($request->type == 'shelf_rent') {
                    $sales_price = ShelfRent::where('to_date', $last_Date_of_Previouse_Month)->where('user_id', $request->customer_id)->first();
                    $amount =  $sales_price->amount;
                    $item_name = 'Shelf Rent debit note';
                }
                if ($request->type == 'rebate_discount') {
                    $rebate_discount = RebateDiscount::where('to_date', $last_Date_of_Previouse_Month)->where('user_id', $request->customer_id)->first();
                    $amount =  $rebate_discount->amount;
                    $item_name = 'Rebate Discount debit note';
                }

                $weight_amount = ($amount / 100) * 5;
                $total_amount = $weight_amount + $amount;

                $debitnote_list_shelf_rebate = new DebitNoteListingfeeShelfrentRebatediscountDetail;
                $debitnote_list_shelf_rebate->debit_note_id = $debitnote->id;
                $debitnote_list_shelf_rebate->customer_id   = $request->customer_id;
                $debitnote_list_shelf_rebate->date          = $last_Date_of_Previouse_Month;
                $debitnote_list_shelf_rebate->amount        = $amount;
                $debitnote_list_shelf_rebate->item_name     = $item_name;
                $debitnote_list_shelf_rebate->type          = $request->type;
                $debitnote_list_shelf_rebate->weight_amount = $weight_amount;
                $debitnote_list_shelf_rebate->total_amount  = $total_amount;
                $debitnote_list_shelf_rebate->save();
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
            \DB::commit();

            if ($request->source != 1) {
                updateNextComingNumber('App\Model\DebitNote', 'debit_note');
            }

            $debitnote->getSaveData();

            return prepareResult(true, $debitnote, [], "Debit note added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating debit notes.", $this->unauthorized);
        }

        $debitnote = DebitNote::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customer:id,firstname,lastname',
                'invoice',
                'salesman:id,firstname,lastname',
                'debitNoteDetails',
                'debitNoteDetails.item:id,item_name,item_code,lower_unit_uom_id',
                'debitNoteDetails.itemUom:id,name,code',
                'debitNoteDetails.item.itemMainPrice',
                'debitNoteDetails.item.itemMainPrice.itemUom:id,name',
                'debitNoteDetails.item.itemUomLowerUnit:id,name',
                'debitNoteListingfeeShelfrentRebatediscountDetails',
                'lob'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($debitnote)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $debitnote, [], "Debit Note Edit", $this->success);
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

        /* if (!isset($request->date) || $request->date == '') {
            return prepareResult(false, [], "data filed is require", "Error while validating debit not", $this->unprocessableEntity);
        } */

        if (!isset($request->is_debit_note) || $request->is_debit_note ==  null || $request->is_debit_note ==  '') {
            $is_debit_note = 1;
        } else {
            if ($request->is_debit_note == 1   || $request->is_debit_note ==  0) {
                $is_debit_note  = $request->is_debit_note;
            } else {
                return prepareResult(false, [], "is_debit_note filed is should be 0 or 1", "Error while validating debit not", $this->unprocessableEntity);
            }
        }


        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add", $request, $is_debit_note);
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
            if ($isActivate = checkWorkFlowRule('Debit Note', 'create', $current_organisation_id)) {
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Debit Note',$request);
            }

            $debitnote = DebitNote::where('uuid', $uuid)->first();

            if ($is_debit_note == 0) {


                if (!isset($request->date) || $request->date == '') {
                    return prepareResult(false, [], "date filed is require", "Error while validating debit not", $this->unprocessableEntity);
                }

                $given_date =   date('Y-m-d', strtotime($request->date));
                $last_Date_of_Previouse_Month =    \Carbon\Carbon::parse($given_date)->subMonth()->endOfMonth()->toDateString();

                $debitnote_list_shelf_rebate = DebitNoteListingfeeShelfrentRebatediscountDetail::where('date', $last_Date_of_Previouse_Month)
                    ->where('customer_id', $request->customer_id)
                    ->where('type', $request->type)
                    ->where('debit_note_id', '!=', $debitnote->id)->first();
                if (is_object($debitnote_list_shelf_rebate)) {
                    return prepareResult(false, [], [], "Record already present for given date of previous month, choose any other month date and type", $this->unauthorized);
                }
            }
            if ($is_debit_note == 1) {
                //Delete old record
                DebitNoteDetail::where('debit_note_id', $debitnote->id)->delete();

                $debitnote_list_shelf_rebate = DebitNoteListingfeeShelfrentRebatediscountDetail::where('debit_note_id', $debitnote->id)->get();
                if (is_object($debitnote_list_shelf_rebate)) {
                    DebitNoteListingfeeShelfrentRebatediscountDetail::where('debit_note_id', $debitnote->id)->delete();
                }
            }

            if ($is_debit_note == 0) {

                $debit_note_detail =  DebitNoteDetail::where('debit_note_id', $debitnote->id)->get();
                if (is_object($debit_note_detail)) {
                    DebitNoteDetail::where('debit_note_id', $debitnote->id)->delete();
                }

                DebitNoteListingfeeShelfrentRebatediscountDetail::where('debit_note_id', $debitnote->id)
                    ->where('date', $last_Date_of_Previouse_Month)
                    ->where('customer_id', $request->customer_id)
                    ->where('type', $request->type)
                    ->delete();
            }
            $debitnote->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            // $debitnote->invoice_id        = (!empty($request->invoice_id)) ? $request->invoice_id : null;
            $debitnote->salesman_id          = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $debitnote->reason = (!empty($request->reason)) ? $request->reason : null;
            $debitnote->debit_note_date = date('Y-m-d', strtotime($request->debit_note_date));
            $debitnote->debit_note_number = $request->debit_note_number;

            // $debitnote->payment_term_id = $request->payment_term_id;

            $debitnote->total_qty = $request->total_qty;
            $debitnote->total_gross = $request->total_gross;
            $debitnote->total_discount_amount   = $request->total_discount_amount;
            $debitnote->total_net = $request->total_net;
            $debitnote->total_vat = $request->total_vat;
            $debitnote->total_excise = $request->total_excise;
            $debitnote->grand_total = $request->grand_total;
            $debitnote->pending_credit = $request->grand_total;
            $debitnote->debit_note_comment = $request->debit_note_comment;
            $debitnote->source = $request->source;
            $debitnote->status = $request->status;
            $debitnote->approval_status = "Updated";
            $debitnote->is_debit_note = $is_debit_note;
            $debitnote->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $debitnote->save();

            if ($isActivate = checkWorkFlowRule('Debit Note', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Debit Note', $request, $debitnote);
            }

            if ($is_debit_note == 1) {
                if (is_array($request->items)) {
                    foreach ($request->items as $item) {
                        $debitnoteDetail = new DebitNoteDetail;
                        $debitnoteDetail->debit_note_id      = $debitnote->id;
                        $debitnoteDetail->item_id       = $item['item_id'];
                        $debitnoteDetail->item_condition   = $item['item_condition'];
                        $debitnoteDetail->item_uom_id   = $item['item_uom_id'];
                        $debitnoteDetail->discount_id       = $item['discount_id'];
                        $debitnoteDetail->is_free       = $item['is_free'];
                        $debitnoteDetail->is_item_poi   = $item['is_item_poi'];
                        $debitnoteDetail->promotion_id  = $item['promotion_id'];
                        $debitnoteDetail->item_qty      = $item['item_qty'];
                        $debitnoteDetail->item_price    = $item['item_price'];
                        $debitnoteDetail->item_gross    = $item['item_gross'];
                        $debitnoteDetail->item_discount_amount = $item['item_discount_amount'];
                        $debitnoteDetail->item_net      = $item['item_net'];
                        $debitnoteDetail->item_vat      = $item['item_vat'];
                        $debitnoteDetail->item_excise   = $item['item_excise'];
                        $debitnoteDetail->item_grand_total = $item['item_grand_total'];
                        $debitnoteDetail->batch_number = $item['batch_number'];
                        $debitnoteDetail->reason = $item['reason'];
                        $debitnoteDetail->save();
                    }
                }
            }

            if ($is_debit_note == 0) {
                $amount = 0;
                $item_name = 'test'; // test is dummy data
                if ($request->type == 'listing_fees') {
                    $list_fees = ListingFee::where('to_date', $last_Date_of_Previouse_Month)->where('user_id', $request->customer_id)->first();
                    $amount =  $list_fees->amount;
                    $item_name = 'Listing fees debit note';
                }
                if ($request->type == 'shelf_rent') {
                    $sales_price = ShelfRent::where('to_date', $last_Date_of_Previouse_Month)->where('user_id', $request->customer_id)->first();
                    $amount =  $sales_price->amount;
                    $item_name = 'Shelf Rent debit note';
                }
                if ($request->type == 'rebate_discount') {
                    $rebate_discount = RebateDiscount::where('to_date', $last_Date_of_Previouse_Month)->where('user_id', $request->customer_id)->first();
                    $amount =  $rebate_discount->amount;
                    $item_name = 'Rebate Discount debit note';
                }

                $weight_amount = ($amount / 100) * 5;
                $total_amount = $weight_amount + $amount;


                $debitnote_list_shelf_rebate = new DebitNoteListingfeeShelfrentRebatediscountDetail;
                $debitnote_list_shelf_rebate->debit_note_id = $debitnote->id;
                $debitnote_list_shelf_rebate->customer_id   = $request->customer_id;
                $debitnote_list_shelf_rebate->date          = $last_Date_of_Previouse_Month;
                $debitnote_list_shelf_rebate->amount        = $amount;
                $debitnote_list_shelf_rebate->item_name     = $item_name;
                $debitnote_list_shelf_rebate->type          = $request->type;
                $debitnote_list_shelf_rebate->weight_amount = $weight_amount;
                $debitnote_list_shelf_rebate->total_amount  = $total_amount;
                $debitnote_list_shelf_rebate->save();
            }

            \DB::commit();

            $debitnote->getSaveData();

            return prepareResult(true, $debitnote, [], "Debit note updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating credit note.", $this->unauthorized);
        }

        $debitnote = DebitNote::where('uuid', $uuid)
            ->first();

        if (is_object($debitnote)) {
            $DebitId = $debitnote->id;
            if ($debitnote) {
                $debitnote->delete();
                $debit_note_detail =  DebitNoteDetail::where('debit_note_id', $debitnote->id)->get();
                if (is_object($debit_note_detail)) {
                    DebitNoteDetail::where('debit_note_id', $debitnote->id)->delete();
                }

                $debitnote_list_shelf_rebate = DebitNoteListingfeeShelfrentRebatediscountDetail::where('debit_note_id', $debitnote->id)->get();
                if (is_object($debitnote_list_shelf_rebate)) {
                    DebitNoteListingfeeShelfrentRebatediscountDetail::where('debit_note_id', $debitnote->id)->delete();
                }
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

        $validate = $this->validations($input, "bulk-action", $is_debit_note = 3);

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->debit_note_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                DebitNote::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $debitnote = $this->index();
            return prepareResult(true, $debitnote, [], "Debit note status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $debitnote = DebitNote::where('uuid', $uuid)
                    ->first();
                $debitId = $debitnote->id;
                $debitnote->delete();
                if ($debitnote) {
                    DebitNoteDetail::where('debit_note_id', $debitId)->delete();
                }
            }
            $debitnote = $this->index();
            return prepareResult(true, $debitnote, [], "Debit note deleted success", $this->success);
        }
    }
    private function validations($input, $type, $request, $is_debit_note)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {

            if ($is_debit_note == 1) {
                $rules_1 = [
                    'customer_id' => 'required|integer|exists:users,id',
                    'debit_note_date' => 'required|date',
                    // 'payment_term_id' => 'required|integer|exists:payment_terms,id',
                    'debit_note_number' => 'required',
                    'total_qty' => 'required',
                    'total_vat' => 'required',
                    'total_net' => 'required',
                    'total_excise' => 'required',
                    'grand_total' => 'required',
                    //'lob_id' => 'required',
                ];
                $rules = $rules_1;
            }

            if (isset($is_debit_note) && $is_debit_note == 0) {
                $rules_2 = [
                    'customer_id' => 'required|integer|exists:users,id',
                    'is_debit_note' => 'required',
                    'date' => 'required',
                    'type' => 'required',
                    // 'lob_id' => 'required',
                ];
                $rules = $rules_2;
            }
            $validator = \Validator::make($input, $rules);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'debit_note_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request, $obj)
    {
        $createObj = new WorkFlowObject();
        $createObj->work_flow_rule_id = $work_flow_rule_id;
        $createObj->module_name = $module_name;
        $createObj->raw_id = $obj->raw_id;
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
}
