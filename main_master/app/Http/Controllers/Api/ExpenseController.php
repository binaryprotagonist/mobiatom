<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ExpensesImport;
use App\Model\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
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

        $expense = Expense::select('id', 'uuid', 'customer_id', 'expense_category_id', 'reference', 'description', 'amount', 'expense_date', 'status', 'lob_id')
            ->with(
                'expenseCategory:id,name',
                'customer:id,firstname,lastname',
                'customer.customerinfo:id,user_id,customer_code',
                'lob'
            )
            ->orderBy('id', 'desc')
            ->get();

        $expense_array = array();
        if (is_object($expense)) {
            foreach ($expense as $key => $expense1) {
                $expense_array[] = $expense[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($expense_array[$offset])) {
                    $data_array[] = $expense_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($expense_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($expense_array);
        } else {
            $data_array = $expense_array;
        }

        return prepareResult(true, $data_array, [], "Expense listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating expense", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $expense = new Expense;
            $expense->expense_category_id = $request->expense_category_id;
            $expense->customer_id = $request->customer_id;
            $expense->expense_date = $request->expense_date;
            $expense->description = $request->description;
            $expense->amount = $request->amount;
            $expense->reference = $request->reference;
            $expense->status = $request->status;
            $expense->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $expense->save();

            \DB::commit();

            $expense->expenseCategory;
            $expense->customer;
            $expense->lob;

            return prepareResult(true, $expense, [], "Expense added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again. 6666", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.88888", $this->internal_server_error);
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

        $expense = Expense::where('uuid', $uuid)
            ->with('expenseCategory:id,name', 'customer:id,firstname,lastname', 'lob')
            ->first();

        if (!is_object($expense)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $expense, [], "Expense edit", $this->success);
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

        $expense = Expense::where('uuid', $uuid)
            ->first();

        if (!is_object($expense)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        \DB::beginTransaction();
        try {

            $expense->expense_category_id = $request->expense_category_id;
            $expense->customer_id = $request->customer_id;
            $expense->expense_date = $request->expense_date;
            $expense->description = $request->description;
            $expense->amount = $request->amount;
            $expense->reference = $request->reference;
            $expense->status = $request->status;
            $expense->lob_id = (!empty($request->lob_id)) ? $request->lob_id : null;
            $expense->save();

            \DB::commit();

            $expense->expenseCategory;
            $expense->customer;
            $expense->lob;

            return prepareResult(true, $expense, [], "Expense updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating expense category", $this->unauthorized);
        }

        $expense = Expense::where('uuid', $uuid)
            ->first();

        if (is_object($expense)) {
            $expense->delete();
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
                'expense_category_id' => 'required|integer|exists:expense_categories,id',
                'customer_id' => 'required|integer|exists:users,id',
                'expense_date' => 'required',
                'amount' => 'required',
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
            'expenses_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate expenses import", $this->unauthorized);
        }

        Excel::import(new ExpensesImport, request()->file('expenses_file'));
        return prepareResult(true, [], [], "Expenses order successfully imported", $this->success);
    }
}
