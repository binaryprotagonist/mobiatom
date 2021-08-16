<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CustomField;
use App\Model\CustomFieldValue;
use App\Model\Module;
use DB;

class CustomFieldController extends Controller
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

        $customfields = CustomField::select('id', 'uuid', 'organisation_id', 'module_id', 'field_type', 'field_label', 'status')
            ->with('customFieldValue:id,custom_field_id,field_value')
            ->get();

        $customfields_array = array();
        if (is_object($customfields)) {
            foreach ($customfields as $key => $customfields1) {
                $customfields_array[] = $customfields[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($customfields_array[$offset])) {
                    $data_array[] = $customfields_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($customfields_array) / $limit);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $customfields_array;
        }
        return prepareResult(true, $data_array, [], "Custom field listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating custom field", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            if ($request->field_type == 'check_box' || $request->field_type == 'dropdown' || $request->field_type == 'multi_select') {
                if (is_array($request->field_value) && sizeof($request->field_value) < 1) {
                    return prepareResult(false, [], [], "Error Please add atleast one field value.", $this->unprocessableEntity);
                }
            }

            $custom_field = new CustomField;
            $custom_field->module_id = $request->module_id;
            $custom_field->field_type = $request->field_type;
            $custom_field->field_label = $request->field_label;
            $custom_field->save();

            if (is_array($request->field_value) && sizeof($request->field_value) >= 1) {
                foreach ($request->field_value as $value) {
                    $custom_field_value = new CustomFieldValue;
                    $custom_field_value->custom_field_id = $custom_field->id;
                    $custom_field_value->field_value = $value;
                    $custom_field_value->save();
                }
            }

            \DB::commit();
            return prepareResult(true, $custom_field, [], "Custom field successfully", $this->created);
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

        $customfields = CustomField::select('id', 'uuid', 'organisation_id', 'module_id', 'field_type', 'field_label', 'status')
            ->with('customFieldValue:id,custom_field_id,field_value')
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($customfields)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $customfields, [], "Custom Field Edit", $this->success);
    }

    public function getmoulewisecustomfield($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }
        if (!$id) {
            return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
        }

        $customfields = CustomField::with('customFieldValue:id,custom_field_id,field_value')
            ->where('module_id', $id)
            ->get();

        if (!is_object($customfields)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $customfields, [], "Custom Field Get Module Wise", $this->success);
    }

    public function getmodule()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $models = Module::select('id', 'module_name')->get();

        if (!is_object($models)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $models, [], "Modules List", $this->success);
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


        \DB::beginTransaction();
        try {

            if ($request->field_type == 'check_box' || $request->field_type == 'dropdown' || $request->field_type == 'multi_select') {
                if (is_array($request->field_value) && sizeof($request->field_value) < 1) {
                    return prepareResult(false, [], [], "Error Please add atleast one field value.", $this->unprocessableEntity);
                }
            }

            $custom_field = CustomField::where('uuid', $uuid)->first();
            $custom_field_value = CustomFieldValue::where('custom_field_id', $custom_field->id)->forceDelete();


            $custom_field->module_id = $request->module_id;
            $custom_field->field_type = $request->field_type;
            $custom_field->field_label = $request->field_label;
            $custom_field->save();

            if (is_array($request->field_value)) {
                foreach ($request->field_value as $value) {
                    $custom_field_value = new CustomFieldValue;
                    $custom_field_value->custom_field_id = $custom_field->id;
                    $custom_field_value->field_value = $value;
                    $custom_field_value->save();
                }
            }

            \DB::commit();
            return prepareResult(true, $custom_field, [], "Custom field updated successfully", $this->created);
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

        $customfields = CustomField::where('uuid', $uuid)
            ->first();

        if (is_object($customfields)) {
            // $customfieldsId = $customfields->id;
            $customfields->delete();

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
        $uuids = $request->custom_field_ids;
        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }
        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                CustomField::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $customfields = $this->index();
            return prepareResult(true, $customfields, [], "Custom field status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $customfields = CustomField::where('uuid', $uuid)
                    ->first();
                $customfieldsId = $customfields->id;
                $customfields->delete();
            }
            $customfields = $this->index();
            return prepareResult(true, $customfields, [], "Custom field deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'module_id' => 'required',
                'field_type' => 'required',
                'field_label' => 'required',
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'custom_field_ids' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
