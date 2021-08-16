<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Batch;

class BatchController extends Controller
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

        $batch = Batch::select('id', 'uuid', 'item_id', 'batch_number', 'manufacturing_date', 'expiry_date', 'manufactured_by', 'qty', 'current_in_stock', 'stock_out_sequence', 'status')
            ->with('item:id,uuid,item_major_category_id,item_sub_category_id,item_group_id,brand_id,sub_brand_id,item_code,item_name,item_barcode,item_weight,item_shelf_life,lower_unit_uom_id,is_tax_apply,status')
            ->orderBy('id', 'desc')
            ->get();
        $batch_array = array();
        if (is_object($batch)) {
            foreach ($batch as $key => $batch1) {
                $batch_array[] = $batch[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($batch_array[$offset])) {
                    $data_array[] = $batch_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($batch_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($batch_array);
        } else {
            $data_array = $batch_array;
        }

        return prepareResult(true, $data_array, [], "Batch listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Batch", $this->unprocessableEntity);
        }

        $batch = new Batch;
        $batch->item_id = $request->item_id;
        $batch->batch_number = $request->batch_number;
        $batch->manufacturing_date = $request->manufacturing_date;
        $batch->expiry_date = $request->expiry_date;
        $batch->manufactured_by = $request->manufactured_by;
        $batch->qty = $request->qty;
        $batch->current_in_stock = $request->current_in_stock;
        $batch->stock_out_sequence = $request->stock_out_sequence;
        $batch->status = $request->status;
        $batch->save();

        if (is_object($batch)) {
            $batch->item;
            return prepareResult(true, $batch, [], "Batch added successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
    }

    /**
     * Edit the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $batch = Batch::where('uuid', $uuid)
            ->select('id', 'uuid', 'item_id', 'batch_number', 'manufacturing_date', 'expiry_date', 'manufactured_by', 'qty', 'current_in_stock', 'stock_out_sequence', 'status')
            ->with('item:id,uuid,item_major_category_id,item_sub_category_id,item_group_id,brand_id,sub_brand_id,item_code,item_name,item_barcode,item_weight,item_shelf_life,lower_unit_uom_id,is_tax_apply,status')
            ->first();

        if (is_object($batch)) {
            return prepareResult(true, $batch, [], "Batch edit successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
    }

    /**
     * Update a created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Batch", $this->unprocessableEntity);
        }

        $batch = Batch::where('uuid', $uuid)
            ->select('id', 'uuid', 'item_id', 'batch_number', 'manufacturing_date', 'expiry_date', 'manufactured_by', 'qty', 'current_in_stock', 'stock_out_sequence', 'status')
            ->first();

        if (!is_object($batch)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $batch->item_id = $request->item_id;
        $batch->batch_number = $request->batch_number;
        $batch->manufacturing_date = $request->manufacturing_date;
        $batch->expiry_date = $request->expiry_date;
        $batch->manufactured_by = $request->manufactured_by;
        $batch->qty = $request->qty;
        $batch->current_in_stock = $request->current_in_stock;
        $batch->stock_out_sequence = $request->stock_out_sequence;
        $batch->status = $request->status;
        $batch->save();

        $batch->item;

        return prepareResult(true, $batch, [], "Batch updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $batch = Batch::where('uuid', $uuid)
            ->first();

        if (is_object($batch)) {
            $batch->delete();
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
                'item_id' => 'required|integer|exists:items,id',
                'batch_number' => 'required',
                'manufacturing_date' => 'required|date',
                'expiry_date' => 'required|date',
                'manufactured_by' => 'required',
                'qty' => 'required|numeric',
                'current_in_stock' => 'required',
                'stock_out_sequence' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'batch_ids'     => 'required'
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

        // if (!checkPermission('item-group-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating batch.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->batch_ids;

            foreach ($uuids as $uuid) {
                Batch::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $batch = $this->index();
            return prepareResult(true, $batch, [], "Batch status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->batch_ids;
            foreach ($uuids as $uuid) {
                Batch::where('uuid', $uuid)->delete();
            }

            $batch = $this->index();
            return prepareResult(true, $batch, [], "Batch deleted success", $this->success);
        }
    }
}
