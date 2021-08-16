<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CustomerGroup;

class CustomerGroupController extends Controller
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

        $customer_group = CustomerGroup::select('id', 'uuid', 'organisation_id', 'group_code', 'group_name', 'status')
        ->orderBy('id', 'desc')
        ->get();

        $customer_group_array = array();
        if (is_object($customer_group)) {
            foreach ($customer_group as $key => $customer_group1) {
                $customer_group_array[] = $customer_group[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($customer_group_array[$offset])) {
                    $data_array[] = $customer_group_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($customer_group_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($customer_group_array);
        } else {
            $data_array = $customer_group_array;
        }

        return prepareResult(true, $data_array, [], "Customer group listing", $this->success, $pagination);
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

        $customer_group = CustomerGroup::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'group_name', 'group_code', 'status')
            ->first();

        if ($customer_group) {
            return prepareResult(true, $customer_group, [], "Customer group added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer group", $this->unprocessableEntity);
        }

        $customer_group = new CustomerGroup;
        // $customer_group->group_code = $request->group_code;
        $customer_group->group_code = nextComingNumber('App\Model\CustomerGroup', 'customer_group', 'group_code', $request->group_code);
        $customer_group->group_name = $request->group_name;
        $customer_group->status = $request->status;
        $customer_group->save();

        if ($customer_group) {
            updateNextComingNumber('App\Model\CustomerGroup', 'customer_group');
            return prepareResult(true, $customer_group, [], "Customer group added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer group", $this->unprocessableEntity);
        }

        $customer_group = CustomerGroup::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'group_name', 'group_code', 'status')
            ->first();

        if (!is_object($customer_group)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $customer_group->group_code = $request->group_code;
        $customer_group->group_name = $request->group_name;
        $customer_group->status = $request->status;
        $customer_group->save();

        return prepareResult(true, $customer_group, [], "Customer group updated successfully", $this->success);
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

        $customer_group = CustomerGroup::where('uuid', $uuid)
            ->first();

        if (is_object($customer_group)) {
            $customer_group->delete();
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
                'group_name'     => 'required',
                'status'     => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'customer_group_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating customer group.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->customer_group_ids;

            foreach ($uuids as $uuid) {
                CustomerGroup::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $Customer_group = $this->index();
            return prepareResult(true, $Customer_group, [], "Customer group status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->customer_group_ids;
            foreach ($uuids as $uuid) {
                CustomerGroup::where('uuid', $uuid)->delete();
            }

            $Customer_group = $this->index();
            return prepareResult(true, $Customer_group, [], "Customer group deleted success", $this->success);
        }
    }
}
