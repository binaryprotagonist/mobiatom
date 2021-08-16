<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\BankImport;
use App\Model\BankInformation;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class BankInformationController extends Controller
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

        if (!checkPermission('bank-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('bank-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $bank_information = BankInformation::select('id', 'uuid', 'organisation_id', 'bank_code', 'bank_name', 'bank_address', 'account_number', 'status')
            ->with('customFieldValueSave', 'customFieldValueSave.customField')
            ->orderBy('id', 'desc')
            ->get();

        $bank_information_array = array();
        if (is_object($bank_information)) {
            foreach ($bank_information as $key => $bank_information1) {
                $bank_information_array[] = $bank_information[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($bank_information_array[$offset])) {
                    $data_array[] = $bank_information_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($bank_information_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($bank_information_array);
        } else {
            $data_array = $bank_information_array;
        }

        return prepareResult(true, $data_array, [], "Bank Information listing", $this->success, $pagination);
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

        if (!checkPermission('bank-add')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating bank information", $this->success);
        }

        \DB::beginTransaction();
        try {

            $bank_information = new BankInformation;
            $bank_information->bank_name = $request->bank_name;
            $bank_information->bank_code = nextComingNumber('App\Model\BankInformation', 'bank_information', 'bank_code', $request->bank_code);
            $bank_information->bank_address = $request->bank_address;
            $bank_information->account_number = $request->account_number;
            $bank_information->status = $request->status;
            $bank_information->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($bank_information->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\BankInformation', 'bank_information');

            return prepareResult(true, $bank_information, [], "Bank Information added successfully", $this->success);
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

        if (!checkPermission('bank-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating bank information", $this->unauthorized);
        }

        $bank_information = BankInformation::where('uuid', $uuid)
            ->with('customFieldValueSave', 'customFieldValueSave.customField')
            ->first();

        if (!is_object($bank_information)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $bank_information, [], "Bank Information Edit", $this->success);
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

        if (!checkPermission('bank-edit')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating bank information", $this->success);
        }
        \DB::beginTransaction();
        try {
            $bank_information = BankInformation::where('uuid', $uuid)
                ->first();

            if (!is_object($bank_information)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            $bank_information->bank_name = $request->bank_name;
            $bank_information->bank_address = $request->bank_address;
            $bank_information->account_number = $request->account_number;
            $bank_information->status = $request->status;
            $bank_information->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                CustomFieldValueSave::where('record_id', $bank_information->id)->delete();
                foreach ($request->modules as $module) {
                    savecustomField($bank_information->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();

            return prepareResult(true, $bank_information, [], "Bank Information updated successfully", $this->success);
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

        if (!checkPermission('bank-delete')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Bank Information", $this->unauthorized);
        }

        $bank_information = BankInformation::where('uuid', $uuid)
            ->first();

        if (is_object($bank_information)) {
            $bank_information->delete();
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
                'bank_name' => 'required',
                'bank_address' => 'required',
                'account_number' => 'required',
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
                'bank_information_ids'     => 'required'
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating bank information.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->bank_information_ids;

            foreach ($uuids as $uuid) {
                BankInformation::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $bank_information = $this->index();
            return prepareResult(true, $bank_information, [], "Bank information status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->bank_information_ids;
            foreach ($uuids as $uuid) {
                BankInformation::where('uuid', $uuid)->delete();
            }

            $bank_information = $this->index();
            return prepareResult(true, $bank_information, [], "Bank information deleted success", $this->success);
        }
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'bank_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate bank import", $this->unauthorized);
        }
        $errors = array();
        //Excel::import(new BankImport, request()->file('bank_file'));
        try {
            $file = request()->file('bank_file')->store('import');
            $import = new BankImport($request->skipduplicate);
            $import->import($file);
            if (count($import->failures()) > 10) {
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
        return prepareResult(true, [], $errors, "Bank successfully imported", $this->success);
    }
}
