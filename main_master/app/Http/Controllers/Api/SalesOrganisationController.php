<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\SalesOrganisation;

class SalesOrganisationController extends Controller
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

        $sales_organisation = SalesOrganisation::select('id', 'uuid', 'organisation_id', 'name', 'node_level','status')
        ->with(
            'customerInfos:id,uuid,user_id,sales_organisation_id,region_id,route_id,channel_id',
            'customerInfos.user:id,uuid,firstname,lastname',
            'children'
        )
        ->whereNull('parent_id')
        ->orderBy('id', 'desc')
        ->get();

        $sales_organisation_array = array();
        if (is_object($sales_organisation)) {
            foreach ($sales_organisation as $key => $sales_organisation1) {
                $sales_organisation_array[] = $sales_organisation[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($sales_organisation_array[$offset])) {
                    $data_array[] = $sales_organisation_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($sales_organisation_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($sales_organisation_array);
        } else {
            $data_array = $sales_organisation_array;
        }

        return prepareResult(true, $data_array, [], "Sales Organisation listing", $this->success, $pagination);

        // return prepareResult(true, $sales_organisation, [], "Sales Organisation listing", $this->success);
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

        $sales_organisation = SalesOrganisation::select('id', 'uuid', 'organisation_id', 'name', 'node_level', 'status')
            ->with('children')
            ->where('uuid', $uuid)
            ->first();

        if (is_object($sales_organisation)) {
            return prepareResult(true, $sales_organisation, [], "Sales Organisation added successfully", $this->success);
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

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating SalesOrganisation", $this->unprocessableEntity);
        }

        $sales_organisation = new SalesOrganisation;
        $sales_organisation->parent_id = $request->parent_id;
        $sales_organisation->name = $request->name;
        $sales_organisation->node_level = $request->node_level;
        $sales_organisation->status = $request->status;
        $sales_organisation->save();

        if (is_object($sales_organisation)) {
            return prepareResult(true, $sales_organisation, [], "Sales Organisation added successfully", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating SalesOrganisation", $this->unprocessableEntity);
        }

        $sales_organisation = SalesOrganisation::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'name', 'node_level', 'status')
            ->first();

        if (!is_object($sales_organisation)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $sales_organisation->parent_id = $request->parent_id;
        $sales_organisation->name = $request->name;
        $sales_organisation->node_level = $request->node_level;
        $sales_organisation->status = $request->status;
        $sales_organisation->save();

        return prepareResult(true, $sales_organisation, [], "Sales Organisation updated successfully", $this->success);
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

        $sales_organisation = SalesOrganisation::where('uuid', $uuid)
            ->first();

        if (is_object($sales_organisation)) {
            $sales_organisation->delete();
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
                'parent_id' => 'nullable|integer|exists:sales_organisations,id',
                'name'     => 'required',
                'status'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
