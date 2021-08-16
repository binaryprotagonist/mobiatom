<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DistributionModelStock;
use App\Model\DistributionModelStockDetails;
use Illuminate\Http\Request;

class DistributionModelStockController extends Controller
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

        $distribution_model_stock = DistributionModelStock::select('id', 'uuid', 'organisation_id', 'customer_id', 'distribution_id')
            ->with(
                'distributionModelStockDetails',
                'distributionModelStockDetails.item:id,item_name,item_code',
                'distributionModelStockDetails.itemUom:id,name,code',
                'distribution'
            )
            ->orderBy('id', 'desc')
            ->whereHas('distributionModelStockDetails', function ($q) {
                $q->where('is_deleted', 0);
            })
            ->get();

        $distribution_model_stock_array = array();
        if (is_object($distribution_model_stock)) {
            foreach ($distribution_model_stock as $key => $distribution_model_stock1) {
                $distribution_model_stock_array[] = $distribution_model_stock[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distribution_model_stock_array[$offset])) {
                    $data_array[] = $distribution_model_stock_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distribution_model_stock_array) / $limit);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $distribution_model_stock_array;
        }

        return prepareResult(true, $data_array, [], "Distribution model stock listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution model stock", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $dms = new DistributionModelStock;
            $dms->customer_id = $request->customer_id;
            $dms->distribution_id = $request->distribution_id;
            $dms->save();

            foreach ($request->items as $item) {
                // DistributionModelStockDetails::where('distribution_model_stock_id', $dms->id)->delete();
                $dmsd = new DistributionModelStockDetails;
                $dmsd->distribution_model_stock_id = $dms->id;
                $dmsd->distribution_id = $dms->distribution_id;
                $dmsd->item_id = $item['item_id'];
                $dmsd->item_uom_id = $item['item_uom_id'];
                $dmsd->capacity = $item['capacity'];
                $dmsd->total_number_of_facing = $item['total_number_of_facing'];
                $dmsd->save();
            }

            \DB::commit();

            $dms->distributionModelStockDetails;

            return prepareResult(true, $dms, [], "Distribution model stock added successfully", $this->created);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating distribution model stock", $this->unauthorized);
        }

        $dms = DistributionModelStock::select('id', 'uuid', 'organisation_id', 'customer_id', 'distribution_id', 'total_number_of_facing')
            ->with('distributionModelStock', 'distributionModelStock.item:id,item_name,item_code', 'distributionModelStock.itemUom:id,name,code', 'distribution')
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($dms)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $dms, [], "Distribution model stock Edit", $this->success);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function modelStockEdit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating distribution model stock", $this->unauthorized);
        }

        $dms = DistributionModelStockDetails::with(
            'distributionModelStock:id,customer_id,distribution_id',
            'item:id,item_name,item_code',
            'itemUom:id,name,code',
            'distribution:id,name'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($dms)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $dms, [], "Distribution model stock Edit", $this->success);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution model stock", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $dms = DistributionModelStock::where('uuid', $uuid)->first();
            DistributionModelStockDetails::where('distribution_model_stock_id', $dms->id)->delete();

            $dms->customer_id = $request->customer_id;
            $dms->distribution_id = $request->distribution_id;
            $dms->save();

            foreach ($request->items as $item) {
                $dmsd = new DistributionModelStockDetails;
                $dmsd->distribution_model_stock_id = $dms->id;
                $dmsd->distribution_id = $dms->distribution_id;
                $dmsd->item_id = $item['item_id'];
                $dmsd->item_uom_id = $item['item_uom_id'];
                $dmsd->capacity = $item['capacity'];
                $dmsd->total_number_of_facing = $item['total_number_of_facing'];
                $dmsd->save();
            }

            \DB::commit();
            $dms->distributionModelStockDetails;
            return prepareResult(true, $dms, [], "Distribution model stock updated successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function modelStockUpdate(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "update");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution model stock", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $dmsd = DistributionModelStockDetails::where('uuid', $uuid)->first();
            $dmsd->item_uom_id = $request->item_uom_id;
            $dmsd->capacity = $request->capacity;
            $dmsd->total_number_of_facing = $request->total_number_of_facing;
            $dmsd->save();

            \DB::commit();

            return prepareResult(true, $dmsd, [], "Distribution model stock updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating distribution model stock.", $this->unauthorized);
        }

        $dms = DistributionModelStock::where('uuid', $uuid)
            ->first();

        if (is_object($dms)) {
            $dms->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function modelStockDestroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating distribution model stock.", $this->unauthorized);
        }

        $dms = DistributionModelStockDetails::where('uuid', $uuid)
            ->first();

        if (is_object($dms)) {
            $dms->is_deleted = 1;
            $dms->save();
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
                'customer_id' => 'required|integer|exists:users,id',
                'distribution_id' => 'required|integer|exists:distributions,id'
                // 'item_id' => 'required|integer|exists:items,id',
                // 'item_uom_id' => 'required|integer|exists:item_uoms,id',
                // 'name' => 'required',
                // 'capacity' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function indexByCustomer($customer_id, $distribution_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $distribution_model_stock = DistributionModelStock::where('customer_id', $customer_id)
            ->where('distribution_id', $distribution_id)
            ->with(
                'distributionModelStockDetails',
                'distributionModelStockDetails.item:id,item_name,item_code',
                'distributionModelStockDetails.itemUom:id,name,code',
                'distribution'
            )
            ->orderBy('id', 'desc')
            ->get();

        $distribution_model_stock_array = array();
        if (is_object($distribution_model_stock)) {
            foreach ($distribution_model_stock as $key => $distribution_model_stock1) {
                $distribution_model_stock_array[] = $distribution_model_stock[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distribution_model_stock_array[$offset])) {
                    $data_array[] = $distribution_model_stock_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distribution_model_stock_array) / $limit);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $distribution_model_stock_array;
        }

        return prepareResult(true, $data_array, [], "Distribution model stock by customer listing", $this->success, $pagination);
    }
}
