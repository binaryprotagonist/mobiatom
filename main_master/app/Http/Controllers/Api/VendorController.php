<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\VendorImport;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Vendor;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
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

        $Vendor = Vendor::with(
            'organisation:id,org_name',
            'customFieldValueSave',
            'customFieldValueSave.customField'
        )
        ->orderBy('id', 'desc')
        ->get();

        $results = GetWorkFlowRuleObject('Vendor');
        $approve_need_vendor = array();
        $approve_need_vendor_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_vendor[] = $raw['object']->raw_id;
                $approve_need_vendor_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }
        //approval
        $vendor_array = array();
        if (is_object($Vendor)) {
            foreach ($Vendor as $key => $Vendor1) {
                if (in_array($Vendor[$key]->id, $approve_need_vendor)) {
                    $Vendor[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_vendor_detail_object_id[$Vendor[$key]->id])) {
                        $Vendor[$key]->objectid = $approve_need_vendor_detail_object_id[$Vendor[$key]->id];
                    } else {
                        $Vendor[$key]->objectid = '';
                    }
                } else {
                    $Vendor[$key]->need_to_approve = 'no';
                    $Vendor[$key]->objectid = '';
                }

                if ($Vendor[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($Vendor[$key]->id, $approve_need_vendor)) {
                    $vendor_array[] = $Vendor[$key];
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
                if (isset($vendor_array[$offset])) {
                    $data_array[] = $vendor_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($vendor_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($vendor_array);
        } else {
            $data_array = $vendor_array;
        }

        return prepareResult(true, $data_array, [], "Vendor listing", $this->success, $pagination);

        // return prepareResult(true, $vendor_array, [], "Vendor listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating vendor", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Vendor', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Vendor',$request);
            }

            $vendor = new Vendor;
            $vendor->vender_code         = nextComingNumber('App\Model\Vendor', 'vendor', 'vender_code', $request->vender_code);
            $vendor->firstname            = (!empty($request->firstname)) ? $request->firstname : null;
            $vendor->lastname            = (!empty($request->lastname)) ? $request->lastname : null;
            $vendor->email       = (!empty($request->email)) ? $request->email : null;
            $vendor->company_name        = (!empty($request->company_name)) ? $request->company_name : null;
            $vendor->mobile        = (!empty($request->mobile)) ? $request->mobile : null;
            $vendor->website        = (!empty($request->website)) ? $request->website : null;
            $vendor->address1        = (!empty($request->address1)) ? $request->address1 : null;
            $vendor->address2        = (!empty($request->address2)) ? $request->address2 : null;
            $vendor->city        = (!empty($request->city)) ? $request->city : null;
            $vendor->state        = (!empty($request->state)) ? $request->state : null;
            $vendor->zip        = (!empty($request->zip)) ? $request->zip : null;
            $vendor->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($vendor->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            if ($isActivate = checkWorkFlowRule('Vendor', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Vendor', $request, $vendor->id);
            }

            \DB::commit();
            updateNextComingNumber('App\Model\Vendor', 'vendor');

            return prepareResult(true, $vendor, [], "Vendor added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating vendor.", $this->unauthorized);
        }
        $Vendor = Vendor::with(
            'organisation:id,org_name',
            'customFieldValueSave',
            'customFieldValueSave.customField'
        )
            ->where('uuid', $uuid)
            ->orderBy('id', 'desc')
            ->get();

        if (!is_object($Vendor)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $Vendor, [], "Vendor Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating vendor.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Vendor', 'edit', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Vendor',$request);
            }

            $vendor = Vendor::where('uuid', $uuid)->first();
            $vendor->vender_code         = (!empty($request->vender_code)) ? $request->vender_code : null;
            $vendor->firstname            = (!empty($request->firstname)) ? $request->firstname : null;
            $vendor->lastname            = (!empty($request->lastname)) ? $request->lastname : null;
            $vendor->email       = (!empty($request->email)) ? $request->email : null;
            $vendor->company_name        = (!empty($request->company_name)) ? $request->company_name : null;
            $vendor->mobile        = (!empty($request->mobile)) ? $request->mobile : null;
            $vendor->website        = (!empty($request->website)) ? $request->website : null;
            $vendor->address1        = (!empty($request->address1)) ? $request->address1 : null;
            $vendor->address2        = (!empty($request->address2)) ? $request->address2 : null;
            $vendor->city        = (!empty($request->city)) ? $request->city : null;
            $vendor->state        = (!empty($request->state)) ? $request->state : null;
            $vendor->zip        = (!empty($request->zip)) ? $request->zip : null;
            $vendor->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                CustomFieldValueSave::where('record_id', $vendor->id)->delete();
                foreach ($request->modules as $module) {
                    savecustomField($vendor->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            if ($isActivate = checkWorkFlowRule('Vendor', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Vendor', $request, $vendor->id);
            }

            \DB::commit();

            return prepareResult(true, $vendor, [], "Vendor updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating vendor.", $this->unauthorized);
        }

        $vendor = Vendor::where('uuid', $uuid)->first();

        if (is_object($vendor)) {
            $vendor->delete();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating vendor", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->vendor_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $vendor = Vendor::where('uuid', $uuid)->first();
                $vendor->delete();
            }
            $vendor = $this->index();
            return prepareResult(true, $vendor, [], "Vendor deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'vender_code' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'email' => 'required|email|unique:vendors,email',
                'company_name' => 'required',
                'mobile' => 'required',
                'city' => 'required',
                'state' => 'required'
            ]);
        }
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'vender_code' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'company_name' => 'required',
                'mobile' => 'required',
                'city' => 'required',
                'state' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'vendor_ids'     => 'required'
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
            'vendor_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate vendor import", $this->unauthorized);
        }

        $errors = array();
        try {
            $file = request()->file('vendor_file')->store('import');
            $import = new VendorImport($request->skipduplicate);
            $import->import($file);
            $errors[] = $import->failures();
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

        //Excel::import(new VendorImport, request()->file('vendor_file'));
        return prepareResult(true, [], $errors, "Vendor successfully imported", $this->success);
    }
}
