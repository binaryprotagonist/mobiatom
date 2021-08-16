<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Channel;
use App\Model\CodeSetting;
use App\Model\Country;
use Illuminate\Http\Request;
use App\User;
use App\Model\CustomerInfo;
use App\Model\CustomerType;
use App\Model\PaymentTerm;
use Illuminate\Support\Facades\Hash;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleApprovalRole;
use App\Model\ActionHistory;

class ActionhistoryController extends Controller
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

        $ActionHistory = ActionHistory::with(
            'user:id,firstname,lastname,email',
            'organisation:id,org_name'
        )
            ->get();

        $ActionHistory_array = array();
        if (is_object($ActionHistory)) {
            foreach ($ActionHistory as $key => $raw) {
                $ActionHistory_array[] = $ActionHistory[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($ActionHistory_array[$offset])) {
                    $data_array[] = $ActionHistory_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($ActionHistory_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($ActionHistory_array);
        } else {
            $data_array = $ActionHistory_array;
        }
        
        return prepareResult(true, $data_array, [], "Action history listing", $this->success, $pagination);
    }

    public function listbymodule(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        if (!$request->module) {
            return prepareResult(false, [], [], "Module required.", $this->unauthorized);
        }
        if (!$request->module_id) {
            return prepareResult(false, [], [], "Module id required.", $this->unauthorized);
        }

        $action_history = ActionHistory::with(
            'user:id,firstname,lastname,email',
            'organisation:id,org_name'
        )
            ->where('module', $request->module)
            ->where('module_id', $request->module_id)
            ->get();

        $action_history_array = array();
        if (is_object($action_history)) {
            foreach ($action_history as $key => $raw) {
                $action_history_array[] = $action_history[$key];
            }
        }
        return prepareResult(true, $action_history_array, [], "Action history listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $action_history = new ActionHistory;
            $action_history->module = $request->module;
            $action_history->module_id = $request->module_id;
            $action_history->user_id = auth()->user()->id;
            $action_history->action = $request->action;
            $action_history->comment = $request->comment;
            $action_history->save();
            \DB::commit();

            $action_history->user;
            return prepareResult(true, $action_history, [], "Action history added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating Action History", $this->unauthorized);
        }

        $action_history = ActionHistory::where('uuid', $uuid)->first();

        if (is_object($action_history)) {
            $action_history->delete();
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
                'module' => 'required',
                'module_id' => 'required|integer',
                'action' => 'required',
                'comment' => 'required',
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
