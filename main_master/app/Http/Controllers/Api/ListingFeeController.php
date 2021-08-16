<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CodeSetting;
use App\Model\CollectionDetails;
use App\Model\CustomerInfo;
use App\Model\DebitNote;
use App\Model\DebitNoteListingfeeShelfrentRebatediscountDetail;
use App\Model\Invoice;
use Illuminate\Http\Request;
use App\Model\ListingFee;
use App\Model\RebateDiscount;
use App\Model\ShelfRent;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ListingFeeController extends Controller
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

        $ListingFee_query = ListingFee::with(
            'user:id,firstname,lastname',
            'user.customerInfo:id,user_id,customer_code',
            'lob'
        );

        if ($request->customer_name) {
            $customer_name = $request->customer_name;
            $exploded_name = explode(" ", $customer_name);
            if (count($exploded_name) < 2) {
                $ListingFee_query->whereHas('user', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $ListingFee_query->whereHas('user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $ListingFee_query->where('customer_code', 'like', '%' . $request->customer_code . '%');
        }

        if ($request->agreement_code) {
            $ListingFee_query->where('agreement_id', 'like', '%' . $request->agreement_code . '%');
        }

        if ($request->name) {
            $ListingFee_query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->start_date) {
            $ListingFee_query->whereDate('from_date', $request->start_date);
        }

        if ($request->end_date) {
            $ListingFee_query->whereDate('from_to', $request->end_date);
        }

        $ListingFee = $ListingFee_query->orderBy('id', 'desc')
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating listing fee request", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $listing_fee = new ListingFee;
            $listing_fee->agreement_id       = $request->agreement_id;
            $listing_fee->customer_code      = $request->customer_code;
            $listing_fee->user_id            = $request->user_id;
            $listing_fee->name               = $request->name;
            $listing_fee->amount             = $request->amount;
            $listing_fee->from_date          = date('Y-m-d', strtotime($request->from_date));
            $listing_fee->to_date            = date('Y-m-d', strtotime($request->to_date));
            $listing_fee->lob_id             = (!empty($request->lob_id)) ? $request->lob_id : null;
            $listing_fee->status             = (!empty($request->status)) ? $request->status : 1;
            $listing_fee->save();

            \DB::commit();
            $listing_fee->getSaveData();
            return prepareResult(true, $listing_fee, [], "listing fee added successfully", $this->created);
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

        $listing_fee = ListingFee::where('uuid', $uuid)
            ->with(
                'user:id,firstname,lastname',
                'user.customerInfo:id,user_id,customer_code',
                'lob'
            )
            ->first();

        if (!is_object($listing_fee)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $listing_fee, [], "listing fee Edit", $this->success);
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

        if (!$uuid) {
            return prepareResult(false, [], [], "select any one listing fee record id", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating listing fee", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $listing_fee = ListingFee::where('uuid', $uuid)->first();
            if (is_object($listing_fee)) {

                $listing_fee->agreement_id       = $request->agreement_id;
                $listing_fee->customer_code      = getCustomerCode($request->user_id);
                $listing_fee->user_id            = $request->user_id;
                $listing_fee->name               = $request->name;
                $listing_fee->amount             = $request->amount;
                $listing_fee->from_date          = date('Y-m-d', strtotime($request->from_date));
                $listing_fee->to_date            = date('Y-m-d', strtotime($request->to_date));
                $listing_fee->lob_id             = (!empty($request->lob_id)) ? $request->lob_id : null;
                $listing_fee->status             = (!empty($request->status)) ? $request->status : 1;
                $listing_fee->save();
            } else {
                return prepareResult(true, [], [], "Record not found.", $this->not_found);
            }

            \DB::commit();
            $listing_fee->getSaveData();
            return prepareResult(true, $listing_fee, [], "listing fee updated successfully", $this->created);
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
            return prepareResult(false, [], [], "select any one listing fee record id", $this->unauthorized);
        }
        $listing_fee = ListingFee::where('uuid', $uuid)->first();

        if (is_object($listing_fee)) {
            $listing_fee->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }


    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'agreement_id' => 'required',
                // 'customer_code' => 'required',
                'user_id' => 'required',
                'name' => 'required',
                'amount' => 'required|numeric',
                'from_date' => 'required|date',
                'to_date' => 'required|date',
                // 'lob_id' => 'required',

            ]);
        }

        if ($type == "debite_note_document") {
            $validator = \Validator::make($input, [
                'year' => 'required',
                'month' => 'required',
                // 'user_id' => 'required',  
                'type' => 'required',
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
    public function getListingFeesCustomer(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->year || !$request->month) {
            return prepareResult(false, [], [], "select month and year", $this->unauthorized);
        }

        $ts = strtotime($request->month . '' . $request->year);

        $start_date = $request->year . '-' . $request->month . '-' . "01";
        // $start_date = "01-" . $request->month . '-' . $request->year;
        // $end_date = date('t', $ts) . '-' . $request->month . '-' . $request->year;
        $end_date = $request->year . '-' . $request->month . '-' .  date('t', $ts);

        $ListingFee = ListingFee::with(
            'user:id,firstname,lastname,email'
        )
            ->where('from_date', '<=', $start_date)
            ->where('to_date', '>=', $end_date)
            ->orderBy('id', 'desc')
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

    public function cronListShelfRebet()
    {
        $firstDayofPreviousMonth = Carbon::now()->startOfMonth()->subMonth()->toDateString();
        $lastDayofPreviousMonth = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        $customer_infos = CustomerInfo::select('user_id')
            ->get()
            ->pluck('user_id')
            ->toArray();

        $listing_fee = ListingFee::where('from_date', '<=', $firstDayofPreviousMonth)
            ->where('to_date', '>=', $lastDayofPreviousMonth)
            ->whereIn('user_id', $customer_infos)
            ->get();

        $this->saveDebitNote($listing_fee, "listing_fees", $lastDayofPreviousMonth, $firstDayofPreviousMonth);

        $shelf_rent = ShelfRent::where('from_date', '<=', $firstDayofPreviousMonth)
            ->where('to_date', '>=', $lastDayofPreviousMonth)
            ->whereIn('user_id', $customer_infos)
            ->get();

        $this->saveDebitNote($shelf_rent, "shelf_rent", $lastDayofPreviousMonth, $firstDayofPreviousMonth);

        // $this->saveDebitNote($shelf_rent, "rebate_discount", $lastDayofPreviousMonth, $firstDayofPreviousMonth);

        // $invoice = Invoice::whereBetween('invoice_date', [$firstDayofPreviousMonth, $lastDayofPreviousMonth])->get();
    }

    private function saveDebitNote(
        $obj,
        $type,
        $lastDayofPreviousMonth,
        $firstDayofPreviousMonth
    ) {
        $status_val = 0;

        $obj->each(function ($o, $key) use ($type, $lastDayofPreviousMonth, $firstDayofPreviousMonth, &$status_val) {
            $debit_note = DebitNote::where('debit_note_date', $lastDayofPreviousMonth)
                ->where('customer_id', $o->user_id)
                ->where('debit_note_type', $type)
                ->first();

            if (!is_object($debit_note)) {

                $lastDate = date('Y-m-d', strtotime('+1 days', strtotime($lastDayofPreviousMonth)));
                $user = User::find($o->user_id);
                $customerInfo = $user->customerInfo;

                $sum_invoice = 0;
                if ($type == "rebate_discount") {
                    $invoices = Invoice::select([DB::raw('group_concat(grand_total) as total')])
                        ->whereBetween('invoice_date', [$firstDayofPreviousMonth, $lastDate])
                        ->where('customer_id', $o->user_id)
                        ->where('organisation_id', $user->organisation_id)
                        ->first();

                    $invoices_exploded = explode(',', $invoices->total);
                    $sum_invoice = array_sum($invoices_exploded);
                }

                $debit_note = DebitNote::where('organisation_id', $user->organisation_id)
                    ->orderBy('id', 'desc')
                    ->first();

                if (is_object($debit_note) && isset($debit_note->debit_note_number)) {
                    $variable = "debit_note";
                    $nextComingNumber['prefix_is'] = null;
                    $nextComingNumber['number_is'] = null;
                    if (CodeSetting::count() > 0) {
                        $code_setting = CodeSetting::first();
                        if ($code_setting['is_final_update_' . $variable] == 1) {
                            $nextComingNumber['prefix_is'] = $code_setting['prefix_code_' . $variable];
                            $nextComingNumber['number_is'] = $code_setting['next_coming_number_' . $variable];
                        }
                    } else {
                        preg_match_all('!\d+!', $variable, $newNumber);
                        pre($variable);
                    }
                    $code = implode(" ", $nextComingNumber);
                } else {
                    $code_setting = CodeSetting::where('organisation_id', request()->user()->organisation_id)->first();
                    if (!is_object($code_setting)) {
                        $code_setting = new CodeSetting;
                    }
                    $code_setting['is_code_auto_debit_note']     = 1;
                    $code_setting['prefix_code_debit_note']      = "DEBIT";
                    $code_setting['start_code_debit_note']       = "00001";
                    $code_setting['next_coming_number_debit_note'] = "DEBIT00002";
                    $code_setting['is_final_update_debit_note']  = 1;
                    $code_setting->save();

                    $code = $code_setting['next_coming_number_debit_note'];
                }

                $debitnote = new DebitNote;
                $debitnote->customer_id = $o->user_id;
                $debitnote->organisation_id = $user->organisation_id;
                $debitnote->salesman_id = null;

                $debitnote->reason = null;
                $debitnote->debit_note_date = date('Y-m-d', strtotime($lastDayofPreviousMonth));
                $debitnote->debit_note_number = nextComingNumber('App\Model\DebitNote', 'debit_note', 'debit_note_number', $code);

                $debitnote->total_qty = 0;

                if ($type == "rebate_discount") {
                    $amount = $o->value;
                    $discount_amount = $o->value;
                    $debitnote->total_gross = $amount; // rebate discount amout
                    if ($sum_invoice != 0) {
                        $debitnote->total_discount_amount = $discount_amount; // Rebate discount
                    }
                } else {
                    $amount = $o->amount;
                    if ($type == "listing_fees") {
                        $d1 = new \DateTime($o->from_date);
                        $d2 = new \DateTime($o->to_date);
                        $interval = $d2->diff($d1);
                        $month = (int)$interval->format('%m months');
                        $amount = $amount / $month;
                    }

                    $discount_amount = 0;
                    $debitnote->total_gross = $amount; // listing feed amout
                    $debitnote->total_discount_amount = $discount_amount; // Rebate discount
                }

                $debitnote->total_net = $amount - $discount_amount;
                $debitnote->total_vat = getPercentAmount($debitnote->total_net);
                $debitnote->total_excise = 0;

                $debitnote->grand_total = $debitnote->total_net + $debitnote->total_vat;
                // if ($type == "rebate_discount") {
                // } else {
                //     $debitnote->grand_total = getPercentAmount($debitnote->total_net) + $o->amount;
                // }
                $debitnote->debit_note_comment = null;
                $debitnote->source = 3;
                $debitnote->status = 1;
                $debitnote->is_debit_note = 0;
                $debitnote->debit_note_type = $type;
                $debitnote->lob_id = $o->lob_id;

                if ($debitnote->save()) {
                    $status_val = 1;
                }

                if ($type == 'listing_fees') {
                    $item_name = 'Listing fees';
                }

                if ($type == 'shelf_rent') {
                    $item_name = 'Shelf Rent';
                }

                if ($type == 'rebate_discount') {
                    $item_name = 'Rebate Discount';
                }

                $debitnote_list_shelf_rebate = new DebitNoteListingfeeShelfrentRebatediscountDetail;
                $debitnote_list_shelf_rebate->debit_note_id         = $debitnote->id;
                $debitnote_list_shelf_rebate->customer_id           = $o->user_id;
                $debitnote_list_shelf_rebate->date                  = $lastDayofPreviousMonth;
                $debitnote_list_shelf_rebate->amount                = $o->amount;
                $debitnote_list_shelf_rebate->item_name             = $item_name;
                $debitnote_list_shelf_rebate->type                  = $type;

                if ($type == "rebate_discount") {
                    $amount = $o->value;
                    $discount_amount = $o->value;
                    $debitnote_list_shelf_rebate->item_price = $amount; // listing feed amout
                    $debitnote_list_shelf_rebate->item_gross = $amount; // listing feed amout
                    if ($sum_invoice != 0) {
                        $debitnote_list_shelf_rebate->item_discount_amount = $discount_amount; // Rebate discount
                    }
                } else {
                    $amount = $o->amount;
                    $discount_amount = 0;
                    $debitnote_list_shelf_rebate->item_price =  $amount; // listing feed amout
                    $debitnote_list_shelf_rebate->item_gross = $amount; // listing feed amout
                    $debitnote_list_shelf_rebate->item_discount_amount = $discount_amount; // Rebate discount
                }
                $debitnote_list_shelf_rebate->item_net = $amount - $discount_amount;
                $debitnote_list_shelf_rebate->item_vat = getPercentAmount($debitnote_list_shelf_rebate->item_net);
                $debitnote_list_shelf_rebate->item_grand_total = $debitnote_list_shelf_rebate->item_net + $debitnote_list_shelf_rebate->item_vat;

                $debitnote_list_shelf_rebate->save();
            }
        });

        if ($status_val == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Debite Note Document create in storage.
     *
     * @param  \Illuminate\Http\Request  $request  
     * @param  year  $year
     * @param  date  $month
     * @param  int  $listing_fee_id
     * @param  int  $shelf_rent_id
     * @param  int  $rebate_discount_id
     * @return \Illuminate\Http\Response
     */

    public function debiteNoteDocument(Request $request)
    {
        $date = $request->year . '-' . $request->month;

        $given_date =   date('Y-m', strtotime($date));
        $firstDayofPreviousMonth = Carbon::parse($given_date)->startOfMonth()->subMonth()->toDateString();
        $lastDayofPreviousMonth =   Carbon::parse($given_date)->subMonth()->endOfMonth()->toDateString();

        $input = $request->json()->all();
        $validate = $this->validations($input, "debite_note_document");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating debite note document create", $this->unprocessableEntity);
        }

        if ($request->type == 'listing_fees') {
            if (empty($request->listing_fee_id)) {
                return prepareResult(false, [], "listing fee id is required", "Error while validating debite note document create", $this->unprocessableEntity);
            }

            $listing_fee = ListingFee::where('id',  $request->listing_fee_id)->get();

            $return = $this->saveDebitNote($listing_fee, "listing_fees", $lastDayofPreviousMonth, $firstDayofPreviousMonth);

            if ($return) {
                return prepareResult(true, [], [], "listing fees record added successfully", $this->created);
            } else {
                return prepareResult(false, [], [], "May be record already there or something went wrong, please try again.", $this->internal_server_error);
            }
        }

        if ($request->type == 'shelf_rent') {
            if (empty($request->shelf_rent_id)) {
                return prepareResult(false, [], "shelf rent id is required", "Error while validating debite_note_document create", $this->unprocessableEntity);
            }

            $shelf_rent = ShelfRent::where('id',  $request->shelf_rent_id)->get();
            $return =  $this->saveDebitNote($shelf_rent, "shelf_rent", $lastDayofPreviousMonth, $firstDayofPreviousMonth);

            if ($return) {
                return prepareResult(true, [], [], "shelf rent record added successfully", $this->created);
            } else {
                return prepareResult(false, [], [], "May be record already there or  something went wrong, please try again.", $this->internal_server_error);
            }
        }

        if ($request->type == 'rebate_discount') {
            if (empty($request->rebate_discount_id)) {
                return prepareResult(false, [], "rebate discount id is required", "Error while validating debite_note_document create", $this->unprocessableEntity);
            }

            $rebate_discount = RebateDiscount::where('id',  $request->rebate_discount_id)->get();

            $return = $this->saveDebitNote($rebate_discount, "rebate_discount", $lastDayofPreviousMonth, $firstDayofPreviousMonth);

            if ($return) {
                return prepareResult(true, [], [], "rebate discount record added successfully", $this->created);
            } else {
                return prepareResult(false, [], [], "May be record already there or something went wrong, please try again.", $this->internal_server_error);
            }
        }
    }
}
