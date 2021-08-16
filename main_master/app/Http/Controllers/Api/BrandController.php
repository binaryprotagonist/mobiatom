<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Brand;

class BrandController extends Controller
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

        // if (!checkPermission('brand-list')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }
        //
        // if (!$this->user->can('brand-list') && $this->user->role_id != '1') {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $brand = Brand::select('id', 'uuid', 'organisation_id', 'parent_id', 'brand_name', 'node_level', 'status')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->get();

        $brand_array = array();
        if (is_object($brand)) {
            foreach ($brand as $key => $brand1) {
                $brand_array[] = $brand[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($brand_array[$offset])) {
                    $data_array[] = $brand_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($brand_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($brand_array);
        } else {
            $data_array = $brand_array;
        }

        return prepareResult(true, $data_array, [], "Brand listing", $this->success, $pagination);
    }

    /**
     * Edit the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // if (!checkPermission('brand-edit')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $brand = Brand::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'parent_id', 'brand_name', 'node_level', 'status')
            ->with('children')
            ->first();

        if (is_object($brand)) {
            return prepareResult(true, $brand, [], "Brand edit successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
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
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        // if (!checkPermission('brand-add')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Brand", $this->unprocessableEntity);
        }

        $brand = new Brand;
        $brand->parent_id = $request->parent_id;
        $brand->brand_name = $request->brand_name;
        $brand->node_level = $request->node_level;
        $brand->status = $request->status;
        $brand->save();

        if (is_object($brand)) {
            $brand->children;
            return prepareResult(true, $brand, [], "Brand added successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
    }

    /**
     * Update a created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Brand", $this->unprocessableEntity);
        }

        $brand = Brand::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'brand_name', 'node_level', 'status')
            ->first();

        if (!is_object($brand)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }
        $brand->parent_id = $request->parent_id;
        $brand->brand_name = $request->brand_name;
        $brand->status = $request->status;
        $brand->save();

        return prepareResult(true, $brand, [], "Brand updated successfully", $this->success);
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

        // if (!checkPermission('brand-delete')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $brand = Brand::where('uuid', $uuid)
            ->first();

        if (is_object($brand)) {
            $brand->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'parent_id' => 'nullable|integer|exists:brands,id',
                'brand_name'     => 'required',
                'status'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'brand_ids'     => 'required'
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating brand.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->brand_ids;

            foreach ($uuids as $uuid) {
                Brand::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $brand = $this->index();
            return prepareResult(true, $brand, [], "Brand status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->brand_ids;
            foreach ($uuids as $uuid) {
                Brand::where('uuid', $uuid)->delete();
            }

            $brand = $this->index();
            return prepareResult(true, $brand, [], "Brand deleted success", $this->success);
        }
    }
}
