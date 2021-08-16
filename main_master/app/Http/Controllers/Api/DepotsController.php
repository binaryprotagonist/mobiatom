<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Depot;
use App\Imports\DepotImport;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class DepotsController extends Controller
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

        $depot = Depot::select('id', 'uuid', 'organisation_id', 'user_id', 'area_id', 'region_id', 'depot_code', 'depot_name', 'depot_manager', 'depot_manager_contact', 'status')
            ->with(
                'region:id,uuid,region_name',
                'area:id,uuid,area_name',
                'customFieldValueSave',
                'customFieldValueSave.customField'
            )
            ->orderBy('id', 'desc')
            ->get();

        $depot_array = array();
        if (is_object($depot)) {
            foreach ($depot as $key => $depot1) {
                $depot_array[] = $depot[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($depot_array[$offset])) {
                    $data_array[] = $depot_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($depot_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($depot_array);
        } else {
            $data_array = $depot_array;
        }

        return prepareResult(true, $data_array, [], "Depot listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating depots", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $depot = new Depot;
            $depot->user_id = $request->user_id;
            $depot->region_id = $request->region_id;
            // $depot->depot_code = $request->depot_code;
            $depot->depot_code = nextComingNumber('App\Model\Depot', 'depot', 'depot_code', $request->depot_code);
            $depot->depot_name = $request->depot_name;
            $depot->area_id = $request->area_id;
            $depot->depot_manager = $request->depot_manager;
            $depot->depot_manager_contact = $request->depot_manager_contact;
            $depot->status = $request->status;
            $depot->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($depot->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }
            
            \DB::commit();
            updateNextComingNumber('App\Model\Depot', 'depot');

            $depot->getSaveData();

            return prepareResult(true, $depot, [], "Depot added successfully", $this->created);
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

        $depot = Depot::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'user_id', 'region_id', 'area_id', 'depot_code', 'depot_name', 'depot_manager', 'depot_manager_contact', 'status')
            ->with('region:id,region_name,uuid', 'area:id,uuid,area_name', 'customFieldValueSave')
            ->first();

        if (is_object($depot)) {

            return prepareResult(true, $depot, [], "Depot listing", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
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
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors'], "Error while validating depots", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $depot = Depot::where('uuid', $uuid)
                ->first();

            if (!is_object($depot)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
            }

            $depot->user_id = $request->user_id;
            $depot->region_id = $request->region_id;
            $depot->depot_name = $request->depot_name;
            $depot->depot_manager = $request->depot_manager;
            $depot->depot_manager_contact = $request->depot_manager_contact;
            $depot->area_id = $request->area_id;
            $depot->status = $request->status;
            $depot->save();

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                CustomFieldValueSave::where('record_id', $depot->id)->delete();
                foreach ($request->modules as $module) {
                    savecustomField($depot->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            $depot->getSaveData();
            return prepareResult(true, $depot, [], "Depots added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating depots", $this->unauthorized);
        }

        $depot = Depot::where('uuid', $uuid)
            ->first();

        if (is_object($depot)) {
            $depot->delete();
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
                'region_id' => 'required|integer|exists:regions,id',
                'area_id' => 'required|integer|exists:areas,id',
                'depot_name' => 'required',
                'depot_code' => 'required',
                'depot_manager' => 'required',
                // 'user_id' => 'required|integer',
                // 'depot_manager_contact' => 'required',
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
                'depot_ids'     => 'required'
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

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'depot_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate van import", $this->unauthorized);
        }

        Excel::import(new DepotImport, request()->file('depot_file'));
        return prepareResult(true, [], [], "Depot successfully imported", $this->success);
    }
}
