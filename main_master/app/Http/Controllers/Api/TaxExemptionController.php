<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\TaxExemption;
use Illuminate\Http\Request;

class TaxExemptionController extends Controller
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

        $tax_exemption = TaxExemption::orderBy('id', 'desc')->get();

        $tax_exemption_array = array();
        if (is_object($tax_exemption)) {
            foreach ($tax_exemption as $key => $tax_exemption1) {
                $tax_exemption_array[] = $tax_exemption[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($tax_exemption_array[$offset])) {
                    $data_array[] = $tax_exemption_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($tax_exemption_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($tax_exemption_array);
        } else {
            $data_array = $tax_exemption_array;
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax exemption", $this->unprocessableEntity);
        }

        $tax_exemption = new TaxExemption;
        $tax_exemption->reason = $request->reason;
        $tax_exemption->description = $request->description;
        $tax_exemption->type = $request->type;
        $tax_exemption->save();

        if ($tax_exemption) {
            return prepareResult(true, $tax_exemption, [], "Tax exemption added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\TaxExemption  $taxExemption
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $tax_exemption = TaxExemption::where('uuid', $uuid)
            ->first();

        if ($tax_exemption) {
            return prepareResult(true, $tax_exemption, [], "Tax exemption edit successfully", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax exemption", $this->unprocessableEntity);
        }

        $tax_exemption = TaxExemption::where('uuid', $uuid)->first();

        if (!is_object($tax_exemption)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $tax_exemption->reason = $request->reason;
        $tax_exemption->description = $request->description;
        $tax_exemption->type = $request->type;
        $tax_exemption->save();

        if ($tax_exemption) {
            return prepareResult(true, $tax_exemption, [], "Tax exemption updated successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\TaxExemption  $taxExemption
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

        $tax_exemption = TaxExemption::where('uuid', $uuid)
            ->first();

        if (is_object($tax_exemption)) {
            $tax_exemption->delete();
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
                'reason'     => 'required',
                'type'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
