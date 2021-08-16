<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\VanType;

class VanTypeController extends Controller
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

        $van_type = VanType::select('id', 'uuid', 'organisation_id', 'name', 'parent_id', 'node_level', 'status')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->get();

        $van_type_array = array();
        if (is_object($van_type)) {
            foreach ($van_type as $key => $van_type1) {
                $van_type_array[] = $van_type[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($van_type_array[$offset])) {
                    $data_array[] = $van_type_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($van_type_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($van_type_array);
        } else {
            $data_array = $van_type_array;
        }

        return prepareResult(true, $data_array, [], "Van type listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating van type", $this->unprocessableEntity);
        }

        $van_type = new VanType;
        // $van_type->code = nextComingNumber('App\Model\VanType', 'van_type', 'code', $request->code);
        $van_type->name = $request->name;
        $van_type->parent_id = $request->parent_id;
        $van_type->node_level = $request->node_level;
        $van_type->status = $request->status;
        $van_type->save();

        if ($van_type) {
            // updateNextComingNumber('App\Model\VanType', 'van_type');
            return prepareResult(true, $van_type, [], "Van type added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        // $validate = $this->validations($input, "vanTypeShow");
        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating van", $this->success);
        }

        $van_type = VanType::where('uuid', $uuid)
        ->with('children')
            ->first();

        if (is_object($van_type)) {
            return prepareResult(true, $van_type, [], "Van type show successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
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
            return prepareResult(false, [], [], "Unauthorized access", $this->unprocessableEntity);
        }

        $van_type = VanType::where('uuid', $uuid)
            ->first();

        if (!is_object($van_type)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $van_type->name = $request->name;
        $van_type->parent_id = $request->parent_id;
        $van_type->node_level = $request->node_level;
        $van_type->status = $request->status;
        $van_type->save();

        return prepareResult(true, $van_type, [], "Van type updated successfully", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
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

        $van_type = VanType::where('uuid', $uuid)
            ->first();

        if (is_object($van_type)) {
            $van_type->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate.", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'name'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "vanTypeShow") {
            $validator = \Validator::make($input, [
                'uuid'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
