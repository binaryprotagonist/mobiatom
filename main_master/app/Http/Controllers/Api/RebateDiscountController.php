<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CollectionDetails;
use App\Model\CustomerInfo;
use Illuminate\Http\Request;
use App\Model\RebateDiscount;
use App\User;

class RebateDiscountController extends Controller
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

        $RebateDiscountQuery = RebateDiscount::with(
            'user:id,firstname,lastname',
            'user.customerInfo:id,user_id,customer_code',
            'lob'
        );
        if ($request->customer_name) {
            $customer_name = $request->customer_name;
            $exploded_name = explode(" ", $customer_name);
            if (count($exploded_name) < 2) {
                $RebateDiscountQuery->whereHas('user', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $RebateDiscountQuery->whereHas('user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $RebateDiscountQuery->where('customer_code', 'like', '%' . $request->customer_code . '%');
        }

        if ($request->agreement_code) {
            $RebateDiscountQuery->where('agreement_id', 'like', '%' . $request->agreement_code . '%');
        }

        if ($request->name) {
            $RebateDiscountQuery->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->start_date) {
            $RebateDiscountQuery->whereDate('from_date', $request->start_date);
        }

        if ($request->end_date) {
            $RebateDiscountQuery->whereDate('from_to', $request->end_date);
        }

        $RebateDiscount = $RebateDiscountQuery->orderBy('id', 'desc')
            ->get();

        $RebateDiscount_array = array();
        if (is_object($RebateDiscount)) {
            foreach ($RebateDiscount as $key => $RebateDiscount1) {
                $RebateDiscount_array[] = $RebateDiscount[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($RebateDiscount_array[$offset])) {
                    $data_array[] = $RebateDiscount_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($RebateDiscount_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($RebateDiscount_array);
        } else {
            $data_array = $RebateDiscount_array;
        }

        return prepareResult(true, $data_array, [], "Rebate discount details", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating rebate discount request", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $rebate_discount = new RebateDiscount;
            $rebate_discount->agreement_id        = $request->agreement_id;
            $rebate_discount->customer_code       = $request->customer_code;
            $rebate_discount->user_id             = $request->user_id;
            $rebate_discount->name                = $request->name;
            $rebate_discount->rebate              = $request->rebate;
            $rebate_discount->is_promtional_sales = $request->is_promtional_sales;

            /* if( $request->rebate == 0){
                $rebate_discount->amount           = $request->value;
            }else{
                $rebate_discount->discount_amount  = $request->amount;
            } */

            $rebate_discount->amount           = $request->amount;
            $rebate_discount->discount_amount  = $request->discount_amount;

            $rebate_discount->from_date          = date('Y-m-d', strtotime($request->from_date));
            $rebate_discount->to_date            = date('Y-m-d', strtotime($request->to_date));
            $rebate_discount->lob_id              = (!empty($request->lob_id)) ? $request->lob_id : null;
            $rebate_discount->status              = (!empty($request->status)) ? $request->status : 1;
            $rebate_discount->save();

            \DB::commit();
            $rebate_discount->getSaveData();
            return prepareResult(true, $rebate_discount, [], "Rebate discount added successfully", $this->created);
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

        $rebate_discount = RebateDiscount::where('uuid', $uuid)
            ->with(
                'user:id,firstname,lastname,email',
                'lob'
            )
            ->first();

        if (!is_object($rebate_discount)) {
            return prepareResult(false, [], 'Record is not present', "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $rebate_discount, [], "Rebate discount Edit", $this->success);
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
            return prepareResult(false, [], [], "select any one rebate discount record id", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $rebate_discount = RebateDiscount::where('uuid', $uuid)->first();
            if (is_object($rebate_discount)) {

                $rebate_discount->agreement_id        = $request->agreement_id;
                $rebate_discount->customer_code       = $request->customer_code;
                $rebate_discount->user_id             = $request->user_id;
                $rebate_discount->name                = $request->name;
                $rebate_discount->rebate              = $request->rebate;
                $rebate_discount->is_promtional_sales = $request->is_promtional_sales;

                $rebate_discount->amount           = $request->amount;
                $rebate_discount->discount_amount  = $request->discount_amount;

                $rebate_discount->from_date          = date('Y-m-d', strtotime($request->from_date));
                $rebate_discount->to_date            = date('Y-m-d', strtotime($request->to_date));
                $rebate_discount->lob_id              = (!empty($request->lob_id)) ? $request->lob_id : null;
                $rebate_discount->status              = (!empty($request->status)) ? $request->status : 1;
                $rebate_discount->save();
            } else {
                return prepareResult(true, [], [], "Record not found.", $this->not_found);
            }

            \DB::commit();
            $rebate_discount->getSaveData();
            return prepareResult(true, $rebate_discount, [], "Rebate discount updated successfully", $this->created);
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
            return prepareResult(false, [], [], "select any one Rebate discount record id", $this->unauthorized);
        }
        $rebate_discount = RebateDiscount::where('uuid', $uuid)->first();

        if (is_object($rebate_discount)) {
            $rebate_discount->delete();
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
            $rules_2 = [
                'agreement_id'          => 'required',
                // 'customer_code' => 'required',
                'user_id'               => 'required',
                'name'                  => 'required',
                'rebate'                => 'required',
                'is_promtional_sales'   => 'required',
                'from_date'             => 'required|date',
                'to_date'               => 'required|date',
                //'lob_id'                => 'required',
                'amount'                => 'required',
                'discount_amount'       => 'required',
            ];
        }

        $validator = \Validator::make($input, $rules_2);

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
    public function getRebateDiscountCustomer(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }


        if (!$request->year || !$request->month) {
            return prepareResult(false, [], [], "select month and year", $this->unauthorized);
        }

        $ts = strtotime($request->month . '' . $request->year);
        $start_date = $request->year . '-' . $request->month . '-' . "01";
        $end_date = $request->year . '-' . $request->month . '-' .  date('t', $ts);

        $RebateDiscount = RebateDiscount::with(
            'user:id,firstname,lastname,email',
            'lob'
        )
            ->where('from_date', '<=', $start_date)
            ->where('to_date', '>=', $end_date)
            ->orderBy('id', 'desc')
            ->get();

        $RebateDiscount_array = array();
        if (is_object($RebateDiscount)) {
            foreach ($RebateDiscount as $key => $RebateDiscount1) {
                $RebateDiscount_array[] = $RebateDiscount[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($RebateDiscount_array[$offset])) {
                    $data_array[] = $RebateDiscount_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($RebateDiscount_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($RebateDiscount_array);
        } else {
            $data_array = $RebateDiscount_array;
        }

        return prepareResult(true, $data_array, [], "Rebate discount details", $this->success, $pagination);
    }
}
