<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\TaxSetting;
use Illuminate\Http\Request;

class TaxSettingController extends Controller
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

        $tax_setting = TaxSetting::first();

        if (!is_object($tax_setting)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $tax_setting, [], "Tax settinglisting", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating tax setting", $this->unprocessableEntity);
        }

        $taxSetting = TaxSetting::first();
        if (is_object($taxSetting)) {
            $taxSetting->delete();
        }

        $tax_setting = new TaxSetting;
        $tax_setting->is_tax_registered = $request->is_tax_registered;
        if ($request->is_tax_registered == 1) {
            $tax_setting->trn_text = $request->trn_text;
            $tax_setting->number = $request->number;
            $tax_setting->register_date = $request->register_date;
            $tax_setting->composition_scheme = $request->composition_scheme;
            $tax_setting->composition_scheme_percentage = $request->composition_scheme_percentage;
            $tax_setting->digital_services = $request->digital_services;
        }
        $tax_setting->save();

        if ($tax_setting) {
            return prepareResult(true, $tax_setting, [], "Tax setting added successfully", $this->success);
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
                'is_tax_registered'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
