<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CollectionDetails;
use App\Model\CustomerInfo;
use Illuminate\Http\Request;
use App\Model\ShelfRent;
use App\User;

class ShelfRentController extends Controller
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

        $SalePrice_query = ShelfRent::with(
            'user:id,firstname,lastname',
            'user.customerInfo:id,user_id,customer_code',
            'lob'
        );

        if ($request->customer_name) {
            $customer_name = $request->customer_name;
            $exploded_name = explode(" ", $customer_name);
            if (count($exploded_name) < 2) {
                $SalePrice_query->whereHas('user', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $SalePrice_query->whereHas('user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $SalePrice_query->where('customer_code', 'like', '%' . $request->customer_code . '%');
        }

        if ($request->agreement_code) {
            $SalePrice_query->where('agreement_id', 'like', '%' . $request->agreement_code . '%');
        }

        if ($request->name) {
            $SalePrice_query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->start_date) {
            $SalePrice_query->whereDate('from_date', $request->start_date);
        }

        if ($request->end_date) {
            $SalePrice_query->whereDate('from_to', $request->end_date);
        }

        $SalePrice = $SalePrice_query->orderBy('id', 'desc')
            ->get();

        $SalePrice_array = array();
        if (is_object($SalePrice)) {
            foreach ($SalePrice as $key => $SalePrice1) {
                $SalePrice_array[] = $SalePrice[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($SalePrice_array[$offset])) {
                    $data_array[] = $SalePrice_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($SalePrice_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($SalePrice_array);
        } else {
            $data_array = $SalePrice_array;
        }

        return prepareResult(true, $data_array, [], "Sales price details", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Sales price request", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $sales_price = new ShelfRent;
            $sales_price->agreement_id       = $request->agreement_id;
            $sales_price->customer_code      = $request->customer_code;
            $sales_price->user_id            = $request->user_id;
            $sales_price->name               = $request->name;
            $sales_price->amount             = $request->shelf_rent_amount;
            $sales_price->from_date          = date('Y-m-d', strtotime($request->from_date));
            $sales_price->to_date            = date('Y-m-d', strtotime($request->to_date));
            $sales_price->lob_id             = (!empty($request->lob_id)) ? $request->lob_id : null;
            $sales_price->status             = (!empty($request->status)) ? $request->status : 1;
            $sales_price->save();

            \DB::commit();
            $sales_price->getSaveData();
            return prepareResult(true, $sales_price, [], "Sales price added successfully", $this->created);
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

        $shelf_rent = ShelfRent::where('uuid', $uuid)
            ->with(
                'user:id,firstname,lastname',
                'user.customerInfo:id,user_id,customer_code',
                'lob'
            )
            ->first();

        if (!is_object($shelf_rent)) {
            return prepareResult(false, [], 'Record is not present', "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $shelf_rent, [], "Sales price Edit", $this->success);
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
            return prepareResult(false, [], [], "select any one sale price record id", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $sales_price = ShelfRent::where('uuid', $uuid)->first();
            if (is_object($sales_price)) {

                $sales_price->agreement_id       = $request->agreement_id;
                $sales_price->customer_code      = $request->customer_code;
                $sales_price->user_id            = $request->user_id;
                $sales_price->name               = $request->name;
                $sales_price->amount  = $request->shelf_rent_amount;
                $sales_price->from_date          = date('Y-m-d', strtotime($request->from_date));
                $sales_price->to_date            = date('Y-m-d', strtotime($request->to_date));
                $sales_price->lob_id             = (!empty($request->lob_id)) ? $request->lob_id : null;
                $sales_price->status             = (!empty($request->status)) ? $request->status : 1;
                $sales_price->save();
            } else {
                return prepareResult(true, [], [], "Record not found.", $this->not_found);
            }

            \DB::commit();
            $sales_price->getSaveData();
            return prepareResult(true, $sales_price, [], "Sale price updated successfully", $this->created);
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
            return prepareResult(false, [], [], "select any one sale price record id", $this->unauthorized);
        }
        $sales_price = ShelfRent::where('uuid', $uuid)->first();

        if (is_object($sales_price)) {
            $sales_price->delete();
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
                'shelf_rent_amount' => 'required|numeric',
                'from_date' => 'required|date',
                'to_date' => 'required|date',
                //'lob_id'  => 'required',
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
    public function getSalesPriceCustomer(Request $request)
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


        $SalePrice = ShelfRent::with(
            'user:id,firstname,lastname,email'
        )
            ->where('from_date', '<=', $start_date)
            ->where('to_date', '>=', $end_date)
            // ->whereYear('from_date', '=', $request->year)
            // ->whereMonth('from_date', '=', $request->month)
            ->orderBy('id', 'desc')
            ->get();

        $SalePrice_array = array();
        if (is_object($SalePrice)) {
            foreach ($SalePrice as $key => $SalePrice1) {
                $SalePrice_array[] = $SalePrice[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($SalePrice_array[$offset])) {
                    $data_array[] = $SalePrice_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($SalePrice_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($SalePrice_array);
        } else {
            $data_array = $SalePrice_array;
        }

        return prepareResult(true, $data_array, [], "Sales price details", $this->success, $pagination);
    }
}
