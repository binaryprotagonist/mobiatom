<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CustomFieldValue;
use App\Model\Module;
use App\Model\Route;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleApprovalRole;
use DB;

class CustomFieldValueController extends Controller
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

        $customfieldvalues = CustomFieldValue::with(
            'moduledeials',
            'CustomfieldDetails'

        )->get();
        // approval
        $workFlowRules = WorkFlowObject::select(
            'work_flow_objects.id as id',
            'work_flow_objects.uuid as uuid',
            'work_flow_objects.work_flow_rule_id',
            'work_flow_objects.module_name',
            'work_flow_objects.request_object',
            'work_flow_objects.currently_approved_stage',
            'work_flow_objects.raw_id',
            'work_flow_rules.work_flow_rule_name',
            'work_flow_rules.description',
            'work_flow_rules.event_trigger'
        )
            ->withoutGlobalScope('organisation_id')
            ->join('work_flow_rules', function ($join) {
                $join->on('work_flow_objects.work_flow_rule_id', '=', 'work_flow_rules.id');
            })
            ->where('work_flow_objects.organisation_id', auth()->user()->organisation_id)
            ->where('status', '1')
            ->where('is_approved_all', '0')
            ->where('is_anyone_reject', '0')
            ->where('work_flow_objects.module_name', 'Custom Field Value')
            //->where('work_flow_objects.raw_id',$users[$key]->id)
            ->get();
        $results = [];
        foreach ($workFlowRules as $key => $obj) {
            $checkCondition = WorkFlowRuleApprovalRole::query();
            if ($obj->currently_approved_stage > 0) {
                $checkCondition->skip($obj->currently_approved_stage);
            }
            $getResult = $checkCondition->where('work_flow_rule_id', $obj->work_flow_rule_id)
                ->orderBy('id', 'ASC')
                ->first();
            $userIds = [];
            if (is_object($getResult) && $getResult->workFlowRuleApprovalUsers->count() > 0) {
                //User based approval
                foreach ($getResult->workFlowRuleApprovalUsers as $prepareUserId) {
                    $WorkFlowObjectAction = WorkFlowObjectAction::where('work_flow_object_id', $obj->id)->get();
                    if (is_object($WorkFlowObjectAction)) {
                        $id_arr = [];
                        foreach ($WorkFlowObjectAction as $action) {
                            $id_arr[] = $action->user_id;
                        }
                        if (!in_array($prepareUserId->user_id, $id_arr)) {
                            $userIds[] = $prepareUserId->user_id;
                        }
                    } else {
                        $userIds[] = $prepareUserId->user_id;
                    }
                }

                if (in_array(auth()->id(), $userIds)) {
                    $results[] = [
                        'object'    => $obj,
                        'Action'    => 'User'
                    ];
                }
            } else {
                //Roles based approval
                if (is_object($getResult) && $getResult->organisation_role_id == auth()->user()->role_id)
                    $results[] = [
                        'object'    => $obj,
                        'Action'    => 'Role'
                    ];
            }
        }
        $approve_need_customfieldvalue = array();
        $approve_need_customfieldvalue_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_customfieldvalue[] = $raw['object']->raw_id;
                $approve_need_customfieldvalue_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        if (is_object($customfieldvalues)) {
            foreach ($customfieldvalues as $key => $customfieldvalue) {
                if (in_array($customfieldvalues[$key]->id, $approve_need_customfieldvalue)) {
                    $customfieldvalues[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_customfieldvalue_object_id[$customfieldvalues[$key]->id])) {
                        $customfieldvalues[$key]->objectid = $approve_need_customfieldvalue_object_id[$customfieldvalues[$key]->id];
                    } else {
                        $customfieldvalues[$key]->objectid = '';
                    }
                } else {
                    $customfieldvalues[$key]->need_to_approve = 'no';
                    $customfieldvalues[$key]->objectid = '';
                }
            }
        }
        $customfieldvalues_array = array();
        if (is_object($customfieldvalues)) {
            foreach ($customfieldvalues as $key => $customfieldvalues1) {
                $customfieldvalues_array[] = $customfieldvalues[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($customfieldvalues_array[$offset])) {
                    $data_array[] = $customfieldvalues_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($customfieldvalues_array) / $limit);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $customfieldvalues_array;
        }
        return prepareResult(true, $data_array, [], "Custom field value listing", $this->success, $pagination);
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

        /* $validate = $this->validations($input, "add");
        if($validate["error"]) 
		{
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit not", $this->unprocessableEntity);
        }
        */
        \DB::beginTransaction();
        try {
            $status = 1;
            if ($isActivate = checkWorkFlowRule('Custom Field Value', 'create')) {
                $status = 0;
                //$this->createWorkFlowObject($isActivate, 'Invoice',$request);
            }
            //echo '<pre>';
            //print_r($request->customerFieldDetails);
            //exit;
            if ($request->customerFieldDetails) {
                $finalresult = array();
                foreach ($request->customerFieldDetails as $cfv) {
                    $customfieldvalues = new CustomFieldValue;
                    $customfieldvalues->ModuleId = (!empty($request->ModuleId)) ? $request->ModuleId : null;
                    $customfieldvalues->ModuleType = (!empty($request->ModuleType)) ? $request->ModuleType : null;
                    $customfieldvalues->CustimFieldId = $cfv['CustimFieldId'];
                    $customfieldvalues->CustomFieldValue = $cfv['CustomFieldValue'];
                    $customfieldvalues->status = $status;
                    $customfieldvalues->save();

                    $finalresult[] = $customfieldvalues;
                }
            }

            \DB::commit();
            return prepareResult(true, $finalresult, [], "Custom field value successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }
    public function getmoduledetails(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "moduletype");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit not", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            //$customfields = CustomFieldValue::where('ModuleId', $request->ModuleId)->where('ModuleType',$request->ModuleType)->get();

            $customfields = DB::table('custom_field_values as cfv')
                ->select('cf.id as CustimFieldId', 'cfv.uuid', 'cf.FieldType', 'cf.FieldLabel', 'cf.FieldValue', 'cfv.CustomFieldValue', 'ms.module_name', 'cf.organisation_id as organisation_id')
                ->join('custom_fields as cf', 'cf.id', '=', 'cfv.CustimFieldId')
                ->join('modules as m', 'm.id', '=', 'cfv.ModuleType', 'left')
                ->join('module_masters as ms', 'ms.id', '=', 'm.moduleMasterId', 'left')
                ->where('cfv.ModuleId', $request->ModuleId)
                ->where('cf.organisation_id', $request->organisation_id)
                ->where('cfv.ModuleType', $request->ModuleType)->get();

            if (!is_object($customfields)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
            }

            return prepareResult(true, $customfields, [], "Custom field value get successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
        }

        $customfields = CustomFieldValue::where('uuid', $uuid)->first();

        if (!is_object($customfields)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $customfields, [], "Custom Field Value Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating sales target.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $customfieldvalues = CustomFieldValue::where('uuid', $uuid)->first();
            $customfieldvalues->ModuleType = (!empty($request->ModuleType)) ? $request->ModuleType : null;
            $customfieldvalues->CustimFieldId = (!empty($request->CustimFieldId)) ? $request->CustimFieldId : null;
            $customfieldvalues->CustomFieldValue = (!empty($request->CustomFieldValue)) ? $request->CustomFieldValue : null;
            $customfieldvalues->save();

            \DB::commit();
            return prepareResult(true, $customfieldvalues, [], "Custom field value updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
        }
        $customfieldvalues = CustomFieldValue::where('uuid', $uuid)
            ->first();
        if (is_object($customfieldvalues)) {
            $customfieldvaluesId = $customfieldvalues->id;
            $customfieldvalues->delete();

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
        $uuids = $request->custom_field_value_ids;
        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }
        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                CustomFieldValue::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $customfieldvalues = $this->index();
            return prepareResult(true, $customfieldvalues, [], "Custom field value status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $customfieldvalues = CustomFieldValue::where('uuid', $uuid)
                    ->first();
                $customfieldsId = $customfieldvalues->id;
                $customfieldvalues->delete();
            }
            $customfieldvalues = $this->index();
            return prepareResult(true, $customfieldvalues, [], "Custom field value deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [

                'ModuleId' => 'required',
                'ModuleType' => 'required',
                'CustimFieldId' => 'required',
                'CustomFieldValue' => 'required',
            ]);
        }
        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'custom_field_value_ids' => 'required'
            ]);
        }
        if ($type == 'moduletype') {
            $validator = \Validator::make($input, [
                'ModuleId' => 'required',
                'ModuleType' => 'required'
            ]);
        }
        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
