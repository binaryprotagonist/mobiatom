<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\PurchaseOrder;
use App\Model\PurchaseOrderDetail;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
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

        $Purchaseorder = PurchaseOrder::with(
            'vendor:id,firstname,lastname,email,company_name',
            'purchaseorderdetail',
            'purchaseorderdetail.item:id,item_name',
            'purchaseorderdetail.itemUom:id,name,code'
        )
            ->orderBy('id', 'desc')
            //->where('order_date', date('Y-m-d'))
            ->get();

        $results = GetWorkFlowRuleObject('Purchaseorder');
        $approve_need_Purchaseorder = array();
        $approve_need_Purchaseorder_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_Purchaseorder[] = $raw['object']->raw_id;
                $approve_need_Purchaseorder_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }
        //approval
        $Purchaseorder_array = array();
        if (is_object($Purchaseorder)) {
            foreach ($Purchaseorder as $key => $Purchaseorder1) {
                if (in_array($Purchaseorder[$key]->id, $approve_need_Purchaseorder)) {
                    $Purchaseorder[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_Purchaseorder_detail_object_id[$Purchaseorder[$key]->id])) {
                        $Purchaseorder[$key]->objectid = $approve_need_Purchaseorder_detail_object_id[$Purchaseorder[$key]->id];
                    } else {
                        $Purchaseorder[$key]->objectid = '';
                    }
                } else {
                    $Purchaseorder[$key]->need_to_approve = 'no';
                    $Purchaseorder[$key]->objectid = '';
                }

                if ($Purchaseorder[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($Purchaseorder[$key]->id, $approve_need_Purchaseorder)) {
                    $Purchaseorder_array[] = $Purchaseorder[$key];
                }
            }
        }
        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($Purchaseorder_array[$offset])) {
                    $data_array[] = $Purchaseorder_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($Purchaseorder_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($Purchaseorder_array);
        } else {
            $data_array = $Purchaseorder_array;
        }
        return prepareResult(true, $data_array, [], "Purchase order listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating purchase order", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Purchaseorder', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Purchaseorder',$request);
            }

            $purchaseorder = new PurchaseOrder;
            $purchaseorder->vendor_id            = (!empty($request->vendor_id)) ? $request->vendor_id : null;
            $purchaseorder->reference            = (!empty($request->reference)) ? $request->reference : null;
            $purchaseorder->purchase_order            = nextComingNumber('App\Model\PurchaseOrder', 'purchase_order', 'purchase_order', $request->purchase_order);
            $purchaseorder->purchase_order_date       = date('Y-m-d', strtotime($request->purchase_order_date));
            $purchaseorder->expected_delivery_date       = date('Y-m-d', strtotime($request->expected_delivery_date));
            $purchaseorder->customer_note            = (!empty($request->customer_note)) ? $request->customer_note : null;
            $purchaseorder->gross_total            = (!empty($request->gross_total)) ? $request->gross_total : '0.00';
            $purchaseorder->vat_total            = (!empty($request->vat_total)) ? $request->vat_total : '0.00';
            $purchaseorder->excise_total            = (!empty($request->excise_total)) ? $request->excise_total : '0.00';
            $purchaseorder->net_total            = (!empty($request->net_total)) ? $request->net_total : '0.00';
            $purchaseorder->discount_total            = (!empty($request->discount_total)) ? $request->discount_total : '0.00';
            $purchaseorder->save();

            if ($isActivate = checkWorkFlowRule('Purchaseorder', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Purchaseorder', $request, $purchaseorder->id);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $purchaseorderdetail = new PurchaseOrderDetail;
                    $purchaseorderdetail->purchase_order_id      = $purchaseorder->id;
                    $purchaseorderdetail->item_id       = $item['item_id'];
                    $purchaseorderdetail->item_uom_id   = $item['item_uom_id'];
                    $purchaseorderdetail->qty   = $item['qty'];
                    $purchaseorderdetail->price       = $item['price'];
                    $purchaseorderdetail->discount   = $item['discount'];
                    $purchaseorderdetail->vat  = $item['vat'];
                    $purchaseorderdetail->net      = $item['net'];
                    $purchaseorderdetail->excise    = $item['excise'];
                    $purchaseorderdetail->total    = $item['total'];
                    $purchaseorderdetail->save();
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\StockAdjustment', 'purchase_order');

            $purchaseorder->getSaveData();

            return prepareResult(true, $purchaseorder, [], "Purchase order added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating purchase order.", $this->unauthorized);
        }

        $Purchaseorder = PurchaseOrder::with(
            'vendor:id,firstname,lastname,email,company_name',
            'purchaseorderdetail',
            'purchaseorderdetail.item:id,item_name',
            'purchaseorderdetail.itemUom:id,name,code'
        )
            ->where('uuid', $uuid)
            ->first();

        if (is_object($Purchaseorder)) {
            $Purchaseorder->grand_total = $Purchaseorder->net_total;
        }

        if (!is_object($Purchaseorder)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $Purchaseorder, [], "Purchase order Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating purchase order.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Purchaseorder', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Purchaseorder',$request);
            }

            $purchaseorder = PurchaseOrder::where('uuid', $uuid)->first();

            //Delete old record
            Purchaseorderdetail::where('purchase_order_id', $purchaseorder->id)->delete();

            $purchaseorder->vendor_id            = (!empty($request->vendor_id)) ? $request->vendor_id : null;
            $purchaseorder->reference            = (!empty($request->reference)) ? $request->reference : null;
            $purchaseorder->purchase_order            = (!empty($request->purchase_order)) ? $request->purchase_order : null;
            $purchaseorder->purchase_order_date       = date('Y-m-d', strtotime($request->purchase_order_date));
            $purchaseorder->expected_delivery_date       = date('Y-m-d', strtotime($request->expected_delivery_date));
            $purchaseorder->customer_note            = (!empty($request->customer_note)) ? $request->customer_note : null;
            $purchaseorder->gross_total            = (!empty($request->gross_total)) ? $request->gross_total : '0.00';
            $purchaseorder->vat_total            = (!empty($request->vat_total)) ? $request->vat_total : '0.00';
            $purchaseorder->excise_total            = (!empty($request->excise_total)) ? $request->excise_total : '0.00';
            $purchaseorder->net_total            = (!empty($request->net_total)) ? $request->net_total : '0.00';
            $purchaseorder->discount_total            = (!empty($request->discount_total)) ? $request->discount_total : '0.00';
            $purchaseorder->save();

            if ($isActivate = checkWorkFlowRule('Purchaseorder', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Purchaseorder', $request, $purchaseorder->id);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $purchaseorderdetail = new PurchaseOrderDetail;
                    $purchaseorderdetail->purchase_order_id      = $purchaseorder->id;
                    $purchaseorderdetail->item_id       = $item['item_id'];
                    $purchaseorderdetail->item_uom_id   = $item['item_uom_id'];
                    $purchaseorderdetail->qty   = $item['qty'];
                    $purchaseorderdetail->price       = $item['price'];
                    $purchaseorderdetail->discount   = $item['discount'];
                    $purchaseorderdetail->vat  = $item['vat'];
                    $purchaseorderdetail->net      = $item['net'];
                    $purchaseorderdetail->excise    = $item['excise'];
                    $purchaseorderdetail->total    = $item['total'];
                    $purchaseorderdetail->save();
                }
            }

            \DB::commit();

            $purchaseorder->getSaveData();

            return prepareResult(true, $purchaseorder, [], "Purchase order updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating purchase order.", $this->unauthorized);
        }

        $purchaseorder = PurchaseOrder::where('uuid', $uuid)
            ->first();

        if (is_object($purchaseorder)) {
            $purchaseorderId = $purchaseorder->id;
            $purchaseorder->delete();
            if ($purchaseorder) {
                Purchaseorderdetail::where('purchase_order_id', $purchaseorderId)->delete();
            }
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->purchaseorder_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'Pending' || $action == 'Approve') {
            foreach ($uuids as $uuid) {
                PurchaseOrder::where('uuid', $uuid)->update([
                    'status' => ($action == 'Pending') ? 'Pending' : 'Approve'
                ]);
            }
            $purchaseorder = $this->index();
            return prepareResult(true, $purchaseorder, [], "Purchase order status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $purchaseorder = PurchaseOrder::where('uuid', $uuid)
                    ->first();
                $purchaseorderId = $purchaseorder->id;
                $purchaseorder->delete();
                if ($purchaseorder) {
                    Purchaseorderdetail::where('purchase_order_id', $purchaseorderId)->delete();
                }
            }
            $purchaseorder = $this->index();
            return prepareResult(true, $purchaseorder, [], "Purchase order deleted success", $this->success);
        }
    }

    /**
     * approove status.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function approve($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating purchase order.", $this->unauthorized);
        }

        PurchaseOrder::where('uuid', $uuid)->update([
            'status' => 'Approve'
        ]);

        return prepareResult(true, [], [], "Purchase order status updated", $this->success);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'vendor_id' => 'required|integer|exists:vendors,id',
                'reference' => 'required',
                'purchase_order' => 'required',
                'purchase_order_date' => 'required|date',
                'expected_delivery_date' => 'required|date',
                'customer_note' => 'required',
                'gross_total' => 'required',
                'vat_total' => 'required',
                'excise_total' => 'required',
                'net_total' => 'required',
                'discount_total' => 'required',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'purchaseorder_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'purchaseorder_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate Purchase order import", $this->unauthorized);
        }

        Excel::import(new PurchaseorderImport, request()->file('purchaseorder_file'));
        return prepareResult(true, [], [], "Purchase order successfully imported", $this->success);
    }
}
