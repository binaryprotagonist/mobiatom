<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\ItemGroup;

class ItemGroupController extends Controller
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

        $item_group = ItemGroup::select('id', 'uuid', 'organisation_id', 'code', 'name', 'status')
            ->orderBy('id', 'desc')
            ->get();

        $item_group_array = array();
        if (is_object($item_group)) {
            foreach ($item_group as $key => $item_group1) {
                $item_group_array[] = $item_group[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($item_group_array[$offset])) {
                    $data_array[] = $item_group_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($item_group_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($item_group_array);
        } else {
            $data_array = $item_group_array;
        }
        return prepareResult(true, $data_array, [], "Item group listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item group", $this->unprocessableEntity);
        }

        $item_group = new ItemGroup;
        $item_group->code = nextComingNumber('App\Model\ItemGroup', 'item_group', 'code', $request->code);
        $item_group->name = $request->name;
        $item_group->status = $request->status;
        $item_group->save();

        if ($item_group) {
            updateNextComingNumber('App\Model\ItemGroup', 'item_group');
            return prepareResult(true, $item_group, [], "Item group added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating item sub category", $this->unauthorized);
        }

        $item_group = ItemGroup::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'code', 'name', 'status')
            ->first();

        if (!is_object($item_group)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $item_group, [], "Item group category Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item group", $this->unprocessableEntity);
        }

        $item_group = ItemGroup::where('uuid', $uuid)
            ->first();

        if (!is_object($item_group)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $item_group->name = $request->name;
        $item_group->status = $request->status;
        $item_group->save();

        return prepareResult(true, $item_group, [], "Item group updated successfully", $this->success);
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

        $item_group = ItemGroup::where('uuid', $uuid)
            ->first();

        if (is_object($item_group)) {
            $item_group->delete();
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
                'code' => 'required',
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
                'item_group_ids'     => 'required'
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item group.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->item_group_ids;

            foreach ($uuids as $uuid) {
                ItemGroup::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $item_group = $this->index();
            return prepareResult(true, $item_group, [], "Item Group status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->item_group_ids;
            foreach ($uuids as $uuid) {
                ItemGroup::where('uuid', $uuid)->delete();
            }

            $item_group = $this->index();
            return prepareResult(true, $item_group, [], "Item Group deleted success", $this->success);
        } else if ($action == 'add') {
            $uuids = $request->item_group_ids;
            foreach ($uuids as $uuid) {
                $item_group = new ItemGroup;
                $item_group->code = $uuid['code'];
                $item_group->name = $uuid['name'];
                $item_group->status = $uuid['status'];
                $item_group->save();
                updateNextComingNumber('App\Model\ItemGroup', 'item_group');
            }

            $item_group = $this->index();
            return prepareResult(true, $item_group, [], "Item Group added success", $this->success);
        }
    }
}
