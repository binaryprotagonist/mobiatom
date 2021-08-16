<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CustomerCategory;

class CustomerCategoryController extends Controller
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

        $customer_category = CustomerCategory::select('id', 'uuid', 'customer_category_name as name', 'parent_id', 'node_level', 'status')
        ->with('children')
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->get();

        $customer_category_array = array();
        if (is_object($customer_category)) {
            foreach ($customer_category as $key => $customer_category1) {
                $customer_category_array[] = $customer_category[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($customer_category_array[$offset])) {
                    $data_array[] = $customer_category_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($customer_category_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($customer_category_array);
        } else {
            $data_array = $customer_category_array;
        }
        return prepareResult(true, $data_array, [], "Customer Category listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->user = getUser();
        if (!is_object($this->user)) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating customer category", $this->success);
        }

        $customer_category = new CustomerCategory;
        // $customer_category->customer_category_code = nextComingNumber('App\Model\CustomerCategory', 'customer_category', 'customer_category_code', $request->customer_category_code);
        $customer_category->customer_category_name = $request->customer_category_name;
        $customer_category->parent_id = $request->parent_id;
        $customer_category->node_level = $request->node_level;
        $customer_category->status = $request->status;
        $customer_category->save();

        if ($customer_category) {
            // updateNextComingNumber('App\Model\CustomerCategory', 'customer_category');
            return prepareResult(true, $customer_category, [], "Customer category added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
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
            return prepareResult(false, [], [], "Error while validating customer category", $this->unauthorized);
        }

        $customer_category = CustomerCategory::where('uuid', $uuid)
            ->with('children')
            ->first();

        if (!is_object($customer_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $customer_category, [], "Customer Category Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating customer category", $this->unprocessableEntity);
        }

        $customer_category = CustomerCategory::where('uuid', $uuid)
            ->first();

        if (!is_object($customer_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $customer_category->customer_category_name = $request->customer_category_name;
        $customer_category->parent_id = $request->parent_id;
        $customer_category->node_level = $request->node_level;
        $customer_category->status = $request->status;
        $customer_category->save();

        return prepareResult(true, $customer_category, [], "Customer Category updated successfully", $this->success);
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

        $customer_category = CustomerCategory::where('uuid', $uuid)
            ->first();

        if (is_object($customer_category)) {
            $customer_category->delete();
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
                'customer_category_name' => 'required',
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
                'customer_category_ids'     => 'required'
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

        // if (!checkPermission('customer-category-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating customer category.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->customer_category_ids;

            foreach ($uuids as $uuid) {
                CustomerCategory::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $customer_category = $this->index();
            return prepareResult(true, $customer_category, [], "Customer Category status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->customer_category_ids;
            foreach ($uuids as $uuid) {
                CustomerCategory::where('uuid', $uuid)->delete();
            }

            $customer_category = $this->index();
            return prepareResult(true, $customer_category, [], "Customer Category deleted success", $this->success);
        } else if ($action == 'add') {
            $uuids = $request->customer_category_ids;
            foreach ($uuids as $uuid) {
                $customer_category = new CustomerCategory;
                $customer_category->customer_category_code = $uuid['customer_category_code'];
                $customer_category->customer_category_name = $uuid['customer_category_name'];
                $customer_category->status = $uuid['status'];
                $customer_category->save();
                updateNextComingNumber('App\Model\CustomerCategory', 'customer_category');
            }

            $customer_category = $this->index();
            return prepareResult(true, $customer_category, [], "Customer Category added success", $this->success);
        }
    }
}
