<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DeviceDetail;
use Illuminate\Http\Request;
use App\Model\LoadRequest;
use App\Model\LoadRequestDetail;
use App\Model\Route;
use App\Model\Warehouse;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\SalesmanInfo;
use App\Model\SalesmanNumberRange;
use App\User;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;
use App\Model\SalesmanLoad;
use App\Model\SalesmanLoadDetails;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleApprovalUser;
use Ixudra\Curl\Facades\Curl;
use Psy\Util\Json;

class LoadrequestController extends Controller
{
    /**
     * Display a listing of the resource.  status is Pending
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $LoadRequest_query = LoadRequest::with(
            'Salesman:id,firstname,lastname',
            'Salesman.salesmanInfo:user_id,salesman_code',
            'Route:id,route_name,route_code,depot_id',
            'Route.depot:id,depot_code,depot_name',
            'LoadRequestDetail',
            'LoadRequestDetail.Item:id,item_name,item_code',
            'LoadRequestDetail.ItemUom:id,name',
            'trip'
        );

        if ($request->date) {
            $LoadRequest_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->load_number) {
            $LoadRequest_query->where('load_number', 'like', '%' . $request->load_number . '%');
        }

        if ($request->load_type) {
            $LoadRequest_query->where('load_type', 'like', '%' . $request->load_type . '%');
        }

        if ($request->current_stage) {
            $LoadRequest_query->where('current_stage', 'like', '%' . $request->current_stage . '%');
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $LoadRequest_query->whereHas('Salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $LoadRequest_query->whereHas('Salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->salesman_code) {
            $salesman_code = $request->salesman_code;
            $LoadRequest_query->whereHas('Salesman.salesmanInfo', function ($q) use ($salesman_code) {
                $q->where('salesman_code', 'like', $salesman_code);
            });
        }

        if ($request->route) {
            $route = $request->route;
            $LoadRequest_query->whereHas('Route', function ($q) use ($route) {
                $q->where('route_name', 'like', '%' . $route . '%');
            });
        }

        $LoadRequest = $LoadRequest_query->orderBy('id', 'desc')
            ->get();


        $results = GetWorkFlowRuleObject('Load Request');
        $approve_need_LoadRequest = array();
        $approve_need_LoadRequest_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_LoadRequest[] = $raw['object']->raw_id;
                $approve_need_LoadRequest_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $LoadRequest_array = array();
        if (is_object($LoadRequest)) {
            foreach ($LoadRequest as $key => $user1) {
                if (in_array($LoadRequest[$key]->id, $approve_need_LoadRequest)) {
                    $LoadRequest[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_LoadRequest_object_id[$LoadRequest[$key]->id])) {
                        $LoadRequest[$key]->objectid = $approve_need_LoadRequest_object_id[$LoadRequest[$key]->id];
                    } else {
                        $LoadRequest[$key]->objectid = '';
                    }
                } else {
                    $LoadRequest[$key]->need_to_approve = 'no';
                    $LoadRequest[$key]->objectid = '';
                }

                if ($LoadRequest[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($LoadRequest[$key]->id, $approve_need_LoadRequest)) {
                    $LoadRequest_array[] = $LoadRequest[$key];
                }
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($LoadRequest_array[$offset])) {
                    $data_array[] = $LoadRequest_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($LoadRequest_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($LoadRequest_array);
        } else {
            $data_array = $LoadRequest_array;
        }
        return prepareResult(true, $data_array, [], "Load Request listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.  status is Approved
     *
     * @return \Illuminate\Http\Response
     */
    public function getApproveData()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $LoadRequest = LoadRequest::with(
            'Salesman:id,firstname,lastname',
            'Route:id,route_name',
            'LoadRequestDetail',
            'LoadRequestDetail.Item:id,item_name',
            'LoadRequestDetail.ItemUom:id,name'
        )
            ->where('status', 'Approved')
            ->orderBy('id', 'desc')
            ->get();

