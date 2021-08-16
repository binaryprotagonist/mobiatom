<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
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

        $expense_category = ExpenseCategory::select('id', 'uuid', 'organisation_id', 'name', 'description', 'status')
            ->orderBy('id', 'desc')
            ->get();

        $expense_category_array = array();
        if (is_object($expense_category)) {
            foreach ($expense_category as $key => $expense_category1) {
                $expense_category_array[] = $expense_category[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($expense_category_array[$offset])) {
                    $data_array[] = $expense_category_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($expense_category_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($expense_category_array);
        } else {
            $data_array = $expense_category_array;
        }

        return prepareResult(true, $data_array, [], "Expense category listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating expense", $this->success);
        }

        $expense_category = new ExpenseCategory;
        $expense_category->name = $request->name;
        $expense_category->description = $request->description;
        $expense_category->status = $request->status;
        $expense_category->save();

        if ($expense_category) {
            return prepareResult(true, $expense_category, [], "Expense category added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating expense category", $this->unauthorized);
        }

        $expense_category = ExpenseCategory::where('uuid', $uuid)
            ->first();

        if (!is_object($expense_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $expense_category, [], "Expense category edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating expense category", $this->success);
        }

        $expense_category = ExpenseCategory::where('uuid', $uuid)
            ->first();

        if (!is_object($expense_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $expense_category->name = $request->name;
        $expense_category->description = $request->description;
        $expense_category->status = $request->status;
        $expense_category->save();

        return prepareResult(true, $expense_category, [], "Expense category updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating expense category", $this->unauthorized);
        }

        $expense_category = ExpenseCategory::where('uuid', $uuid)
            ->first();

        if (is_object($expense_category)) {
            $expense_category->delete();
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
                'name' => 'required',
                'status' => 'required'
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating depot.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->expense_category_ids;

            foreach ($uuids as $uuid) {
                ExpenseCategory::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $expense_category = $this->index();
            return prepareResult(true, $expense_category, [], "Expense Category status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->expense_category_ids;
            foreach ($uuids as $uuid) {
                ExpenseCategory::where('uuid', $uuid)->delete();
            }

            $expense_category = $this->index();
            return prepareResult(true, $expense_category, [], "Expense Category deleted success", $this->success);
        }
    }
}
