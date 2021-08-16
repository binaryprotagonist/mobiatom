<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\PaymentTerm;

class PaymentTermsController extends Controller
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

        $payment_term = PaymentTerm::select('id', 'uuid', 'organisation_id', 'name', 'number_of_days', 'status')
            ->orderBy('id', 'desc')
            ->get();

        $payment_term_array = array();
        if (is_object($payment_term)) {
            foreach ($payment_term as $key => $payment_term1) {
                $payment_term_array[] = $payment_term[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($payment_term_array[$offset])) {
                    $data_array[] = $payment_term_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($payment_term_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($payment_term_array);
        } else {
            $data_array = $payment_term_array;
        }

        return prepareResult(true, $data_array, [], "Payment Terms listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Payment term", $this->unprocessableEntity);
        }

        $payment_term = new PaymentTerm;
        $payment_term->name = $request->name;
        $payment_term->number_of_days = $request->number_of_days;
        $payment_term->status = $request->status;
        $payment_term->save();

        if ($payment_term) {
            return prepareResult(true, $payment_term, [], "Payment term added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
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

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating payment terms", $this->unauthorized);
        }

        $payment_term = PaymentTerm::where('uuid', $uuid)
            ->first();

        if (!is_object($payment_term)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $payment_term, [], "Payment terms Edit", $this->success);
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

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating payment terms", $this->unprocessableEntity);
        }

        $payment_term = PaymentTerm::where('uuid', $uuid)
            ->first();

        if (!is_object($payment_term)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $payment_term->name = $request->name;
        $payment_term->number_of_days = $request->number_of_days;
        $payment_term->status = $request->status;
        $payment_term->save();

        return prepareResult(true, $payment_term, [], "Payment terms updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating payment terms", $this->unauthorized);
        }

        $payment_term = PaymentTerm::where('uuid', $uuid)
            ->first();

        if (is_object($payment_term)) {
            $payment_term->delete();

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
                'name' => 'required',
                'number_of_days' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
