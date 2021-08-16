<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\EstimationImport;
use Illuminate\Http\Request;
use App\Model\Estimation;
use App\Model\EstimationDetail;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class EstimationController extends Controller
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

        $Estimation = Estimation::with(array('customerInfo.user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customerInfo:id,user_id',
                'organisation:id,org_name',
                'salesperson:id,name,email',
                'estimationdetail',
                'estimationdetail.item:id,item_name',
                'estimationdetail.itemUom:id,name'
            )
            //->where('order_date', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        $Estimation_array = array();
        if (is_object($Estimation)) {
            foreach ($Estimation as $key => $Estimation1) {
                $Estimation_array[] = $Estimation[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($Estimation_array[$offset])) {
                    $data_array[] = $Estimation_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($Estimation_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($Estimation_array);
        } else {
            $data_array = $Estimation_array;
        }
        return prepareResult(true, $data_array, [], "Estimation listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Estimation", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $estimation = new Estimation;
            $estimation->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $estimation->reference            = (!empty($request->reference)) ? $request->reference : null;
            $estimation->estimate_code            = nextComingNumber('App\Model\Estimation', 'estimate', 'estimate_code', $request->estimate_code);
            $estimation->estimate_date       = date('Y-m-d', strtotime($request->estimate_date));
            $estimation->expairy_date       = date('Y-m-d', strtotime($request->expairy_date));
            $estimation->salesperson_id            = (!empty($request->salesperson_id)) ? $request->salesperson_id : null;
            $estimation->subject            = (!empty($request->subject)) ? $request->subject : null;
            $estimation->customer_note            = (!empty($request->customer_note)) ? $request->customer_note : null;
            $estimation->gross_total     = $request->gross_total;
            $estimation->vat            = $request->vat;
            $estimation->exise           = $request->exise;
            $estimation->net_total         = $request->net_total;
            $estimation->discount   = $request->discount;
            $estimation->total           = $request->total;
            $estimation->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $estimationDetail = new EstimationDetail;
                    $estimationDetail->estimation_id      = $estimation->id;
                    $estimationDetail->item_id       = $item['item_id'];
                    $estimationDetail->item_uom_id   = $item['item_uom_id'];
                    $estimationDetail->item_qty   = $item['item_qty'];
                    $estimationDetail->item_price       = $item['item_price'];
                    $estimationDetail->item_discount_amount   = $item['item_discount_amount'];
                    $estimationDetail->item_vat  = $item['item_vat'];
                    $estimationDetail->item_excise      = $item['item_excise'];
                    $estimationDetail->item_grand_total    = $item['item_grand_total'];
                    $estimationDetail->item_net    = $item['item_net'];
                    $estimationDetail->save();
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\Estimation', 'estimate');

            $estimation->getSaveData();

            return prepareResult(true, $estimation, [], "Estimation added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating estimation.", $this->unauthorized);
        }

        $Estimation = Estimation::with(array('customerInfo.user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customerInfo:id,user_id',
                'organisation:id,org_name',
                'salesperson:id,name,email',
                'estimationdetail',
                'estimationdetail.item:id,item_name',
                'estimationdetail.itemUom:id,name'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($Estimation)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $Estimation, [], "Estimation Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating estimation.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {

            $estimation = Estimation::where('uuid', $uuid)->first();

            //Delete old record
            EstimationDetail::where('estimation_id', $estimation->id)->delete();

            $estimation->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $estimation->reference            = (!empty($request->reference)) ? $request->reference : null;
            $estimation->estimate_code            = (!empty($request->estimate_code)) ? $request->estimate_code : null;
            $estimation->estimate_date       = date('Y-m-d', strtotime($request->estimate_date));
            $estimation->expairy_date       = date('Y-m-d', strtotime($request->expairy_date));
            $estimation->salesperson_id            = (!empty($request->salesperson_id)) ? $request->salesperson_id : null;
            $estimation->subject            = (!empty($request->subject)) ? $request->subject : null;
            $estimation->customer_note            = (!empty($request->customer_note)) ? $request->customer_note : null;
            $estimation->gross_total     = $request->gross_total;
            $estimation->vat            = $request->vat;
            $estimation->exise           = $request->exise;
            $estimation->net_total         = $request->net_total;
            $estimation->discount   = $request->discount;
            $estimation->total           = $request->total;
            $estimation->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $estimationDetail = new EstimationDetail;
                    $estimationDetail->estimation_id      = $estimation->id;
                    $estimationDetail->item_id       = $item['item_id'];
                    $estimationDetail->item_uom_id   = $item['item_uom_id'];
                    $estimationDetail->item_qty   = $item['item_qty'];
                    $estimationDetail->item_price       = $item['item_price'];
                    $estimationDetail->item_discount_amount   = $item['item_discount_amount'];
                    $estimationDetail->item_vat  = $item['item_vat'];
                    $estimationDetail->item_excise      = $item['item_excise'];
                    $estimationDetail->item_grand_total    = $item['item_grand_total'];
                    $estimationDetail->item_net    = $item['item_net'];
                    $estimationDetail->save();
                }
            }

            \DB::commit();

            $estimation->getSaveData();

            return prepareResult(true, $estimation, [], "Estimation updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating estimation.", $this->unauthorized);
        }

        $estimation = Estimation::where('uuid', $uuid)
            ->first();

        if (is_object($estimation)) {
            $estimationId = $estimation->id;
            $estimation->delete();
            if ($estimation) {
                EstimationDetail::where('estimation_id', $estimationId)->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating estimation", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->estimation_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $estimation = Estimation::where('uuid', $uuid)
                    ->first();
                $estimationId = $estimation->id;
                $estimation->delete();
                if ($estimation) {
                    EstimationDetail::where('estimation_id', $estimationId)->delete();
                }
            }
            $estimation = $this->index();
            return prepareResult(true, $estimation, [], "Estimation deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'customer_id' => 'required|integer',
                'reference' => 'required',
                'estimate_code' => 'required',
                'estimate_date' => 'required:date',
                'expairy_date' => 'required',
                'salesperson_id' => 'required|integer',
                'subject' => 'required',
                'customer_note' => 'required',
                'gross_total' => 'required',
                'vat' => 'required',
                'exise' => 'required',
                'net_total' => 'required',
                'discount' => 'required',
                'total' => 'required',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'estimation_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Get price specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $item_id, $item_uom_id, $item_qty
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'estimation_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate estimation import", $this->unauthorized);
        }
        $errors = array();
        try {
            //Excel::import(new EstimationImport, request()->file('estimation_file'));
            $file = request()->file('estimation_file')->store('import');
            $import = new EstimationImport($request->skipduplicate);
            $import->import($file);
            if (count($import->failures()) > 46) {
                $errors[] = $import->failures();
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                info($failure->row());
                info($failure->attribute());
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
                $errors[] = $failure->errors();
            }

            return prepareResult(true, [], $errors, "Failed to validate bank import", $this->success);
        }
        //Excel::import(new EstimationImport, request()->file('estimation_file'));
        return prepareResult(true, [], $errors, "Estimation successfully imported", $this->success);
    }
}
