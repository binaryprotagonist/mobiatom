<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\VanCategory;

class VanCategoryController extends Controller
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

        $van_category = VanCategory::select('id', 'uuid', 'organisation_id', 'parent_id', 'node_level', 'name', 'status')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->get();

            $van_category_array = array();
        if (is_object($van_category)) {
            foreach ($van_category as $key => $van_category1) {
                $van_category_array[] = $van_category[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($van_category_array[$offset])) {
                    $data_array[] = $van_category_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($van_category_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($van_category_array);
        } else {
            $data_array = $van_category_array;
        }

        return prepareResult(true, $data_array, [], "Van category listing", $this->success, $pagination);
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

        $van_category = VanCategory::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'name', 'parent_id', 'node_level', 'status')
            ->with('children')
            ->first();

        if ($van_category) {
            return prepareResult(true, $van_category, [], "Van category edit successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
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

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating van category", $this->unprocessableEntity);
        }

        $van_category = new VanCategory;
        // $van_category->code = $request->code;
        // $van_category->code = nextComingNumber('App\Model\VanCategory', 'van_category', 'code', $request->code);
        $van_category->name = $request->name;
        $van_category->parent_id = $request->parent_id;
        $van_category->node_level = $request->node_level;
        $van_category->save();

        if ($van_category) {
            // updateNextComingNumber('App\Model\VanCategory', 'van_category');
            return prepareResult(true, $van_category, [], "Van category added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating van category", $this->unprocessableEntity);
        }

        $van_category = VanCategory::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'name', 'parent_id', 'node_level', 'status')
            ->first();

        if (!is_object($van_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $van_category->name = $request->name;
        $van_category->parent_id = $request->parent_id;
        $van_category->node_level = $request->node_level;
        $van_category->save();

        return prepareResult(true, $van_category, [], "Van category updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating area", $this->unprocessableEntity);
        }

        $van_category = VanCategory::where('uuid', $uuid)
            ->first();

        if (is_object($van_category)) {
            $van_category->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unprocessableEntity);
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

        return ["error" => $error, "errors" => $errors];
    }
}
