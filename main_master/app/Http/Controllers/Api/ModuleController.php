<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Module;
use App\Model\ModuleMaster;
use App\Model\Route;
use DB;
use Illuminate\Database\Eloquent\Model;

class ModuleController extends Controller
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

        $modulesMaster = ModuleMaster::select('id', 'module_name', 'custom_field_status')
            ->with('module:id,uuid,organisation_id,module_master_id,module_name,custom_field_status')
            ->orderBy('id', 'desc')
            ->get();

        $modules = array();
        foreach ($modulesMaster as $key => $modulemst) {
            if ($modulemst->module) {
                if ($modulemst->module->custom_field_status) {

                    $modules[] = $modulemst->module;
                }
            } else {
                $modules[] = $modulemst;
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($modules[$offset])) {
                    $data_array[] = $modules[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($modules) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($modules);
        } else {
            $data_array = $modules;
        }
        return prepareResult(true, $data_array, [], "Module listing", $this->success, $pagination);

        // if (sizeof($modules) < 1) {
        //     $modules = ModuleMaster::select('id', 'module_name', 'custom_field_status')->get();
        // }

        // $modules = Module::Where('organisation_id', auth()->user()->organisation_id)->get();
        // $modulesMaster = ModuleMaster::select('id', 'module_name', 'custom_field_status')->get();

        // $finalmodeiidarray = array();
        // foreach ($modules as $mall) {
        //     pre($mall);
        //     array_push($finalmodeiidarray, $mall->module_master_id);
        // }

        // foreach ($modulesMaster as $mm) {
        //     if (in_array($mm->id, $finalmodeiidarray)) {
        //         $mm->custom_field_status = 1;
        //     } else {
        //         $mm->custom_field_status = 0;
        //     }
        // }

        // return prepareResult(true, $modules, [], "Module listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }
        if (is_array($request->module_id) && sizeof($request->module_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one modules.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            if (count($request->module_id) > 0) {

                Module::where('organisation_id', auth()->user()->organisation_id)->forceDelete();

                foreach ($request->module_id as $module_id) {
                    $modules_master = ModuleMaster::find($module_id);
                    $modules = new Module;
                    $modules->module_master_id = $module_id;
                    $modules->module_name = $modules_master->module_name;
                    $modules->custom_field_status = 1;
                    $modules->save();
                }
            }

            $modulesMaster = ModuleMaster::select('id', 'module_name', 'custom_field_status')
                ->with('module:id,uuid,organisation_id,module_master_id,module_name,custom_field_status')
                ->get();

            $modules = array();
            foreach ($modulesMaster as $key => $modulemst) {
                if ($modulemst->module) {
                    $modules[] = $modulemst->module;
                    // $modulemst->module->module_name = $modulemst->module_name;
                }
            }

            // $modules = Module::Where('organisation_id', $request->organisation_id)->get();
            // $modulesMaster = ModuleMaster::select('id', 'uuid', 'module_name', 'custom_field_status')->get();

            // $finalmodeiidarray = array();
            // foreach ($modules as $mall) {
            //     array_push($finalmodeiidarray, $mall->module_master_id);
            // }

            // foreach ($modulesMaster as $mm) {
            //     if (in_array($mm->id, $finalmodeiidarray)) {
            //         $mm->custom_field_status = 1;
            //     } else {
            //         $mm->custom_field_status = 0;
            //     }
            // }

            \DB::commit();

            return prepareResult(true, $modules, [], "Module successfully chages", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }
        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
        }

        $modules = Module::select('id', 'uuid', 'organisation_id', 'module_master_id', 'module_name', 'custom_field_status')
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($modules)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $modules, [], "Module Edit", $this->success);
    }

    public function checkstatus(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        $input = $request->json()->all();
        $validate = $this->validations($input, "checkstatus");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating module", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $checkstatus  = Module::where('module_master_id', $request->module_id)
                ->get();

            if (count($checkstatus) > 0) {
                $modulesMaster = array('custom_field_status' => 1);
            } else {
                $modulesMaster = array('custom_field_status' => 0);
            }

            \DB::commit();
            return prepareResult(true, $modulesMaster, [], "Get custome field status", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (is_array($request->modules) && sizeof($request->modules) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one modules.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $modules = Module::where('uuid', $uuid)->first();
            $modules->custom_field_status = (!empty($request->custom_field_status)) ? $request->custom_field_status : 0;
            $modules->save();

            \DB::commit();
            return prepareResult(true, $modules, [], "Module updated successfully", $this->created);
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
        $modules = Module::where('uuid', $uuid)->first();
        if (is_object($modules)) {
            $modules->delete();
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
        $uuids = $request->module_ids;
        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }
        if ($action == 'active' || $action == 'inactive') {
            foreach ($uuids as $uuid) {
                Module::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }
            $modules = $this->index();
            return prepareResult(true, $modules, [], "Module status updated", $this->success);
        } else if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $modules = Module::where('uuid', $uuid)
                    ->first();
                $modules->delete();
            }
            $modules = $this->index();
            return prepareResult(true, $modules, [], "Module deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == 'add') {
            $validator = \Validator::make($input, [
                'module_id' => 'required'
            ]);
        }
        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'module_ids' => 'required'
            ]);
        }
        if ($type == 'checkstatus') {
            $validator = \Validator::make($input, [
                'module_id' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
