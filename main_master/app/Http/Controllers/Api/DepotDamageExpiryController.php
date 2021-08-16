<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\DepotDamageExpiry;
use App\Model\DepotDamageExpiryDetail;

class DepotDamageExpiryController extends Controller
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

        $DepotDamageExpiry = DepotDamageExpiry::with(
            'depot:id,depot_name',
            'depotdamageexpiryDetail',
            'depotdamageexpiryDetail.item:id,item_name',
            'depotdamageexpiryDetail.itemUom:id,name,code',
            'depotdamageexpiryDetail.reason:id,name,parent_id'
        )
        ->orderBy('id', 'desc')
            ->get();

        $DepotDamageExpiry_array = array();
        if (is_object($DepotDamageExpiry)) {
            foreach ($DepotDamageExpiry as $key => $DepotDamageExpiry1) {
                $DepotDamageExpiry_array[] = $DepotDamageExpiry[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($DepotDamageExpiry_array[$offset])) {
                    $data_array[] = $DepotDamageExpiry_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($DepotDamageExpiry_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($DepotDamageExpiry_array);
        } else {
            $data_array = $DepotDamageExpiry_array;
        }

        return prepareResult(true, $data_array, [], "Depot damage expiry listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating depot damage expiry", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $depotdamageexpiry = new DepotDamageExpiry;
            $depotdamageexpiry->depot_id            = (!empty($request->depot_id)) ? $request->depot_id : null;
            $depotdamageexpiry->reference_code            = (!empty($request->reference_code)) ? $request->reference_code : null;
            $depotdamageexpiry->reference_code            = nextComingNumber('App\Model\DepotDamageExpiry', 'depot_damage_expiry', 'reference_code', $request->reference_code);
            $depotdamageexpiry->date       = date('Y-m-d', strtotime($request->date));
            $depotdamageexpiry->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $depotdamageexpiryDetail = new DepotDamageExpiryDetail;
                    $depotdamageexpiryDetail->depotdamageexpiry_id      = $depotdamageexpiry->id;
                    $depotdamageexpiryDetail->item_id       = $item['item_id'];
                    $depotdamageexpiryDetail->item_uom_id   = $item['item_uom_id'];
                    $depotdamageexpiryDetail->qty   = $item['qty'];
                    $depotdamageexpiryDetail->reason_id       = $item['reason_id'];
                    $depotdamageexpiryDetail->save();
                }
            }

            
            \DB::commit();
            updateNextComingNumber('App\Model\DepotDamageExpiry', 'depot_damage_expiry');

            $depotdamageexpiry->getSaveData();

            return prepareResult(true, $depotdamageexpiry, [], "Depot damage expiry added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
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
            return prepareResult(false, [], [], "Error while validating Depot Damage Expiry.", $this->unauthorized);
        }

        $DepotDamageExpiry = DepotDamageExpiry::with(
            'depot:id,depot_name',
            'depotdamageexpiryDetail',
            'depotdamageexpiryDetail.item:id,item_name',
            'depotdamageexpiryDetail.itemUom:id,name,code',
            'depotdamageexpiryDetail.reason:id,name,parent_id'
        )
            ->where('uuid', $uuid)
            ->first();


        if (!is_object($DepotDamageExpiry)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $DepotDamageExpiry, [], "Depot Damage Expiry Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Depot Damage Expiry.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }


        \DB::beginTransaction();
        try {
            $depotdamageexpiry = DepotDamageExpiry::where('uuid', $uuid)->first();

            //Delete old record
            DepotDamageExpiryDetail::where('depotdamageexpiry_id', $depotdamageexpiry->id)->delete();

            $depotdamageexpiry->depot_id            = (!empty($request->depot_id)) ? $request->depot_id : null;
            $depotdamageexpiry->reference_code            = (!empty($request->reference_code)) ? $request->reference_code : null;
            $depotdamageexpiry->date       = date('Y-m-d', strtotime($request->date));
            $depotdamageexpiry->save();

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    $depotdamageexpiryDetail = new DepotDamageExpiryDetail;
                    $depotdamageexpiryDetail->depotdamageexpiry_id      = $depotdamageexpiry->id;
                    $depotdamageexpiryDetail->item_id       = $item['item_id'];
                    $depotdamageexpiryDetail->item_uom_id   = $item['item_uom_id'];
                    $depotdamageexpiryDetail->qty   = $item['qty'];
                    $depotdamageexpiryDetail->reason_id       = $item['reason_id'];
                    $depotdamageexpiryDetail->save();
                }
            }

            \DB::commit();

            $depotdamageexpiry->getSaveData();

            return prepareResult(true, $depotdamageexpiry, [], "Depot Damage Expiry updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating Depot Damage Expiry.", $this->unauthorized);
        }

        $DepotDamageExpiry = DepotDamageExpiry::where('uuid', $uuid)
            ->first();

        if (is_object($DepotDamageExpiry)) {
            $DepotDamageExpiryId = $DepotDamageExpiry->id;
            $DepotDamageExpiry->delete();
            if ($DepotDamageExpiry) {
                DepotDamageExpiryDetail::where('depotdamageexpiry_id', $DepotDamageExpiryId)->delete();
            }
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Depot damage expiry", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->depotdamageexpiry_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $DepotDamageExpiry = DepotDamageExpiry::where('uuid', $uuid)
                    ->first();
                $DepotDamageExpiryId = $DepotDamageExpiry->id;
                $DepotDamageExpiry->delete();
                if ($DepotDamageExpiry) {
                    DepotDamageExpiryDetail::where('depotdamageexpiry_id', $DepotDamageExpiryId)->delete();
                }
            }
            $DepotDamageExpiry = $this->index();
            return prepareResult(true, $DepotDamageExpiry, [], "Depot damage expiry deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'depot_id' => 'required|integer|exists:depots,id',
                'reference_code' => 'required',
                'date' => 'required|date'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'depotdamageexpiry_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
