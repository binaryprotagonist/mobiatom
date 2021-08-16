<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\TaxExemption;
use App\Model\TaxPreference;
use Illuminate\Http\Request;

class TaxPreferenceController extends Controller
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

        $tax_preference = TaxPreference::first();

        if (!is_object($tax_preference)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $tax_preference, [], "Tax preferencelisting", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax preference", $this->unprocessableEntity);
        }

        $taxPreference = TaxPreference::first();
        if (is_object($taxPreference)) {
            $taxPreference->delete();
        }

        $tax_preference = new TaxPreference;
        $tax_preference->intra_state_tax_rate = $request->intra_state_tax_rate;
        $tax_preference->inter_state_tax_rate = $request->inter_state_tax_rate;
        $tax_preference->save();

        if ($tax_preference) {
            return prepareResult(true, $tax_preference, [], "Tax exemption added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\TaxPreference  $taxPreference
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $tax_preference = TaxPreference::where('uuid', $uuid)
            ->first();

        if ($tax_preference) {
            return prepareResult(true, $tax_preference, [], "Tax preference edit successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\TaxPreference  $taxPreference
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax preference", $this->unprocessableEntity);
        }

        $tax_preference = TaxPreference::where('uuid', $uuid)->first();

        if (!is_object($tax_preference)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $tax_preference->intra_state_tax_rate = $request->intra_state_tax_rate;
        $tax_preference->inter_state_tax_rate = $request->inter_state_tax_rate;
        $tax_preference->save();

        if ($tax_preference) {
            return prepareResult(true, $tax_preference, [], "Tax preference updated successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'intra_state_tax_rate'     => 'required',
                'inter_state_tax_rate'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