        $LoadRequest_array = array();
        if (is_object($LoadRequest)) {
            foreach ($LoadRequest as $key => $LoadRequest1) {
                $LoadRequest_array[] = $LoadRequest[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($LoadRequest_array[$offset])) {
                    $data_array[] = $LoadRequest_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($LoadRequest_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($LoadRequest_array);
        } else {
            $data_array = $LoadRequest_array;
        }


        return prepareResult(true, $data_array, [], "Load Request Approved listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load request", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Load Request', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Customer',$request);
            }

            $loadrequest = new LoadRequest;
            $loadrequest->route_id = (!empty($request->route_id)) ? $request->route_id : null;
            $loadrequest->salesman_id = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $loadrequest->trip_id = (!empty($request->trip_id)) ? $request->trip_id : null;
            $loadrequest->load_number = (!empty($request->load_number)) ? $request->load_number : null;
            $loadrequest->load_type = (!empty($request->load_type)) ? $request->load_type : null;
            $loadrequest->load_date = date('Y-m-d', strtotime($request->load_date));
            $loadrequest->status = (!empty($request->status)) ? $request->status : null;
            $loadrequest->source = (!empty($request->source)) ? $request->source : 3;
            $loadrequest->current_stage = $current_stage;
            $loadrequest->approval_status = "Created";
            $loadrequest->save();

            // if mobile order
            if (is_object($loadrequest) && $loadrequest->source == 1) {
                $user = User::find($request->user()->id);
                if (is_object($user)) {
                    $salesmanInfo = $user->salesmanInfo;
                    $smr = SalesmanNumberRange::where('salesman_id', $salesmanInfo->id)->first();
                    $smr->order_from = $request->order_number;
                    $smr->save();
                }
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $LoadRequestDetail = new LoadRequestDetail;
                    $LoadRequestDetail->load_request_id = $loadrequest->id;
                    $LoadRequestDetail->item_id = $item['item_id'];
                    $LoadRequestDetail->item_uom_id = $item['item_uom_id'];
                    $LoadRequestDetail->requested_item_uom_id = $item['item_uom_id'];
                    $LoadRequestDetail->qty = $item['qty'];
                    $LoadRequestDetail->requested_qty = $item['qty'];
                    $LoadRequestDetail->save();
                }
            }

            // if mobile order
            if (is_object($loadrequest) && $loadrequest->source == 1) {
                $user = User::find($request->user()->id);
                if (is_object($user)) {
                    $salesmanInfo = $user->salesmanInfo;
                    $smr = SalesmanNumberRange::where('salesman_id', $salesmanInfo->id)->first();
                    $smr->order_from = $request->load_number;
                    $smr->save();

                    if ($request->salesman_id) {
                        $dataNofi = array(
                            'message' => "Load Request Created, Load Number is " . $loadrequest->load_number,
                            'title' => "Load Request created by " . $request->user()->firstname,
                            'noti_type' => "Load Request"
                        );

                        $supervisor = SalesmanInfo::where('user_id', $request->salesman_id)->first();

                        $device_detail = DeviceDetail::where('user_id', $supervisor->salesman_supervisor)
                            ->orderBy('id', 'desc')
                            ->first();

                        // $device_detail->each(function ($token, $key) use ($dataNofi) {
                        // });

                        if (is_object($device_detail)) {
                            $t = $device_detail->device_token;
                            sendNotificationAndroid($dataNofi, $t);
                        }


                        $d = array(
                            'uuid' => $loadrequest->uuid,
                            'user_id' => $salesmanInfo->salesman_supervisor,
                            'type' => 'Load Request',
                            'message' => "Load Request Created, Load Number is " . $loadrequest->load_number,
                            'status' => 1
                        );
                        saveNotificaiton($d);
                    }
                }
            }

            if ($isActivate = checkWorkFlowRule('Load Request', 'create', $loadrequest->organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Load Request', $request, $loadrequest);
            }

            \DB::commit();
            $loadrequest->getSaveData();
            return prepareResult(true, $loadrequest, [], "Load Request added successfully", $this->created);
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
     * @param int $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating load request.", $this->unauthorized);
        }

        $LoadRequest = LoadRequest::with(
            'Salesman:id,firstname,lastname',
            'Salesman.salesmanInfo:user_id,salesman_code',
            'Route:id,route_name,route_code,depot_id',
            'Route.depot:id,depot_code,depot_name',
            'LoadRequestDetail',
            'LoadRequestDetail.Item:id,item_name,item_code,lower_unit_uom_id',
            'LoadRequestDetail.Item.itemUomLowerUnit:id,name',
            'LoadRequestDetail.Item.itemMainPrice',
            'LoadRequestDetail.Item.itemMainPrice.itemUom:id,name',
            'LoadRequestDetail.ItemUom:id,name',
            'trip'
        )
            ->where('uuid', $uuid)
            ->whereHas('LoadRequestDetail', function ($q) {
                $q->where('is_delete', 0);
            })
            ->first();

        if (!is_object($LoadRequest)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $LoadRequest, [], "Load Request Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load request.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Load Request', 'edit', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Customer',$request);
            }

            $loadrequest = LoadRequest::where('uuid', $uuid)->first();
            $loadrequest->route_id = (!empty($request->route_id)) ? $request->route_id : null;
            $loadrequest->salesman_id = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $loadrequest->trip_id = (!empty($request->trip_id)) ? $request->trip_id : null;
            $loadrequest->load_number = (!empty($request->load_number)) ? $request->load_number : null;
            $loadrequest->load_type = (!empty($request->load_type)) ? $request->load_type : null;
            $loadrequest->load_date = date('Y-m-d', strtotime($request->load_date));
            $loadrequest->status = (!empty($request->status)) ? $request->status : null;
            $loadrequest->current_stage = $current_stage;
            $loadrequest->approval_status = "Updated";
            $loadrequest->save();

            LoadRequestDetail::where('load_request_id', $loadrequest->id)->update([
                'is_delete' => 1
            ]);

            if (is_array($request->items)) {
                foreach ($request->items as $item) {

                    $LoadRequestDetail = LoadRequestDetail::where('load_request_id', $loadrequest->id)
                        ->where('item_id', $item['item_id'])
                        ->where('item_uom_id', $item['item_uom_id'])
                        ->first();

                    if (!is_object($LoadRequestDetail)) {
                        $LoadRequestDetail = new LoadRequestDetail;
                    }

                    $LoadRequestDetail->load_request_id = $loadrequest->id;
                    $LoadRequestDetail->item_id = $item['item_id'];
                    $LoadRequestDetail->item_uom_id = $item['item_uom_id'];
                    $LoadRequestDetail->requested_item_uom_id = $item['item_uom_id'];
                    $LoadRequestDetail->qty = $item['qty'];
                    $LoadRequestDetail->requested_qty = $item['requested_qty'];
                    $LoadRequestDetail->is_delete = 0;
                    $LoadRequestDetail->save();
                }
            }

            $LoadRequestDetail = LoadRequestDetail::where('load_request_id', $loadrequest->id)
                ->where('is_delete', 1)
                ->get();

            if (count($LoadRequestDetail)) {
                foreach ($LoadRequestDetail as $key => $detail) {
                    $detail->qty = 0;
                    $detail->save();
                }
            }

            if (is_object($loadrequest) && $loadrequest->source == 1) {
                $user = User::find($request->user()->id);
                if (is_object($user)) {

                    $dataNofi = array(
                        'message' => "Your Load Request " . $loadrequest->load_number . " is updated by " . $request->user()->firstname,
                        'title' => "Load Request",
                        'noti_type' => "load_request",
                        "uuid" => $uuid
                    );

                    $device_detail = DeviceDetail::where('user_id', $loadrequest->salesman_id)
                        ->orderBy('id', 'desc')
                        ->first();
                    // $device_detail->each(function ($token, $key) use ($dataNofi) {
                    // });
                    if (is_object($device_detail)) {

                        $t = $device_detail->device_token;
                        sendNotificationAndroid($dataNofi, $t);
                    }

                    $d = array(
                        'uuid' => $loadrequest->uuid,
                        'user_id' => $loadrequest->salesman_id,
                        'type' => 'Load Request',
                        'message' => "Your Load Request " . $loadrequest->load_number . " is updated by " . $request->user()->firstname,
                        'status' => 1
                    );

                    saveNotificaiton($d);
                }
            }

            if ($isActivate = checkWorkFlowRule('Load Request', 'edit')) {
                $this->createWorkFlowObject($isActivate, 'Load Request', $request, $loadrequest);
            }

            \DB::commit();
            $loadrequest->getSaveData();
            return prepareResult(true, $loadrequest, [], "Load Request updated successfully", $this->created);
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
     * @param int $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating load request.", $this->unauthorized);
        }

