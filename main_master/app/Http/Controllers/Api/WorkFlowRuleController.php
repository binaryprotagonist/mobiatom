<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\WorkFlowRuleModule;
use App\Model\WorkFlowRule;
use App\Model\WorkFlowRuleApprovalRole;
use App\Model\WorkFlowRuleApprovalUser;


class WorkFlowRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function moduleList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $moduleList = WorkFlowRuleModule::select('id', 'name', 'status')
            ->where('type', '1')
            ->get();

        return prepareResult(true, $moduleList, [], "Work flow module listing.", $this->success);
    }

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

        $workFlowRules = WorkFlowRule::with(
            'workFlowRuleModule:id,name',
            'workFlowRuleApprovalRoles:id,uuid,work_flow_rule_id,organisation_role_id',
            'workFlowRuleApprovalRoles.organisationRole:id,uuid,name,description',
            'workFlowRuleApprovalRoles.workFlowRuleApprovalUsers:id,uuid,wfr_approval_role_id,user_id',
            'workFlowRuleApprovalRoles.workFlowRuleApprovalUsers.user:id,firstname,lastname'
        )
            ->get();

        return prepareResult(true, $workFlowRules, [], "Work flow rule listing.", $this->success);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating work flow rules", $this->unprocessableEntity);
        }

        if (is_array($request->approval_roles) && sizeof($request->approval_roles) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one role.", $this->unprocessableEntity);
        }

        if (WorkFlowRule::where('work_flow_rule_module_id', $request->work_flow_rule_module_id)->where('status', 1)->count() > 0) {
            return prepareResult(false, [], [], "Error!!! Rules have already been created for this module, please remove the existing rule of this module, and try again.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $workflowrule = new WorkFlowRule;
            $workflowrule->work_flow_rule_module_id = $request->work_flow_rule_module_id;
            $workflowrule->work_flow_rule_name  = $request->work_flow_rule_name;
            $workflowrule->description      = $request->description;
            $workflowrule->event_trigger    = $request->event_trigger;
            $workflowrule->status           = $request->status;
            $workflowrule->save();

            if ($workflowrule) {
                foreach ($request->approval_roles as $key => $role) {
                    $appRole = new WorkFlowRuleApprovalRole;
                    $appRole->work_flow_rule_id     = $workflowrule->id;
                    $appRole->organisation_role_id  = $role['organisation_role_id'];
                    $appRole->save();

                    if (is_array($role['users']) && sizeof($role['users']) > 0) {
                        foreach ($role['users'] as $keyUser => $user) {
                            $roleUser = new WorkFlowRuleApprovalUser;
                            $roleUser->work_flow_rule_id     = $workflowrule->id;
                            $roleUser->wfr_approval_role_id  = $appRole->id;
                            $roleUser->user_id               = $user;
                            $roleUser->save();
                        }
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $workflowrule, [], "Work flow rule added successfully", $this->created);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $workFlowRule = WorkFlowRule::with(
            'workFlowRuleModule:id,name',
            'workFlowRuleApprovalRoles:id,uuid,work_flow_rule_id,organisation_role_id',
            'workFlowRuleApprovalRoles.organisationRole:id,uuid,name,description',
            'workFlowRuleApprovalRoles.workFlowRuleApprovalUsers:id,uuid,wfr_approval_role_id,user_id',
            'workFlowRuleApprovalRoles.workFlowRuleApprovalUsers.user:id,firstname,lastname'
        )
            ->where('uuid', $uuid)
            ->first();

        return prepareResult(true, $workFlowRule, [], "Work flow rule listing.", $this->success);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "edit");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating work flow rules", $this->unprocessableEntity);
        }

        if (is_array($request->approval_roles) && sizeof($request->approval_roles) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one role.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $workflowrule = WorkFlowRule::where('uuid', $uuid)->first();
            $workflowrule->work_flow_rule_name  = $request->work_flow_rule_name;
            $workflowrule->description      = $request->description;
            $workflowrule->event_trigger    = $request->event_trigger;
            $workflowrule->status           = $request->status;
            $workflowrule->save();

            //delete old records
            WorkFlowRuleApprovalRole::where('work_flow_rule_id', $workflowrule->id)->delete();
            WorkFlowRuleApprovalUser::where('work_flow_rule_id', $workflowrule->id)->delete();

            if ($workflowrule) {
                foreach ($request->approval_roles as $key => $role) {
                    $appRole = new WorkFlowRuleApprovalRole;
                    $appRole->work_flow_rule_id     = $workflowrule->id;
                    $appRole->organisation_role_id  = $role['organisation_role_id'];
                    $appRole->save();

                    if (is_array($role['users']) && sizeof($role['users']) > 0) {
                        foreach ($role['users'] as $keyUser => $user) {
                            $roleUser = new WorkFlowRuleApprovalUser;
                            $roleUser->work_flow_rule_id     = $workflowrule->id;
                            $roleUser->wfr_approval_role_id  = $appRole->id;
                            $roleUser->user_id               = $user;
                            $roleUser->save();
                        }
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $workflowrule, [], "Work flow rule added successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating work flow rule", $this->unauthorized);
        }

        $workFlowRule = WorkFlowRule::where('uuid', $uuid)
            ->first();

        if (is_object($workFlowRule)) {
            WorkFlowRuleApprovalRole::where('work_flow_rule_id', $workFlowRule->id)->delete();
            WorkFlowRuleApprovalUser::where('work_flow_rule_id', $workFlowRule->id)->delete();
            $workFlowRule->delete();

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
                'work_flow_rule_module_id' => 'required|integer|exists:work_flow_rule_modules,id',
                'work_flow_rule_name'   => 'required',
                'event_trigger'   => 'required'
            ]);
        }

        if ($type == "edit") {
            $validator = \Validator::make($input, [
                'work_flow_rule_name'   => 'required',
                'event_trigger'   => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
