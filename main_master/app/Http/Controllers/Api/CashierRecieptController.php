<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CashierReciept;
use App\Model\CashierRecieptDetail;
use App\Model\Route;
use App\Model\Collection;
use App\Model\CollectionDetails;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use DB;

class CashierRecieptController extends Controller
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

        if (!checkPermission('cashier-receipt-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('cashier-receipt-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $CashierReciept_query = CashierReciept::with(
            'route:id,route_code,route_name',
            'salesman:id,firstname,lastname',
            'cashierrecieptdetail'
        )
            ->where('payment_type', $request->type);

        if ($request->code) {
            $CashierReciept_query->where('cashier_reciept_number', 'like', '%' . $request->code . '%');
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $CashierReciept_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $CashierReciept_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->salesman_code) {
            $salesman_code = $request->salesman_code;
            $CashierReciept_query->whereHas('salesman.salesmanInfo', function ($q) use ($salesman_code) {
                $q->where('salesman_code', 'like', $salesman_code);
            });
        }

        if ($request->route) {
            $route = $request->route;
            $CashierReciept_query->whereHas('route', function ($q) use ($route) {
                $q->where('route_name', 'like', $route);
            });
        }

        $CashierReciept = $CashierReciept_query->orderBy('id', 'desc')
            ->get();

        $CashierReciept_array = array();
        if (is_object($CashierReciept)) {
            foreach ($CashierReciept as $key => $CashierReciept1) {
                $CashierReciept_array[] = $CashierReciept[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();

        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($CashierReciept_array[$offset])) {
                    $data_array[] = $CashierReciept_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($CashierReciept_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($CashierReciept_array);
        } else {
            $data_array = $CashierReciept_array;
        }

        return prepareResult(true, $data_array, [], "Cashier Reciept listing", $this->success, $pagination);
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

        if (!checkPermission('cashier-receipt-add')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }


        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Cashier Reciept", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $cashierreciept = new CashierReciept;
            $cashierreciept->cashier_reciept_number         = nextComingNumber('App\Model\CashierReciept', 'cashier_reciept', 'cashier_reciept_number', $request->cashier_reciept_number);
            $cashierreciept->route_id            = (!empty($request->route_id)) ? $request->route_id : null;
            $cashierreciept->salesman_id            = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $cashierreciept->slip_number       = (!empty($request->slip_number)) ? $request->slip_number : null;
            $cashierreciept->bank_id        = (!empty($request->bank_id)) ? $request->bank_id : null;
            $cashierreciept->date       = date('Y-m-d', strtotime($request->date));
            $cashierreciept->slip_date       = date('Y-m-d', strtotime($request->slip_date));
            $cashierreciept->total_amount        = (!empty($request->total_amount)) ? $request->total_amount : null;
            $cashierreciept->payment_type        = (!empty($request->payment_type)) ? $request->payment_type : null;
            $cashierreciept->actual_amount        = (!empty($request->actual_amount)) ? $request->actual_amount : null;
            $cashierreciept->variance        = (!empty($request->variance)) ? $request->variance : null;
            $cashierreciept->save();

            //----------
            //--------------Invoice
            if ($request->variance != 0) {
                $invoice = new Invoice;
                $invoice->customer_id         = $request->salesman_id;
                $invoice->order_id            = $request->slip_number;
                $invoice->order_type_id       = $cashierreciept->id;
                $invoice->delivery_id         = $cashierreciept->id;
                $invoice->depot_id            = $request->route_id;
                $invoice->trip_id            = $request->salesman_id;
                $invoice->salesman_id            = $request->salesman_id;
                $invoice->invoice_type        = 2;
                $invoice->invoice_number      = $cashierreciept->id;
                $invoice->invoice_date        = date('Y-m-d', strtotime($request->date));
                $invoice->payment_term_id     = 30;
                $invoice->total_qty           = 0;
                $invoice->total_gross         = $request->variance;
                $invoice->total_discount_amount   = 0;
                $invoice->total_net           = $request->variance;
                $invoice->total_vat           = 0;
                $invoice->total_excise        = 0;
                $invoice->grand_total         = $request->variance;
                $invoice->pending_credit = $request->variance;
                $invoice->current_stage_comment         = "Variance Invoice";
                $invoice->source              = 2;
                $invoice->status              = 0;
                $invoice->save();
            }
            //----------


            \DB::commit();
            updateNextComingNumber('App\Model\CashierReciept', 'cashier_reciept');

            $cashierreciept->getSaveData();

            return prepareResult(true, $cashierreciept, [], "Cashier Reciept added successfully", $this->created);
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

        if (!checkPermission('cashier-receipt-delete')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Cashier Reciept.", $this->unauthorized);
        }

        $cashierreciept = CashierReciept::where('uuid', $uuid)
            ->first();

        if (is_object($cashierreciept)) {
            $cashierrecieptId = $cashierreciept->id;
            $cashierreciept->delete();
            if ($cashierreciept) {
                CashierRecieptDetail::where('cashier_reciept_id', $cashierrecieptId)->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating cashier reciept", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->cashierreciept_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $CashierReciept = CashierReciept::where('uuid', $uuid)
                    ->first();

                if (is_object($CashierReciept)) {
                    $CashierRecieptId = $CashierReciept->id;
                    $CashierReciept->delete();
                    if ($CashierReciept) {
                        CashierRecieptDetail::where('cashier_reciept_id', $CashierRecieptId)->delete();
                    }
                }
            }
            $CashierReciept = $this->index();
            return prepareResult(true, $CashierReciept, [], "Cashier reciept deleted success", $this->success);
        }
    }
    public function getcollection(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "get");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Cashier Reciept", $this->unprocessableEntity);
        }


        $collection_array = array();
        $cash_collection = Collection::with(
            'invoice',
            'customer:id,firstname,lastname',
            'salesman:id,firstname,lastname',
            'collectiondetails',
            'collectiondetails.invoice:id,grand_total,invoice_number,total_net',
            'collectiondetails.debit_note:id,debit_note_number,total_net,grand_total',
            'collectiondetails.credit_note:id,credit_note_number,total_net,grand_total',
        )
        ->where('salesman_id', $request->salesman_id)
        ->where('payemnt_type', $request->payment_type)
        ->whereDate('created_at', $request->date)
        ->get();

        $collection_array = $cash_collection;
        return prepareResult(true, $collection_array, [], "Cashier Reciept listing", $this->success);
    }
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'route_id' => 'required|integer',
                'salesman_id' => 'required|integer',
                'slip_number' => 'required',
                'bank_id' => 'required|integer',
                'date' => 'required|date',
                'slip_date' => 'required|date',
                'total_amount' => 'required',
            ]);
        }
        if ($type == "get") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer',
                'payment_type' => 'required|integer',
                'date' => 'required|date'

            ]);
        }
        if ($type == "pdc") {
            $validator = \Validator::make($input, [
                'bank_id' => 'required',
                'cheque_no' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'cashierreciept_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
