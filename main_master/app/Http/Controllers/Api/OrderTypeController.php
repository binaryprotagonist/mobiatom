<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\OrderType;
use Facade\FlareClient\Http\Response;

class OrderTypeController extends Controller
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

        $orderType = OrderType::select('id', 'uuid', 'organisation_id', 'use_for', 'name', 'description', 'status')
            ->where('for_module', 'Order')
            ->where('organisation_id', Auth()->user()->organisation_id)
            ->orWhereNull('organisation_id')
            ->orderBy('id', 'desc')
            ->where('status', 1)
            ->get();

        $orderType_array = array();
        if (is_object($orderType)) {
            foreach ($orderType as $key => $orderType1) {
                $orderType_array[] = $orderType[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($orderType_array[$offset])) {
                    $data_array[] = $orderType_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($orderType_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($orderType_array);
        } else {
            $data_array = $orderType_array;
        }

        return prepareResult(true, $data_array, [], "Order Type listing", $this->success, $pagination);

        // return prepareResult(true, $orderType, [], "Order Type listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order type", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {
            $orderType = new OrderType;
            $orderType->organisation_id     = Auth()->user()->organisation_id;
            $orderType->use_for     = $request->use_for;
            $orderType->name        = $request->name;
            $orderType->description = $request->description;
            // $orderType->prefix_code = $request->prefix_code;
            // $orderType->start_range  = $request->start_range;
            // $orderType->end_range    = $request->end_range;
            // $orderType->next_available_code    = $request->prefix_code.sprintf("%0".strlen($request->end_range)."d", $request->start_range);
            $orderType->save();
            \DB::commit();
            return prepareResult(true, $orderType, [], "Order type added successfully", $this->created);
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

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating order type", $this->unauthorized);
        }

        $orderType = OrderType::select('id', 'uuid', 'organisation_id', 'use_for', 'name', 'description', 'status')
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($orderType)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $orderType, [], "Order Type Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating order type", $this->unprocessableEntity);
        }

        $orderType = OrderType::where('uuid', $uuid)
            ->first();

        if (!is_object($orderType)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        \DB::beginTransaction();
        try {
            $orderType->name        = $request->name;
            $orderType->description = $request->description;
            // $orderType->prefix_code = $request->prefix_code;
            // $orderType->start_range = $request->start_range;
            // $orderType->end_range   = $request->end_range;
            $orderType->save();
            \DB::commit();
            return prepareResult(true, $orderType, [], "Order type added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating order type", $this->unauthorized);
        }

        $orderType = OrderType::where('uuid', $uuid)
            ->first();

        if (is_object($orderType)) {
            $orderType->delete();

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
                'name'          => 'required',
                'description'   => 'required',
                // 'prefix_code'   => 'required',
                // 'start_range'   => 'required|integer',
                // 'end_range'     => 'required|integer|gt:start_range',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
