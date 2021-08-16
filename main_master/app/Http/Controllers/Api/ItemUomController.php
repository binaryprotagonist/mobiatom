<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ItemuomImport;
use Illuminate\Http\Request;
use App\Model\ItemUom;
use App\Model\ItemUomMaster;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ItemUomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($status = null)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $item_uom = ItemUom::query();
        if ($status) {
            $item_uom->where('status', 1);
        }
        $rec = $item_uom->orderBy('id', 'desc')->get();

        // $item_uom_master = ItemUomMaster::query();
        // if ($status) {
        //     $item_uom_master->where('status', 1);
        // }
        // $recMaster = $item_uom_master->get();
        // $combine_array = array_merge($rec->toArray(), $recMaster->toArray());

        $rec_array = array();
        if (is_object($rec)) {
            foreach ($rec as $key => $rec1) {
                $rec_array[] = $rec[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($rec_array[$offset])) {
                    $data_array[] = $rec_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($rec_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($rec_array);
        } else {
            $data_array = $rec_array;
        }

        return prepareResult(true, $data_array, [], "Item Uoms listing", $this->success, $pagination);
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

        $item_uom = ItemUom::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'name', 'status')
            ->first();

        if (is_object($item_uom)) {
            return prepareResult(true, $item_uom, [], "Item Uoms added successfully", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item uom", $this->unprocessableEntity);
        }

        $item_uom = new ItemUom;
        $item_uom->code = nextComingNumber('App\Model\ItemUom', 'item_uoms', 'code', $request->code);
        // $item_uom->code = $request->code;
        $item_uom->name = $request->name;
        $item_uom->status = $request->status;
        $item_uom->save();

        if ($item_uom) {
            updateNextComingNumber('App\Model\ItemUom', 'item_uoms');
            return prepareResult(true, $item_uom, [], "Item uom added successfully", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Item uom", $this->unprocessableEntity);
        }

        $item_uom = ItemUom::where('uuid', $uuid)
            ->select('id', 'uuid', 'organisation_id', 'name', 'status')
            ->first();

        if (!is_object($item_uom)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        $item_uom->name = $request->name;
        $item_uom->status = $request->status;
        $item_uom->save();

        return prepareResult(true, $item_uom, [], "Item uom updated successfully", $this->success);
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

        $item_uom = ItemUom::where('uuid', $uuid)
            ->first();

        if (is_object($item_uom)) {
            $item_uom->delete();
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
                'name'     => 'required',
                'code'     => 'required',
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
                'item_uom_ids'     => 'required'
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
            $uuids = $request->item_uom_ids;

            foreach ($uuids as $uuid) {
                ItemUom::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $item_group = $this->index();
            return prepareResult(true, $item_group, [], "Item Group status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->item_uom_ids;
            foreach ($uuids as $uuid) {
                ItemUom::where('uuid', $uuid)->delete();
            }

            $item_group = $this->index();
            return prepareResult(true, $item_group, [], "Item Group deleted success", $this->success);
        } else if ($action == 'add') {
            $uuids = $request->item_uom_ids;
            foreach ($uuids as $uuid) {
                $item_group = new ItemUom;
                $item_group->code = $uuid['code'];
                $item_group->name = $uuid['name'];
                $item_group->status = $uuid['status'];
                $item_group->save();
                updateNextComingNumber('App\Model\ItemGroup', 'item_uoms');
            }

            $item_group = $this->index();
            return prepareResult(true, $item_group, [], "Item Group added success", $this->success);
        }
    }

    public function import(Request $request)
	{
		if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
		
		$validator = Validator::make($request->all(), [
		   'itemuom_file'=> 'required|mimes:xlsx,xls,csv'
		]);
		
		if ($validator->fails()) {
			$error = $validator->messages()->first();
			return prepareResult(false, [], $error, "Failed to validate Item uom import", $this->unauthorized);
		}

		Excel::import(new ItemuomImport, request()->file('itemuom_file'));
		return prepareResult(true, [], [], "Item uom successfully imported", $this->success);
	}
}