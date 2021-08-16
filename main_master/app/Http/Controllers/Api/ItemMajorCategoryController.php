<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\ItemMajorCategory;

class ItemMajorCategoryController extends Controller
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

        $item_major_category = ItemMajorCategory::select('id', 'uuid', 'organisation_id', 'parent_id', 'name', 'node_level', 'status')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->get();

        $item_major_category_array = array();
        if (is_object($item_major_category)) {
            foreach ($item_major_category as $key => $item_major_category1) {
                $item_major_category_array[] = $item_major_category[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($item_major_category_array[$offset])) {
                    $data_array[] = $item_major_category_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($item_major_category_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($item_major_category_array);
        } else {
            $data_array = $item_major_category_array;
        }

        return prepareResult(true, $data_array, [], "Item major category listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item major category", $this->unprocessableEntity);
        }

        $item_major_category = new ItemMajorCategory;
        $item_major_category->parent_id = $request->parent_id;
        $item_major_category->name = $request->name;
        $item_major_category->node_level = $request->node_level;
        $item_major_category->status = $request->status;
        $item_major_category->save();

        if ($item_major_category) {
            $item_major_category->children;
            return prepareResult(true, $item_major_category, [], "Item major category added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating item major category", $this->unprocessableEntity);
        }

        $item_major_category = ItemMajorCategory::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'parent_id', 'name', 'node_level', 'status')
            ->with('children')
            ->first();

        if (!is_object($item_major_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $item_major_category, [], "Item major category Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item major category", $this->unprocessableEntity);
        }

        $item_major_category = ItemMajorCategory::where('uuid', $uuid)
            ->first();

        if (!is_object($item_major_category)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $item_major_category->parent_id = $request->parent_id;
        $item_major_category->name = $request->name;
        $item_major_category->node_level = $request->node_level;
        $item_major_category->status = $request->status;
        $item_major_category->save();

        $item_major_category->children;

        return prepareResult(true, $item_major_category, [], "Item major category updated successfully", $this->success);
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

        $item_major_category = ItemMajorCategory::where('uuid', $uuid)
            ->first();

        if (is_object($item_major_category)) {
            $item_major_category->delete();
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
                'parent_id' => 'nullable|integer|exists:item_major_categories,id',
                'name' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
