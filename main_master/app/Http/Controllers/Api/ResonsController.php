<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Reason;
use Illuminate\Http\Request;

class ResonsController extends Controller
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

        $reason = Reason::select('id', 'uuid', 'organisation_id', 'parent_id', 'name', 'node_level', 'status')
        ->with('children')
        ->whereNull('parent_id')
        ->orderBy('id', 'desc')
        ->get();

        $reason_array = array();
        if (is_object($reason)) {
            foreach ($reason as $key => $reason1) {
                $reason_array[] = $reason[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($reason_array[$offset])) {
                    $data_array[] = $reason_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($reason_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($reason_array);
        } else {
            $data_array = $reason_array;
        }

        return prepareResult(true, $data_array, [], "Reason listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allReason()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $reason = Reason::select('id', 'uuid', 'organisation_id', 'parent_id', 'name', 'node_level', 'status')
        // ->with('children')
        // ->whereNull('parent_id')
        ->orderBy('id', 'desc')
        ->get();

        $reason_array = array();
        if (is_object($reason)) {
            foreach ($reason as $key => $reason1) {
                $reason_array[] = $reason[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($reason_array[$offset])) {
                    $data_array[] = $reason_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($reason_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($reason_array);
        } else {
            $data_array = $reason_array;
        }

        return prepareResult(true, $data_array, [], "Reason listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating reason", $this->unprocessableEntity);
        }

        $reason = new Reason;
        $reason->parent_id = $request->parent_id;
        $reason->name = $request->name;
        $reason->node_level = $request->node_level;
        $reason->status = $request->status;
        $reason->save();

        if ($reason) {
            $reason->children;
            return prepareResult(true, $reason, [], "Reason added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating reason", $this->unauthorized);
        }

        $reason = Reason::where('uuid', $uuid)
            ->with('children')
            ->first();

        if (!is_object($reason)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $reason, [], "Reason Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating reason", $this->success);
        }

        $reason = Reason::where('uuid', $uuid)
            ->first();

        if (!is_object($reason)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $reason->parent_id = $request->parent_id;
        $reason->name = $request->name;
        $reason->node_level = $request->node_level;
        $reason->status = $request->status;
        $reason->save();

        $reason->children;
        return prepareResult(true, $reason, [], "Reason updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating Reason", $this->unauthorized);
        }

        $reason = Reason::where('uuid', $uuid)
            ->first();

        if (is_object($reason)) {
            $reason->delete();
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
                'parent_id' => 'nullable|integer|exists:reasons,id',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
