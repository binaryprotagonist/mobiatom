<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\TaxRates;
use Illuminate\Http\Request;

class TaxRateController extends Controller
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

        $tax_rate = TaxRates::orderBy('id', 'desc')->get();

        $tax_rate_array = array();
        if (is_object($tax_rate)) {
            foreach ($tax_rate as $key => $tax_rate1) {
                $tax_rate_array[] = $tax_rate[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($tax_rate_array[$offset])) {
                    $data_array[] = $tax_rate_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($tax_rate_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($tax_rate_array);
        } else {
            $data_array = $tax_rate_array;
        }

        return prepareResult(true, $data_array, [], "Tax rate listing", $this->success, $pagination);
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
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax rate", $this->unprocessableEntity);
        }

        $tax_rate = new TaxRates;
        $tax_rate->name = $request->name;
        $tax_rate->rate = $request->rate;
        $tax_rate->type = $request->type;
        $tax_rate->save();

        if ($tax_rate) {
            return prepareResult(true, $tax_rate, [], "Tax rate added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\TaxRates  $taxRates
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $tax_rate = TaxRates::where('uuid', $uuid)
            ->first();

        if ($tax_rate) {
            return prepareResult(true, $tax_rate, [], "Tax rate edit successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\TaxRates  $taxRates
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax rate", $this->unprocessableEntity);
        }

        $tax_rate = TaxRates::where('uuid', $uuid)->first();

        if (!is_object($tax_rate)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $tax_rate->name = $request->name;
        $tax_rate->rate = $request->rate;
        $tax_rate->type = $request->type;
        $tax_rate->save();

        if ($tax_rate) {
            return prepareResult(true, $tax_rate, [], "Tax rate updated successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\TaxRates  $taxRates
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating area", $this->unprocessableEntity);
        }

        $tax_rate = TaxRates::where('uuid', $uuid)
            ->first();

        if (is_object($tax_rate)) {
            $tax_rate->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'name'     => 'required',
                'rate'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
