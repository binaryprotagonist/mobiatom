<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Currency;
use App\Model\CurrencyMaster;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function allCurrency()
    {
        $currency = CurrencyMaster::select('id', 'symbol', 'name', 'code', 'name_plural', 'symbol_native', 'decimal_digits', 'rounding')->get();

        $currency_array = array();
        if (is_object($currency)) {
            foreach ($currency as $key => $currency1) {
                $currency_array[] = $currency[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($currency_array[$offset])) {
                    $data_array[] = $currency_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($currency_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($currency_array);
        } else {
            $data_array = $currency_array;
        }
        return prepareResult(true, $data_array, [], "Currency listing", $this->success, $pagination);
    }


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

        $country = Currency::select('id', 'uuid', 'organisation_id', 'currency_master_id', 'name', 'symbol', 'code', 'name_plural', 'symbol_native', 'decimal_digits', 'rounding', 'default_currency', 'format')
            ->with('currencyMaster:id,name,symbol,code,name_plural,symbol_native,decimal_digits,rounding')
            ->get();

        $count = 10 - $country->count();

        $currencyMaster = CurrencyMaster::select('id', 'symbol', 'name', 'code', 'name_plural', 'symbol_native', 'decimal_digits', 'rounding')
        ->whereNotIn('id', $country->pluck('currency_master_id')->toArray())
        ->get()
        ->take($count);

        $currencies = array_merge($country->toArray(), $currencyMaster->toArray());

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($currencies[$offset])) {
                    $data_array[] = $currencies[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($currencies) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($currencies);
        } else {
            $data_array = $currencies;
        }
        return prepareResult(true, $data_array, [], "Currency listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating currency", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $currency = new Currency;
            $currency->currency_master_id = $request->currency_master_id;
            $currency->name = $request->name;
            $currency->symbol = $request->symbol;
            $currency->code = $request->code;
            $currency->name_plural = $request->name_plural;
            $currency->symbol_native = $request->symbol_native;
            $currency->decimal_digits = $request->decimal_digits;
            $currency->rounding = $request->rounding;
            $currency->default_currency = $request->default_currency;
            $currency->format = $request->format;
            $currency->save();

            \DB::commit();
            $currency->currencyMaster;
            return prepareResult(true, $currency, [], "Currency added successfully", $this->created);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating country", $this->unauthorized);
        }

        $currency = Currency::select('id', 'id', 'organisation_id', 'currency_master_id', 'name', 'symbol', 'code', 'name_plural', 'symbol_native', 'decimal_digits', 'rounding', 'default_currency', 'format')
            ->with('currencyMaster:id,name,symbol,code,name_plural,symbol_native,decimal_digits,rounding')
            ->where('id', $id)
            ->first();

        if (!is_object($currency)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }
        $currency->currencyMaster;
        return prepareResult(true, $currency, [], "Currency Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating currency", $this->success);
        }

        $currency = Currency::where('id', $id)
            ->first();
        $currencyMaster = array();

        if (!is_object($currency)) {
            $currencyMaster = CurrencyMaster::where('id', $id)
            ->first();
        }

        if (!is_object($currency) && !is_object($currencyMaster)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        \DB::beginTransaction();
        try {
            if (is_object($currencyMaster)) {
                $currency = new Currency;
                $currency->currency_master_id = $currencyMaster->id;
                $currency->name = $request->name;
                $currency->symbol = $request->symbol;
                $currency->code = $request->code;
                $currency->name_plural = $request->name_plural;
                $currency->symbol_native = $request->symbol_native;
                $currency->decimal_digits = $request->decimal_digits;
                $currency->rounding = $request->rounding;
                $currency->default_currency = 0;
                if ($request->decimal_digits == 2) {
                    $currency->format = '1,234,567.89';
                } else if ($request->decimal_digits == 3) {
                    $currency->format = '1,234,567.899';
                } else {
                    $currency->format = '1,234,567';
                }
                $currency->save();
            } else {
                $currency->currency_master_id = $request->currency_master_id;
                $currency->name = $request->name;
                $currency->symbol = $request->symbol;
                $currency->code = $request->code;
                $currency->name_plural = $request->name_plural;
                $currency->symbol_native = $request->symbol_native;
                $currency->decimal_digits = $request->decimal_digits;
                $currency->rounding = $request->rounding;
                $currency->default_currency = $request->default_currency;
                $currency->format = $request->format;
                $currency->save();
            }

            \DB::commit();
            $currency->currencyMaster;
            return prepareResult(true, $currency, [], "Currency updated successfully", $this->created);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating country", $this->unauthorized);
        }

        $currency = Currency::where('uuid', $uuid)
            ->first();

        if (is_object($currency)) {
            $currency->delete();
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
                'currency_master_id' => 'required|integer|exists:currency_masters,id',
                'name' => 'required',
                'symbol' => 'required',
                'code' => 'required',
                'name_plural' => 'required',
                'symbol_native' => 'required',
                'decimal_digits' => 'required',
                'rounding' => 'required',
                'format' =>  'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'currency_ids'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $action
     * @param  string  $status
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // if (!checkPermission('currency-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating currency.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->currency_ids;

            foreach ($uuids as $uuid) {
                Currency::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $currency = $this->index();
            return prepareResult(true, $currency, [], "Currency status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->currency_ids;
            foreach ($uuids as $uuid) {
                Currency::where('uuid', $uuid)->delete();
            }

            $currency = $this->index();
            return prepareResult(true, $currency, [], "Currency deleted success", $this->success);
        }
    }
}
