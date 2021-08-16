<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\OutletProductCode;
use App\Model\OutletProductCodeCustomer;
use App\Model\OutletProductCodeItem;

class OutletProductController extends Controller
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

        $outlet_product_code = OutletProductCode::with(
            'outletProductCodeCustomers:id,uuid,outlet_product_code_id,customer_id',
            'outletProductCodeCustomers.customer:id,firstname,lastname',
            'outletProductCodeCustomers.customer.customerInfo:id,user_id,customer_code',
            'outletProductCodeItems:id,uuid,outlet_product_code_id,item_id,outlet_product_code',
            'outletProductCodeItems.item:id,item_code,item_name'
        )
            ->orderBy('id', 'desc')
            ->get();

        $outlet_product_code_array = array();
        if (is_object($outlet_product_code)) {
            foreach ($outlet_product_code as $key => $outlet_product_code1) {
                $outlet_product_code_array[] = $outlet_product_code[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($outlet_product_code_array[$offset])) {
                    $data_array[] = $outlet_product_code_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($outlet_product_code_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($outlet_product_code_array);
        } else {
            $data_array = $outlet_product_code_array;
        }

        return prepareResult(true, $data_array, [], "Outlet product code listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating outlet product code", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $outlet_product_code = new OutletProductCode;
            $outlet_product_code->name = $request->name;
            $outlet_product_code->code = nextComingNumber('App\Model\OutletProductCode', 'outlet_product_codes', 'code', $request->code);
            // $outlet_product_code->code = $request->code;
            $outlet_product_code->save();

            if (is_array($request->customers) && sizeof($request->customers) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
            }

            if (is_array($request->customers)) {
                foreach ($request->customers as $customer) {
                    //save OutletProductCodeCustomer
                    $outlet_product_code_customer = new OutletProductCodeCustomer;
                    $outlet_product_code_customer->outlet_product_code_id = $outlet_product_code->id;
                    $outlet_product_code_customer->customer_id = $customer['customer_id'];
                    $outlet_product_code_customer->save();
                }
            }

            if (is_array($request->items) && sizeof($request->items) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //save OutletProductCodeItem
                    $outlet_product_code_item = new OutletProductCodeItem;
                    $outlet_product_code_item->outlet_product_code_id = $outlet_product_code->id;
                    $outlet_product_code_item->item_id = $item['item_id'];
                    $outlet_product_code_item->outlet_product_code = $item['outlet_product_code'];
                    $outlet_product_code_item->save();
                }
            }


            \DB::commit();
            updateNextComingNumber('App\Model\OutletProductCode', 'outlet_product_codes');

            $outlet_product_code->getSaveData();

            return prepareResult(true, $outlet_product_code, [], "Outlet Product Code added successfully", $this->success);
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

        $outlet_product_code = OutletProductCode::where('uuid', $uuid)
            ->with(
                'outletProductCodeCustomers:id,uuid,outlet_product_code_id,customer_id',
                'outletProductCodeCustomers.customer:id,firstname,lastname',
                'outletProductCodeCustomers.customer.customerInfo:id,user_id,customer_code',
                'outletProductCodeItems:id,uuid,outlet_product_code_id,item_id,outlet_product_code',
                'outletProductCodeItems.item:id,item_code,item_name'
            )
            ->first();

        if (!is_object($outlet_product_code)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unprocessableEntity);
        }

        return prepareResult(true, $outlet_product_code, [], "Outlet product code Edit", $this->success);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating outlet product code", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $outlet_product_code = OutletProductCode::where('uuid', $uuid)
                ->first();

            if (!is_object($outlet_product_code)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            OutletProductCodeCustomer::where('outlet_product_code_id', $outlet_product_code->id)
                ->delete();

            OutletProductCodeItem::where('outlet_product_code_id', $outlet_product_code->id)
                ->delete();

            $outlet_product_code->name = $request->name;
            $outlet_product_code->save();

            if (is_array($request->customers) && sizeof($request->customers) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one customers.", $this->unprocessableEntity);
            }

            if (is_array($request->customers)) {
                foreach ($request->customers as $customer) {
                    //save OutletProductCodeCustomer
                    $outlet_product_code_customer = new OutletProductCodeCustomer;
                    $outlet_product_code_customer->outlet_product_code_id = $outlet_product_code->id;
                    $outlet_product_code_customer->customer_id = $customer['customer_id'];
                    $outlet_product_code_customer->save();
                }
            }

            if (is_array($request->items) && sizeof($request->items) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //save OutletProductCodeItem
                    $outlet_product_code_item = new OutletProductCodeItem;
                    $outlet_product_code_item->outlet_product_code_id = $outlet_product_code->id;
                    $outlet_product_code_item->item_id = $item['item_id'];
                    $outlet_product_code_item->outlet_product_code = $item['outlet_product_code'];
                    $outlet_product_code_item->save();
                }
            }

            \DB::commit();
            $outlet_product_code->getSaveData();
            return prepareResult(true, $outlet_product_code, [], "Outlet Product Code added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating outlet product code", $this->unauthorized);
        }

        $outlet_product_code = OutletProductCode::where('uuid', $uuid)
            ->first();

        if (is_object($outlet_product_code)) {
            $outlet_product_code->delete();

            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'name' => 'required',
                'code' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