        $LoadRequest = LoadRequest::where('uuid', $uuid)
            ->first();

        if (is_object($LoadRequest)) {
            $LoadRequestId = $LoadRequest->id;
            $LoadRequest->delete();
            if ($LoadRequest) {
                LoadRequestDetail::where('load_request_id', $LoadRequestId)->delete();
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
     * @param \Illuminate\Http\Request $request
     * @param array int  $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load request", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->loadrequest_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $loadrequest = LoadRequest::where('uuid', $uuid)
                    ->first();
                $loadrequestId = $loadrequest->id;
                $loadrequest->delete();
                if ($loadrequest) {
                    LoadRequestDetail::where('load_request_id', $loadrequestId)->delete();
                }
            }
            $loadrequest = $this->index();
            return prepareResult(true, $loadrequest, [], "Load request deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'route_id' => 'required|integer',
                'salesman_id' => 'required|integer',
                'load_number' => 'required',
                'load_type' => 'required',
                'load_date' => 'required|date',
                'status' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'loadrequest_ids' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$request->uuid) {
            return prepareResult(false, [], [], "Error while validating load request.", $this->unauthorized);
        }
        $uuid = $request->uuid;

        \DB::beginTransaction();
        try {
            $actionPerformed = WorkFlowObject::where('uuid', $uuid)
                ->first();

            if (is_object($actionPerformed)) {
                if (request()->user()->usertype == 1) {
                    if ($request->action == 1) {
                        if (is_object($actionPerformed->workFlowRule->workFlowRuleApprovalUsers)) {
                            foreach ($actionPerformed->workFlowRule->workFlowRuleApprovalUsers as $approve_user) {
                                $actionPerformed->currently_approved_stage = $actionPerformed->currently_approved_stage + 1;
                            }

                            $LoadRequest = $this->loadApprove($request, $actionPerformed->raw_id);

                            if (isset($LoadRequest->id)) {
                                \DB::commit();
                                return prepareResult(true, $LoadRequest, [], "Load Request Approved and Load created", $this->success);
                            } else {
                                $load_encode = json_decode(json_encode($LoadRequest, true));
                                \DB::rollback();
                                return prepareResult(false, [], [], $load_encode->original->errors->error, $this->internal_server_error);
                            }
                        }
                    } else {
                        $actionPerformed->is_anyone_reject = 1;
                    }
                    $actionPerformed->save();

                    if (is_object($actionPerformed->workFlowRule->workFlowRuleApprovalUsers)) {
                        foreach ($actionPerformed->workFlowRule->workFlowRuleApprovalUsers as $approve_user) {
                            //Add log
                            $addLog = new WorkFlowObjectAction;
                            $addLog->work_flow_object_id = $actionPerformed->id;
                            $addLog->user_id = $approve_user->user_id;
                            $addLog->approved_or_rejected = $request->action;
                            $addLog->save();
                        }
                    }
                } else {
                    if ($request->action == 1) {
                        $actionPerformed->currently_approved_stage = $actionPerformed->currently_approved_stage + 1;
                    } else {
                        $actionPerformed->is_anyone_reject = 1;
                    }
                    $actionPerformed->save();

                    //Add log
                    $addLog = new WorkFlowObjectAction;
                    $addLog->work_flow_object_id = $actionPerformed->id;
                    $addLog->user_id = auth()->id();
                    $addLog->approved_or_rejected = $request->action;
                    $addLog->save();
                }

                $totalLevelDefine = $actionPerformed->workFlowRule->workFlowRuleApprovalRoles->count();
                $countActionTotal = $actionPerformed->workFlowObjectActions->count();

                if ($totalLevelDefine <= $countActionTotal) {
                    $actionPerformed->is_approved_all = 1;
                    $actionPerformed->save();

                    $getObj = $actionPerformed->request_object;
                    if ($actionPerformed->workFlowRule->event_trigger == 'deleted') {
                        //delete logic here according to module
                    } else {

                        if ($request->action == 1) {
                            $load_request = LoadRequest::find($actionPerformed->raw_id);
                            $load_request->status = 'Approved';
                            $load_request->save();

                            if ($totalLevelDefine == $countActionTotal) {
                                $LoadRequest = $this->loadApprove($request, $load_request->id);

                                if (isset($LoadRequest->id)) {
                                    \DB::commit();
                                    return prepareResult(true, $LoadRequest, [], "Load Request Approved and Load created", $this->success);
                                } else {
                                    $load_encode = json_decode(json_encode($LoadRequest, true));
                                    \DB::rollback();
                                    return prepareResult(false, [], [], $load_encode->original->errors->error, $this->internal_server_error);
                                }
                            }
                        } else {
                            $LoadRequest = LoadRequest::find($actionPerformed->raw_id);
                            $LoadRequest->status = 'Rejected';
                            $LoadRequest->save();

                            \DB::commit();
                            return prepareResult(true, $LoadRequest, [], "Load Request Rejected", $this->success);
                        }
                    }
                }

            }

            \DB::commit();
            return prepareResult(true, $actionPerformed, [], "Load Request Approved", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function loadRequestPostOdoo($id)
    {
        $load = LoadRequest::with(
            'Salesman:id,firstname,lastname',
            'Salesman.salesmanInfo:user_id,salesman_code',
            'Route:id,route_name,route_code,depot_id',
            'Route.depot:id,depot_code,depot_name',
            'LoadRequestDetail',
            'LoadRequestDetail.Item:id,item_name,item_code',
            'LoadRequestDetail.ItemUom:id,name',
            'trip'
        )->find($id);

        $response = Curl::to('http://rfctest.dyndns.org:11214/api/create/stockpicking')
            ->withData(array('params' => $load))
            ->asJson(true)
            ->post();
        if (isset($response['result'])) {
            $data = json_decode($response['result']);
            if ($data->response[0]->state == "success") {
                $load->oddo_post_id = $data->response[0]->stockpicking_id;
            } else {
                $load->odoo_failed_response = $response['result'];
            }
        }

        if (isset($response['error'])) {
            $load->odoo_failed_response = $response['error'];
        }

        $load->save();

        if (!empty($load->oddo_post_id)) {
            return prepareResult(true, $load, [], "Load Request posted sucessfully", $this->success);
        }

        return prepareResult(false, $load, [], "Load Request not posted", $this->unprocessableEntity);
    }

    public function loadRequestGenerate(Request $request, $id)
    {
        $LoadRequest = $this->loadApprove($request, $id, true);

        if (isset($LoadRequest->id)) {

            $this->loadRequestPostOdoo($LoadRequest->id);

            $dataNofi = array(
                'message' => "The Load Request " . $LoadRequest->load_number . " is genererated by " . $request->user()->firstname,
                'title' => "Load Request",
                'noti_type' => "load_request",
                "uuid" => $LoadRequest->uuid
            );

            $device_detail = DeviceDetail::where('user_id', $LoadRequest->salesman_id)
                ->orderBy('id', 'desc')
                ->first();

            if (is_object($device_detail)) {

                $t = $device_detail->device_token;
                sendNotificationAndroid($dataNofi, $t);
            }

            $d = array(
                'uuid' => $LoadRequest->uuid,
                'user_id' => $LoadRequest->salesman_id,
                'type' => 'Load Request',
                'message' => "The Load Request " . $LoadRequest->load_number . " is generated by " . $request->user()->firstname,
                'status' => 1
            );

            saveNotificaiton($d);

            return prepareResult(true, $LoadRequest, [], "Load Request Approved and Load created", $this->success);
        } else {
            $load_encode = json_decode(json_encode($LoadRequest, true));
            return prepareResult(false, [], [], $load_encode->original->errors->error, $this->internal_server_error);
        }
    }


    private function loadApprove($request, $id, $generate = false)
    {
        $LoadRequest = LoadRequest::with(
            'Salesman:id,firstname,lastname',
            'Route:id,route_name',
            'LoadRequestDetail:id,uuid,load_request_id,item_id,item_uom_id,qty,requested_qty,requested_item_uom_id'
        )
            ->where('id', $id)
            ->first();

        // $route = Route::where('id', $LoadRequest->route_id)->first();

        if (is_object($LoadRequest)) {
            $routes = Route::find($LoadRequest->route_id);

            if (is_object($routes)) {
                $depot_id = $routes->depot_id;
                if (is_object($LoadRequest->LoadRequestDetail) && $depot_id != null) {
                    foreach ($LoadRequest->LoadRequestDetail as $detail) {
                        if ($detail->qty > 0) {
                            $Warehouse = Warehouse::where('depot_id', $depot_id)->first();

                            $conversation = getItemDetails2($detail->item_id, $detail->item_uom_id, $detail->qty);
                            if (is_object($Warehouse)) {

                                $warehouselocation = Storagelocation::where('warehouse_id', $Warehouse->id)
                                    ->where('loc_type', '1')
                                    ->first();

                                $LoadRequest->src_location = $warehouselocation->name;
                                $LoadRequest->save();

                                if (is_object($warehouselocation)) {
                                    $routelocation = Storagelocation::where('route_id', $LoadRequest->route_id)
                                        ->where('loc_type', '1')
                                        ->first();
                                    if (is_object($routelocation)) {

                                        $routestoragelocation_id = $routelocation->id;
                                        $warehousestoragelocation_id = $warehouselocation->id;
                                        $warehouselocation_detail = StoragelocationDetail::where('storage_location_id', $warehousestoragelocation_id)
                                            ->where('item_id', $detail->item_id)
                                            ->first();

                                        $routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
                                            ->where('item_id', $detail->item_id)
                                            ->where('item_uom_id', $conversation['UOM'])
                                            ->first();


                                        if (is_object($warehouselocation_detail)) {

                                            // if ($warehouselocation_detail->item_uom_id == $detail->item_uom_id) {
                                                if ($warehouselocation_detail->qty >= $conversation['Qty']) {
                                                    $warehouselocation_detail->qty = ($warehouselocation_detail->qty - $conversation['Qty']);
                                                    $warehouselocation_detail->save();
                                                } else {
                                                    $item_detail = Item::where('id', $detail->item_id)->first();
                                                    return prepareResult(false, [], ["error" => "Item is out of stock! the item name is $item_detail->item_name"], " Item is out of stock!  the item name is $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                                }
                                            // } else {
                                            // }
                                        } else {
                                            //--------Item not available Error
                                            $item_detail = Item::where('id', $detail->item_id)->first();
                                            return prepareResult(false, [], ["error" => "Item not available!. the item name is $item_detail->item_name"], " Item not available! the item name is  $item_detail->item_name Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                        }
                                        if (is_object($routelocation_detail)) {

                                            $routelocation_detail->qty = ($routelocation_detail->qty + $conversation['Qty']);
                                            $routelocation_detail->save();
                                        } else {

                                            $routestoragedetail = new StoragelocationDetail;
                                            $routestoragedetail->storage_location_id = $routelocation->id;
                                            $routestoragedetail->item_id = $detail->item_id;
                                            $routestoragedetail->item_uom_id = $conversation['UOM'];
                                            $routestoragedetail->qty = $conversation['Qty'];
                                            $routestoragedetail->save();
                                        }
                                    } else {
                                        return prepareResult(false, [], ["error" => "Route Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                    }
                                } else {

                                    return prepareResult(false, [], ["error" => "Wherehouse Location not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                                }
                            } else {
                                return prepareResult(false, [], ["error" => "Wherehouse not available!"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                            }
                        }
                    }
                }

                $LoadRequest = LoadRequest::find($LoadRequest->id);
                $LoadRequest->status = 'Approved';
                if ($generate) {
                    $LoadRequest->approval_status = 'Load Created';
                }
                $LoadRequest->save();
            } else {
                return prepareResult(false, [], ["error" => "Route not found"], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            }
        }
        //------

        $loadheader = new SalesmanLoad;
        $loadheader->load_number = $LoadRequest->load_number;
        $loadheader->route_id = $LoadRequest->route_id;
        $loadheader->depot_id = $depot_id;
        $loadheader->salesman_id = $LoadRequest->salesman_id;
        $loadheader->load_date = $LoadRequest->load_date;
        $loadheader->load_type = 3;
        $loadheader->load_confirm = 0;
        $loadheader->status = 0;
        $loadheader->save();

        if ($LoadRequest->LoadRequestDetail) {
            foreach ($LoadRequest->LoadRequestDetail as $item) {
                $loaddetail = new SalesmanLoadDetails;
                $loaddetail->salesman_load_id = $loadheader->id;
                $loaddetail->route_id = $LoadRequest->route_id;
                $loaddetail->salesman_id = $LoadRequest->salesman_id;
                $loaddetail->depot_id = $depot_id;
                $loaddetail->load_date = $LoadRequest->load_date;
                $loaddetail->item_id = $item->item_id;
                $loaddetail->item_uom = $item->item_uom_id;
                $loaddetail->load_qty = $item->qty;
                $loaddetail->requested_item_uom_id = $item->requested_item_uom_id;
                $loaddetail->requested_qty = $item->requested_qty;
                $loaddetail->save();
            }
        }

        if (is_object($LoadRequest) && $generate === false) {
            $dataNofi = array(
                'message' => "Your Load Request number is " . $LoadRequest->load_number . " approved by " . $request->user()->firstname,
                'title' => "Load Request",
                'noti_type' => "load_request",
                "uuid" => $LoadRequest->uuid
            );

            $device_detail = DeviceDetail::where('user_id', $LoadRequest->salesman_id)
                ->orderBy('id', 'desc')
                ->first();
            // $device_detail->each(function ($token, $key) use ($dataNofi) {
            // });
            if (is_object($device_detail)) {
                $t = $device_detail->device_token;
                sendNotificationAndroid($dataNofi, $t);
            }

            $d = array(
                'uuid' => $loadheader->uuid,
                'user_id' => $request->salesman_id,
                'type' => 'Load Request',
                'message' => "Your Load Request is" . $request->load_number . " approved by " . $request->user()->firstname,
                'status' => 1
            );
            saveNotificaiton($d);
        }

        return $LoadRequest;
    }

    public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request, $obj)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id = $work_flow_rule_id;
        $createObj->module_name = $module_name;
        $createObj->raw_id = $obj->id;
        $createObj->request_object = $request->all();
        $createObj->save();

        $wfrau = WorkFlowRuleApprovalUser::where('work_flow_rule_id', $work_flow_rule_id)->first();

        $data = array(
            'uuid' => (is_object($obj)) ? $obj->uuid : 0,
            'user_id' => $wfrau->user_id,
            'type' => $module_name,
            'message' => "Approve the New " . $module_name,
            'status' => 1,
        );
        saveNotificaiton($data);
    }
}
