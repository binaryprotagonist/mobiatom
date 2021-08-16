<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Country;
use App\Model\CustomFieldValueSave;
use App\Model\ImportTempFile;
use App\Model\Route;
use Illuminate\Http\Request;
use App\User;
use App\Model\SalesmanInfo;
use App\Model\SalesmanRole;
use App\Model\SalesmanType;
use App\Model\SalesmanNumberRange;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SalesmanImport;
use App\Model\Collection;
use App\Model\CreditNote;
use App\Model\CustomerVisit;
use App\Model\Invoice;
use File;
use URL;
use App\Model\SalesmanLob;
use App\Model\Lob;
use App\Model\SalesmanLoginLog;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowRuleApprovalUser;
use Illuminate\Support\Carbon;

class SalesmanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $users_query = SalesmanInfo::with(
            'user:id,uuid,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status',
            'organisation:id,org_name',
            'route:id,route_code,route_name,status',
            'salesmanRole:id,name,code,status',
            'salesmanType:id,name,code,status',
            'salesmanRange',
            'salesmanSupervisor:id,firstname,lastname',
            'salesmanHelper:id,firstname,lastname',
            'customFieldValueSave',
            'customFieldValueSave.customField',
            'salesmanlob:id,lob_id,salesman_info_id',
            'salesmanlob.lob:id,name',
        );

        if ($request->salesman_code) {
            $users_query->where('salesman_code', 'like', '%' . $request->salesman_code . '%');
        }

        if ($request->category) {
            $users_query->where('category_id', 'like', '%' . $request->category . '%');
        }

        if ($request->name) {
            $name = $request->name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $users_query->whereHas('user', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $users_query->whereHas('user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->type) {
            $type = $request->type;
            $users_query->whereHas('salesmanType', function ($q) use ($type) {
                $q->where('name', 'like', '%' . $type . '%');
            });
        }

        if ($request->route) {
            $route = $request->route;
            $users_query->whereHas('route', function ($q) use ($route) {
                $q->where('route_name', 'like', '%' . $route . '%');
            });
        }

        if ($request->routeCode) {
            $routeCode = $request->routeCode;
            $users_query->whereHas('route', function ($q) use ($routeCode) {
                $q->where('route_code', 'like', '%' . $routeCode . '%');
            });
        }

        if ($request->role) {
            $role = $request->role;
            $users_query->whereHas('salesmanRole', function ($q) use ($role) {
                $q->where('name', 'like', '%' . $role . '%');
            });
        }

        $users = $users_query->orderBy('id', 'desc')->get();

        // approval
        $results = GetWorkFlowRuleObject('Salesman');
        $approve_need_salesman = array();
        $approve_need_salesman_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_salesman[] = $raw['object']->raw_id;
                $approve_need_salesman_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $users_array = array();
        if (is_object($users)) {
            foreach ($users as $key => $user1) {
                if (in_array($users[$key]->id, $approve_need_salesman)) {
                    $users[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_salesman_object_id[$users[$key]->id])) {
                        $users[$key]->objectid = $approve_need_salesman_object_id[$users[$key]->id];
                    } else {
                        $users[$key]->objectid = '';
                    }
                } else {
                    $users[$key]->need_to_approve = 'no';
                    $users[$key]->objectid = '';
                }

                if ($users[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($users[$key]->id, $approve_need_salesman)) {
                    $users_array[] = $users[$key];
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
                if (isset($users_array[$offset])) {
                    $data_array[] = $users_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($users_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($users_array);
        } else {
            $data_array = $users_array;
        }
        return prepareResult(true, $data_array, [], "Salesman listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function salesmanTypeList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }


        $salesman_type = SalesmanType::orderBy('id', 'desc')->get();

        if (is_object($salesman_type)) {
            foreach ($salesman_type as $key => $salesman_type1) {
                $salesman_type_array[] = $salesman_type[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($salesman_type_array[$offset])) {
                    $data_array[] = $salesman_type_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($salesman_type_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($salesman_type_array);
        } else {
            $data_array = $salesman_type_array;
        }
        return prepareResult(true, $data_array, [], "Salesman type listing", $this->success, $pagination);

        // return prepareResult(true, $salesman_type, [], "Salesman type listing", $this->success);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function salesmanRoleList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $salesman_role = SalesmanRole::orderBy('id', 'desc')->get();

        if (is_object($salesman_role)) {
            foreach ($salesman_role as $key => $salesman_role1) {
                $salesman_role_array[] = $salesman_role[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($salesman_role_array[$offset])) {
                    $data_array[] = $salesman_role_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($salesman_role_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($salesman_role_array);
        } else {
            $data_array = $salesman_role_array;
        }

        return prepareResult(true, $data_array, [], "Salesman role listing", $this->success, $pagination);
    }

    /**
     * Show the form for creating a new resource.
     *
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Salesman", $this->unprocessableEntity);
        }

        if ($request->salesman_type_id == 1 && !$request->route_id) {
            $validator = \Validator::make($input, [
                'route_id' => 'required|integer|exists:routes,id',
            ]);

            if ($validator->fails()) {
                return prepareResult(false, [], $validator->errors()->first(), "Error while validating Salesman", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Salesman', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Salesman',$request);
            }

            $user = new User;
            $user->usertype = 3;
            $user->parent_id = $request->parent_id;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->mobile = $request->mobile;
            $user->country_id = $request->country_id;
            $user->api_token = \Str::random(35);
            $user->is_approved_by_admin = $request->is_approved_by_admin;
            $user->role_id = $request->role_id;
            $user->status = $status;
            $user->save();

            $salesman_infos = new SalesmanInfo;
            $salesman_infos->user_id = $user->id;
            $salesman_infos->salesman_type_id = $request->salesman_type_id;

            $salesman_infos->salesman_code = nextComingNumber('App\Model\SalesmanInfo', 'salesman', 'salesman_code', $request->salesman_code);
            // $salesman_infos->salesman_code = $request->salesman_code;
            if ($request->salesman_profile) {
                $salesman_infos->profile_image = saveImage($request->firstname . ' ' . $request->lastname, $request->salesman_profile, 'salesman-profile');
            }

            if ($request->salesman_type_id != 3) {
                $salesman_infos->route_id = $request->route_id;
                $salesman_infos->salesman_role_id = $request->salesman_role_id;
                $salesman_infos->salesman_supervisor = $request->salesman_supervisor;
                $salesman_infos->is_lob = (!empty($request->is_lob)) ? $request->is_lob : 0;
            }
            $salesman_infos->salesman_helper_id = (!empty($request->salesman_helper_id)) ? $request->salesman_helper_id : null;

            $salesman_infos->current_stage = $current_stage;
            $salesman_infos->status = $status;
            $salesman_infos->category_id = (!empty($request->category_id)) ? $request->category_id : null;
            $salesman_infos->region_id = (!empty($request->region_id)) ? $request->region_id : null;
            $salesman_infos->save();

            if ($request->is_lob == 1) {
                if (is_array($request->salesman_lob)) {
                    foreach ($request->salesman_lob as $salesman_lob_value) {
                        $salesman_lob = new SalesmanLob;
                        $salesman_lob->salesman_info_id  = $salesman_infos->id;
                        $salesman_lob->lob_id            = $salesman_lob_value['lob_id'];
                        $salesman_lob->save();
                    }
                }
            }

            if ($request->category_id == "Salesman") {
                $salesman_number_range = new SalesmanNumberRange;
                $salesman_number_range->salesman_id = $salesman_infos->id;
                $salesman_number_range->customer_from = $request->customer_from;
                $salesman_number_range->customer_to = $request->customer_to;
                $salesman_number_range->order_from = $request->order_from;
                $salesman_number_range->order_to = $request->order_to;
                $salesman_number_range->invoice_from = $request->invoice_from;
                $salesman_number_range->invoice_to = $request->invoice_to;
                $salesman_number_range->collection_from = $request->collection_from;
                $salesman_number_range->collection_to = $request->collection_to;
                $salesman_number_range->credit_note_from = $request->credit_note_from;
                $salesman_number_range->credit_note_to = $request->credit_note_to;
                $salesman_number_range->unload_from = $request->unload_from;
                $salesman_number_range->unload_to = $request->unload_to;
                $salesman_number_range->exchange_from = "100000";
                $salesman_number_range->exchange_to = "999999";
                $salesman_number_range->save();
            }

            if ($isActivate = checkWorkFlowRule('Salesman', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Salesman', $request, $salesman_infos);
            }

            updateNextComingNumber('App\Model\SalesmanInfo', 'salesman');

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($salesman_infos->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            $salesman_infos->getSaveData();
            return prepareResult(true, $salesman_infos, [], "Salesman added successfully", $this->success);
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

        $users = User::with(
            'salesmanInfo',
            'salesmanInfo.salesmanRange',
            'salesmanInfo.salesmanSupervisor:id,firstname,lastname',
            'salesmanInfo.salesmanHelper:id,firstname,lastname',
            'salesmanInfo.salesmanlob',
            'salesmanInfo.salesmanlob.lob'
        )->where('uuid', $uuid)
            ->first();

        if (!is_object($users)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $users, [], "Salesman Edit", $this->success);
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
        $validate = $this->validations($input, "edit");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Salesman", $this->unprocessableEntity);
        }

        if ($request->salesman_type_id == 1 && !$request->route_id) {
            $validator = \Validator::make($input, [
                'route_id' => 'required|integer|exists:routes,id',
            ]);

            if ($validator->fails()) {
                return prepareResult(false, [], $validator->errors()->first(), "Error while validating Salesman", $this->unprocessableEntity);
            }
        }

        $status = $request->status;
        $current_stage = 'Approved';
        $current_organisation_id = request()->user()->organisation_id;
        if ($isActivate = checkWorkFlowRule('Salesman', 'edit', $current_organisation_id)) {
            $current_stage = 'Pending';
        }

        // $user->email = $request->email;
        $user = User::where('uuid', $uuid)->first();

        $user->usertype = 3;
        $user->parent_id = $request->parent_id;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->mobile = $request->mobile;
        $user->country_id = $request->country_id;
        $user->is_approved_by_admin = $request->is_approved_by_admin;
        $user->role_id = $request->role_id;
        $user->save();

        $salesman_infos = $user->salesmanInfo;

        if ($request->salesman_type_id != 3) {
            $salesman_delete = SalesmanLob::where('salesman_info_id', $user->salesmanInfo->id)->delete();
            $salesman_infos->route_id = $request->route_id;
            $salesman_infos->salesman_role_id = $request->salesman_role_id;
            $salesman_infos->salesman_supervisor = $request->salesman_supervisor;
            $salesman_infos->is_lob = (!empty($request->is_lob)) ? $request->is_lob : 0;
        }
        $salesman_infos->salesman_helper_id = (!empty($request->salesman_helper_id)) ? $request->salesman_helper_id : null;


        // $salesman_infos->route_id = $request->route_id;
        $salesman_infos->salesman_type_id = $request->salesman_type_id;
        // $salesman_infos->salesman_role_id = $request->salesman_role_id;
        // $salesman_infos->salesman_supervisor = $request->salesman_supervisor;
        if ($request->salesman_profile) {
            $salesman_infos->profile_image = saveImage($request->firstname . ' ' . $request->lastname, $request->salesman_profile, 'salesman-profile');
        }
        $salesman_infos->current_stage = $current_stage;
        $salesman_infos->status = $status;
        $salesman_infos->region_id = (!empty($request->region_id)) ? $request->region_id : null;
        $salesman_infos->category_id = (!empty($request->category_id)) ? $request->category_id : null;
        $salesman_infos->save();

        if ($request->is_lob == 1) {
            if (is_array($request->salesman_lob)) {
                foreach ($request->salesman_lob as $salesman_lob_value) {
                    $salesman_lob = new SalesmanLob;
                    $salesman_lob->salesman_info_id  = $salesman_infos->id;
                    $salesman_lob->lob_id            = $salesman_lob_value['lob_id'];
                    $salesman_lob->save();
                }
            }
        }

        if ($request->category_id == "Salesman") {
            $salesman_number_range = SalesmanNumberRange::where('salesman_id', $salesman_infos->id)->first();
            if (!is_object($salesman_number_range)) {
                $salesman_number_range = new SalesmanNumberRange;
            }
            $salesman_number_range->salesman_id = $salesman_infos->id;
            $salesman_number_range->customer_from = $request->customer_from;
            $salesman_number_range->customer_to = $request->customer_to;
            $salesman_number_range->order_from = $request->order_from;
            $salesman_number_range->order_to = $request->order_to;
            $salesman_number_range->invoice_from = $request->invoice_from;
            $salesman_number_range->invoice_to = $request->invoice_to;
            $salesman_number_range->collection_from = $request->collection_from;
            $salesman_number_range->collection_to = $request->collection_to;
            $salesman_number_range->credit_note_from = $request->credit_note_from;
            $salesman_number_range->credit_note_to = $request->credit_note_to;
            $salesman_number_range->unload_from = $request->unload_from;
            $salesman_number_range->unload_to = $request->unload_to;
            $salesman_number_range->save();
        }

        if ($isActivate = checkWorkFlowRule('Salesman', 'edit', $current_organisation_id)) {
            $this->createWorkFlowObject($isActivate, 'Salesman', $request, $salesman_infos);
        }

        if (is_array($request->modules) && sizeof($request->modules) >= 1) {
            CustomFieldValueSave::where('record_id', $salesman_infos->id)->delete();
            foreach ($request->modules as $module) {
                savecustomField($salesman_infos->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
            }
        }
        $salesman_infos->getSaveData();
        return prepareResult(true, $user, [], "Salesman updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating depots", $this->unauthorized);
        }

        $user = User::where('uuid', $uuid)
            ->first();

        if (is_object($user)) {
            $user->salesmanInfo->delete();
            $user->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                // 'route_id' => 'required|integer|exists:routes,id',
                // 'country_id' => 'required|integer|exists:countries,id',
                'salesman_type_id' => 'required|integer|exists:salesman_types,id',
                'salesman_role_id' => 'required|integer|exists:salesman_roles,id',
                'salesman_code' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'role_id' => 'required',
                // 'customer_from' => 'required',
                // 'customer_to' => 'required',
                // 'order_from' => 'required',
                // 'order_to' => 'required',
                // 'invoice_from' => 'required',
                // 'invoice_to' => 'required',
                // 'collection_from' => 'required',
                // 'collection_to' => 'required',
                // 'credit_note_from' => 'required',
                // 'credit_note_to' => 'required',
                // 'unload_from' => 'required',
                // 'unload_to' => 'required',
                'status' => 'required'
                // 'mobile' => 'required',
                // 'is_approved_by_admin' => 'required',
                // 'salesman_supervisor' => 'required'
            ]);
        }

        if ($type == "edit") {
            $validator = \Validator::make($input, [
                // 'route_id' => 'required|integer|exists:routes,id',
                // 'country_id' => 'required|integer|exists:countries,id',
                'salesman_type_id' => 'required|integer|exists:salesman_types,id',
                'salesman_role_id' => 'required|integer|exists:salesman_roles,id',
                'salesman_code' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'role_id' => 'required',

                /* 'customer_from' => 'required',
                'customer_to' => 'required',
                'order_from' => 'required',
                'order_to' => 'required',
                'invoice_from' => 'required',
                'invoice_to' => 'required',
                'collection_from' => 'required',
                'collection_to' => 'required',
                'credit_note_from' => 'required',
                'credit_note_to' => 'required',
                'unload_from' => 'required',
                'unload_to' => 'required', */

                'status' => 'required'
                // 'password' => 'required',
                // 'mobile' => 'required',
                // 'is_approved_by_admin' => 'required',
                // 'salesman_supervisor' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'salesman_info_ids' => 'required'
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function merchandiserList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $salesman_info = SalesmanInfo::select('id', 'user_id', 'salesman_type_id', 'organisation_id')
            ->with(
                'user:id,uuid,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status'
            )
            ->where('salesman_type_id', 2)
            ->orderBy('id', 'desc')
            ->get();

        $salesman_info_array = array();
        if (is_object($salesman_info)) {
            foreach ($salesman_info as $key => $salesman_info1) {
                $salesman_info_array[] = $salesman_info[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($salesman_info_array[$offset])) {
                    $data_array[] = $salesman_info_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($salesman_info_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_reocrds'] = count($salesman_info_array);
        } else {
            $data_array = $salesman_info_array;
        }

        return prepareResult(true, $data_array, [], "Merchandiser listing", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $action
     * @param  string $status
     * @param  string $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating salesman info.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->salesman_info_ids;

            foreach ($uuids as $uuid) {
                SalesmanInfo::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            // $CustomerInfo = $this->index();
            return prepareResult(true, "", [], "SalesmanInfo status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->salesman_info_ids;
            foreach ($uuids as $uuid) {
                SalesmanInfo::where('uuid', $uuid)->delete();
            }

            // $CustomerInfo = $this->index();
            return prepareResult(true, "", [], "SalesmanInfo deleted success", $this->success);
        }
    }

    public function imports(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Salesman not authenticate", $this->unauthorized);
        }

        $validator = \Validator::make($request->all(), [
            'salesman_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate region import", $this->unauthorized);
        }

        Excel::import(new SalesmanImport, request()->file('salesman_file'));
        return prepareResult(true, [], [], "Salesman successfully imported", $this->success);
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("First Name", "Last Name", "Email", "Password", "Mobile", "Country", "Status", "Route", "Region", "Salesman Type", "Salesman Role", "Salesman Category", "Salesman Helper", "Salesman Code", "Salesman Supervisor", "Profile Image", "Incentive", "Date of Joning", "Block Start Date", "Block End Date", "Order From", "Order To", "Invoice From", "Invoice To", "Collection From", "Collection To", "Return From", "Return To", "Unload From", "Unload To", "Is Lob", "LOB Name");

        // $mappingarray = array("First Name", "Last Name", "Email", "Password", "Mobile", "Country", "Status", "Route", 'Region', "Salesman Type", "Salesman Role", "Salesman Code", "Salesman Supervisor", 'Category', 'Salesman Helper Code', 'LOB', "Order From", "Order To", "Invoice From", "Invoice To", "Collection From", "Collection To", "Return From", "Return To", "Unload From", "Unload To");


        return prepareResult(true, $mappingarray, [], "Customer Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'salesman_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate salesman import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('salesman_file')->store('import');
            $filename = storage_path("app/" . $file);
            $fp = fopen($filename, "r");
            $content = fread($fp, filesize($filename));
            $lines = explode("\n", $content);
            $heading_array_line = isset($lines[0]) ? $lines[0] : '';
            $heading_array = explode(",", trim($heading_array_line));
            fclose($fp);

            if (!$heading_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            if (!$map_key_value_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }

            $import = new SalesmanImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);
            $succussrecords = 0;
            $successfileids = 0;
            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());
                $data = json_encode($import->successAllRecords());
                $fileName = time() . '_datafile.txt';
                File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();
                $successfileids = $importtempfiles->id;
            }
            $errorrecords = 0;
            $errror_array = array();
            if ($import->failures()) {

                foreach ($import->failures() as $failure_key => $failure) {
                    if ($failure->row() != 1) {
                        $failure->row(); // row that went wrong
                        $failure->attribute(); // either heading key (if using heading row concern) or column index
                        $failure->errors(); // Actual error messages from Laravel validator
                        $failure->values(); // The values of the row that has failed.

                        $error_msg = isset($failure->errors()[0]) ? $failure->errors()[0] : '';
                        if ($error_msg != "") {
                            $error_result = array();
                            $error_row_loop = 0;
                            foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
                                $error_result[$map_key_value_array_value] = isset($failure->values()[$error_row_loop]) ? $failure->values()[$error_row_loop] : '';
                                $error_row_loop++;
                            }
                            $errror_array[] = array(
                                'errormessage' => "There was an error on row " . $failure->row() . ". " . $error_msg,
                                'errorresult' => $error_result, //$failure->values(),
                            );
                        }
                    }
                }
                $errorrecords = count($errror_array);
            }

            $errors = $errror_array;
            $result['successrecordscount'] = $succussrecords;
            $result['errorrcount'] = $errorrecords;
            $result['successfileids'] = $successfileids;
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                if ($failure->row() != 1) {
                    info($failure->row());
                    info($failure->attribute());
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                    $errors[] = $failure->errors();
                }
            }
            return prepareResult(true, [], $errors, "Failed to validate bank import", $this->success);
        }
        return prepareResult(true, $result, $errors, "salesman successfully imported", $this->success);
    }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);

            if ($finaldata) :
                foreach ($finaldata as $row) :

                    $country = Country::where('name', 'LIKE', '%' . $row[5] . '%')->first();
                    $route = Route::where('route_name', $row[7])->first();
                    $salesman_type = SalesmanType::where('name', $row[8])->first();
                    $salesman_role = SalesmanRole::where('name', $row[9])->first();

                    $user = User::where('email', $row[2])->first();

                    $current_stage = 'Approved';
                    $current_organisation_id = request()->user()->organisation_id;

                    if ($row[6] == "Yes") {
                        $status = 1;
                    }

                    if ($row[6] == "No") {
                        $status = 0;
                    }

                    if ($isActivate = checkWorkFlowRule('Salesman', 'create', $current_organisation_id)) {
                        $status = 0;
                        $current_stage = 'Pending';
                        //$this->createWorkFlowObject($isActivate, 'Salesman',$request);
                    }

                    //$skipduplicate = 1 means skip the data
                    //$skipduplicate = 0 means overwrite the data
                    $skipduplicate = $request->skipduplicate;

                    if ($skipduplicate) {
                        $salesmanInfo = User::where('email', $row[2])->first();
                        $salesmaninfos = $salesmanInfo->salesmanInfo;

                        $lob = Lob::where('name', 'like', '%' . $row[31] . '%')->first();

                        $salesmanLob = SalesmanLob::where('salesman_info_id', $salesmaninfos->id)
                            ->where('lob_id', $lob->id)
                            ->first();

                        if (is_object($salesmanInfo) && is_object($salesmanLob)) {
                            continue;
                        }

                        if (!is_object($salesmanInfo)) {
                            $user = $this->users($row, $country, $status);

                            $salesman_infos = $this->salesmanInfo(
                                $current_organisation_id,
                                $user,
                                $route,
                                $salesman_type,
                                $salesman_role,
                                $row,
                                $current_stage,
                                $status
                            );

                            // Save Salesman Number Range
                            $this->salesmanNumberRange($salesman_infos, $row);

                            // Save Salesman Number Range
                            if ($row[30] == 1) {
                                $this->salesmanLob($salesman_infos, $row);
                            }
                        } else {
                            $this->salesmanLob($salesmanInfo, $row);
                        }
                    } else {
                        $salesmanInfos = SalesmanInfo::where('salesman_code', $row[10])->first();
                        // $user_check = User::where('email', $row[2])->first();

                        if (is_object($salesmanInfos)) {

                            $user = User::find($salesmanInfos->user_id);
                            $user = $this->users($row, $country, $status, $user);

                            $salesman_infos = $this->salesmanInfo(
                                $current_organisation_id,
                                $user,
                                $route,
                                $salesman_type,
                                $salesman_role,
                                $row,
                                $current_stage,
                                $status,
                                $salesmanInfos
                            );

                            $salesman_number_ranges = SalesmanNumberRange::where('salesman_id', $salesman_infos->id)->first();

                            if (!is_object($salesman_number_ranges)) {
                                // Save Salesman Number Range
                                $this->salesmanNumberRange($salesman_infos, $row);
                            } else {
                                $this->salesmanNumberRange($salesman_infos, $row, $salesman_number_ranges);
                            }

                            // Save Salesman lob
                            if ($row[30] == 1) {
                                $this->salesmanLob($salesman_infos, $row);
                            }
                        } else {
                            $user = $this->users($row, $country, $status);

                            $salesman_infos = $this->salesmanInfo(
                                $current_organisation_id,
                                $user,
                                $route,
                                $salesman_type,
                                $salesman_role,
                                $row,
                                $current_stage,
                                $status
                            );

                            $this->salesmanNumberRange($salesman_infos, $row);

                            if ($row[30] == "Yes") {
                                $this->salesmanLob($salesman_infos, $row);
                            }
                        }
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "salesman successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    private function users($row, $country, $status, $userObj = null)
    {
        if ($userObj) {
            $user = $userObj;
        } else {
            $user = new User;
        }
        $user->usertype = 3;
        // $user->parent_id = auth()->user()->id;
        $user->firstname = $row[0];
        $user->lastname  = $row[1];
        $user->email = $row[2];
        $user->password = Hash::make($row[3]);
        $user->mobile = $row[4];
        $user->country_id = (is_object($country)) ? $country->id : 0;
        $user->api_token = \Str::random(35);
        $user->status = $status;
        $user->save();
        return $user;
    }

    private function salesmanInfo(
        $organisation_id,
        $user,
        $route,
        $salesman_type,
        $salesman_role,
        $row,
        $current_stage,
        $status,
        $salesmanInfos = null
    ) {
        if ($salesmanInfos) {
            $salesman_infos = $salesmanInfos;
        } else {
            $salesman_infos = new SalesmanInfo;
        }
        $salesman_infos->organisation_id = $organisation_id;
        $salesman_infos->user_id = $user->id;
        $salesman_infos->route_id = (is_object($route)) ? $route->id : 0;
        $salesman_infos->salesman_type_id = (is_object($salesman_type)) ? $salesman_type->id : 0;
        $salesman_infos->salesman_role_id = (is_object($salesman_role)) ? $salesman_role->id : 0;
        $salesman_infos->salesman_code = $row[10];
        $salesman_infos->salesman_supervisor = $row[11];
        $salesman_infos->current_stage = $current_stage;
        $salesman_infos->status = $status;
        $salesman_infos->save();

        return $salesman_infos;
    }

    private function salesmanLob($salesman_infos, $row)
    {
        if ($row[30] == "Yes") {
            $salesman_lob = new SalesmanLob;
            $salesman_lob->salesman_info_id  = (is_object($salesman_infos)) ? $salesman_infos->id : 0;
            $salesman_lob->lob_id            = $row[31];
            $salesman_lob->save();
        }
    }

    private function salesmanNumberRange($salesman_infos, $row, $salesman_number_ranges = null)
    {
        if ($salesman_number_ranges) {
            $salesman_number_ranges = SalesmanNumberRange::where('salesman_id', $salesman_infos->id)->first();
        } else {
            $salesman_number_ranges = new SalesmanNumberRange;
        }
        $salesman_number_ranges->salesman_id = (is_object($salesman_infos)) ? $salesman_infos->id : 0;
        $salesman_number_ranges->order_from = $row[20];
        $salesman_number_ranges->order_to = $row[21];
        $salesman_number_ranges->invoice_from = $row[22];
        $salesman_number_ranges->invoice_to = $row[23];
        $salesman_number_ranges->collection_from = $row[24];
        $salesman_number_ranges->collection_to = $row[25];
        $salesman_number_ranges->credit_note_from = $row[26];
        $salesman_number_ranges->credit_note_to  = $row[27];
        $salesman_number_ranges->unload_from = $row[28];
        $salesman_number_ranges->unload_to  = $row[29];
        $salesman_number_ranges->save();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSPRouteList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $users = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor', 'route_id')
            ->with(
                'route:id,route_code,route_name'
            )
            ->where('salesman_supervisor', $request->supervisor_id)
            ->orderBy('id', 'desc')
            ->groupBy('route_id')
            ->get();

        return prepareResult(true, $users, [], "Supervisor route listing", $this->success);
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

    public function salesmanLoginLog(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $salesman_login_log_query = SalesmanLoginLog::select('id', 'user_id', 'ip', 'device_token', 'vesion', 'device_name', 'imei_number', 'created_at');

        if ($request->merchandiser_id) {
            $salesman_login_log_query->where('user_id', $request->merchandiser_id);
        }

        if ($request->start_date && !$request->end_date) {
            $salesman_login_log_query->whereDate('created_at', $request->start_date);
        }

        if (!$request->start_date && $request->end_date) {
            $salesman_login_log_query->whereDate('created_at', $request->end_date);
        }

        if ($request->start_date && $request->end_date) {
            $sdate = $request->start_date;
            $edate = date('Y-m-d', strtotime('+1 days', strtotime($request->end_date)));
            if ($request->start_date == $request->end_date) {
                $salesman_login_log_query->whereDate('created_at', $request->start_datedate);
            } else {
                $salesman_login_log_query->whereBetween('created_at', [$sdate, $edate]);
            }
        }

        $salesman_login_log = $salesman_login_log_query->get();

        $salesman_login_log_array = array();
        if (is_object($salesman_login_log)) {
            foreach ($salesman_login_log as $key => $salesman_login_log1) {
                $salesman_login_log_array[] = $salesman_login_log[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($salesman_login_log_array[$offset])) {
                    $data_array[] = $salesman_login_log_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($salesman_login_log_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($salesman_login_log_array);
        } else {
            $data_array = $salesman_login_log_array;
        }

        return prepareResult(true, $data_array, [], "Salesman login log listing", $this->success, $pagination);
    }

    public function salesmanTotalCountPerMonth($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating salesman id", $this->unauthorized);
        }

        $invoice['invoice_count'] = 0;
        $invoice['invoice_total'] = 0;
        $invoice['details'] = [];

        $credit_note['credit_note_count'] = 0;
        $credit_note['credit_note_total'] = 0;

        $collection['collection_count'] = 0;
        $collection['collection_total'] = 0;

        $cusotmer_visit['cusotmer_visit_count'] = 0;

        if ($id) {
            // $salesman_ids = $salesmans->pluck('user_id')->toArray();

            $invoices = Invoice::select('id', 'invoice_number', 'total_gross', 'grand_total', 'created_at', 'invoice_date', 'total_net')
                ->where('salesman_id', $id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->get();

            if ($invoices->count()) {
                $grand_total_array = $invoices->pluck('grand_total')->toArray();
                $grand_net_total = $invoices->pluck('total_net')->toArray();
                $grand_total_gross = $invoices->pluck('total_gross')->toArray();
                $invoice_count = count($invoices);
                $invoice_total = array_sum($grand_total_array);
                $invoice_net_total = array_sum($grand_net_total);
                $invoice_total_gross = array_sum($grand_total_gross);

                $invoice['invoice_count'] = $invoice_count;
                $invoice['invoice_net'] = $invoice_net_total;
                $invoice['invoice_gross'] = $invoice_total_gross;
                $invoice['invoice_total'] = $invoice_total;
                $invoice['details'] = $invoices;
            }

            $credit_notes = CreditNote::where('salesman_id', $id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->get();
            if ($credit_notes->count()) {
                $grand_total_array = $credit_notes->pluck('grand_total')->toArray();
                $grand_gross_array = $credit_notes->pluck('total_gross')->toArray();
                $grand_net_array = $credit_notes->pluck('total_net')->toArray();
                $credit_note_count = count($credit_notes);
                $credit_note_total = array_sum($grand_total_array);
                $credit_note_gross = array_sum($grand_gross_array);
                $credit_note_net = array_sum($grand_net_array);

                $credit_note['credit_note_count'] = $credit_note_count;
                $credit_note['credit_note_total'] = $credit_note_total;
                $credit_note['credit_note_gross'] = $credit_note_gross;
                $credit_note['credit_note_net'] = $credit_note_net;
            }

            $collections = Collection::where('salesman_id', $id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->get();

            if ($collections->count()) {
                $grand_total_array = $collections->pluck('invoice_amount')->toArray();
                $collection_count = count($collections);
                $collection_total = array_sum($grand_total_array);

                $collection['collection_count'] = $collection_count;
                $collection['collection_total'] = $collection_total;
            }

            $cusotmer_visits = CustomerVisit::where('salesman_id', $id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->groupBy('customer_id', 'created_at')
                ->get();

            if ($cusotmer_visits->count()) {
                $cusotmer_visit_count = count($cusotmer_visits);
                $cusotmer_visit['cusotmer_visit_count'] = $cusotmer_visit_count;
            }
        }

        $data = array_merge($invoice, $credit_note, $collection, $cusotmer_visit);

        return prepareResult(true, collect($data), [], "Salesman month wise data", $this->success);
    }
}
