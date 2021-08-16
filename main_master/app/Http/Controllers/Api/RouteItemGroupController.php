<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\RouteItemGrouping;
use App\Model\RouteItemGroupingDetail;
use Illuminate\Http\Request;

class RouteItemGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $route_item_grouping_query = RouteItemGrouping::select('id', 'uuid', 'organisation_id', 'merchandiser_id', 'route_id', 'name', 'code')
            ->with(
                'route:id,area_id,route_code,route_name,status',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'routeItemGroupingDetails:id,route_item_grouping_id,item_id',
                'routeItemGroupingDetails.item:id,item_major_category_id,item_group_id,brand_id,item_code,item_name,item_description,item_barcode,item_weight,item_shelf_life,lower_unit_item_upc,lower_unit_uom_id,lower_unit_item_price,is_tax_apply,item_vat_percentage,current_stage,current_stage_comment,status'
            );

            if ($request->code) {
                $route_item_grouping_query->where('code', $request->code);
            }

            if ($request->name) {
                $route_item_grouping_query->where('name', $request->name);
            }

            $route_item_grouping = $route_item_grouping_query->orderBy('id', 'desc')
            ->get();

        $route_item_grouping_array = array();
        if (is_object($route_item_grouping)) {
            foreach ($route_item_grouping as $key => $route_item_grouping1) {
                $route_item_grouping_array[] = $route_item_grouping[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($route_item_grouping_array[$offset])) {
                    $data_array[] = $route_item_grouping_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($route_item_grouping_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($route_item_grouping_array);
        } else {
            $data_array = $route_item_grouping_array;
        }

        return prepareResult(true, $data_array, [], "Route Item Grouping listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Item Grouping listing", $this->success);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $route_item_grouping = new RouteItemGrouping;
            $route_item_grouping->name = $request->name;
            $route_item_grouping->code = nextComingNumber('App\Model\RouteItemGrouping', 'route_item_grouping', 'code', $request->code);
            // $route_item_grouping->code = $request->code;
            $route_item_grouping->merchandiser_id = $request->merchandiser_id;
            $route_item_grouping->route_id = $request->route_id;
            $route_item_grouping->save();


            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //save RouteItemGroupingDetail
                    $route_item_grouping_details = new RouteItemGroupingDetail;
                    $route_item_grouping_details->route_item_grouping_id = $route_item_grouping->id;
                    $route_item_grouping_details->item_id = $item['item_id'];
                    $route_item_grouping_details->save();
                }
            }


            \DB::commit();
            updateNextComingNumber('App\Model\RouteItemGrouping', 'route_item_grouping');

            $route_item_grouping->getSaveData();

            return prepareResult(true, $route_item_grouping, [], "Route Item Grouping added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating Item Grouping listing", $this->unprocessableEntity);
        }

        $route_item_grouping = RouteItemGrouping::where('uuid', $uuid)
            ->with(
                'route:id,area_id,route_code,route_name,status',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'routeItemGroupingDetails:id,route_item_grouping_id,item_id',
                'routeItemGroupingDetails.item:id,item_major_category_id,item_group_id,brand_id,item_code,item_name,item_description,item_barcode,item_weight,item_shelf_life,lower_unit_item_upc,lower_unit_uom_id,lower_unit_item_price,is_tax_apply,item_vat_percentage,current_stage,current_stage_comment,status'
            )
            ->first();

        if (!is_object($route_item_grouping)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $route_item_grouping, [], "Route Item Grouping Edit", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Item Grouping listing", $this->success);
        }

        \DB::beginTransaction();
        try {
            $route_item_grouping = RouteItemGrouping::where('uuid', $uuid)
                ->first();

            if (!is_object($route_item_grouping)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            RouteItemGroupingDetail::where('route_item_grouping_id', $route_item_grouping->id)
                ->delete();

            $route_item_grouping->name = $request->name;
            $route_item_grouping->code = $request->code;
            $route_item_grouping->merchandiser_id = $request->merchandiser_id;
            $route_item_grouping->route_id = $request->route_id;
            $route_item_grouping->save();

            if (is_array($request->items) && sizeof($request->items) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
            }

            if (is_array($request->items)) {
                foreach ($request->items as $item) {
                    //save RouteItemGroupingDetail
                    $route_item_grouping_details = new RouteItemGroupingDetail;
                    $route_item_grouping_details->route_item_grouping_id = $route_item_grouping->id;
                    $route_item_grouping_details->item_id = $item['item_id'];
                    $route_item_grouping_details->save();
                }
            }


            \DB::commit();

            $route_item_grouping->getSaveData();

            return prepareResult(true, $route_item_grouping, [], "Route Item Grouping added successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating Item Grouping", $this->unauthorized);
        }

        $route_item_grouping = RouteItemGrouping::where('uuid', $uuid)
            ->first();

        if (is_object($route_item_grouping)) {
            $route_item_grouping->delete();
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
                'code' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function routeGroupByMerchandiser(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$request->merchandiser_id && !$request->route_id) {
            return prepareResult(false, [], [], "Error while validating Item Grouping listing", $this->unprocessableEntity);
        }

        $route_item_groupingq = RouteItemGrouping::select('id', 'uuid', 'organisation_id', 'merchandiser_id', 'route_id', 'name', 'code')
            ->with(
                'route:id,area_id,route_code,route_name,status',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'routeItemGroupingDetails:id,route_item_grouping_id,item_id',
                'routeItemGroupingDetails.item:id,item_major_category_id,item_group_id,brand_id,item_code,item_name,item_description,item_barcode,item_weight,item_shelf_life,lower_unit_item_upc,lower_unit_uom_id,lower_unit_item_price,is_tax_apply,item_vat_percentage,current_stage,current_stage_comment,status'
            );
            if ($request->merchandiser_id) {
                $route_item_grouping = $route_item_groupingq->where('merchandiser_id', $request->merchandiser_id)
                ->orderBy('id', 'desc')
                ->get();
            }
            if ($request->route_id) {
                $route_item_grouping = $route_item_groupingq->where('route_id', $request->route_id)
                ->orderBy('id', 'desc')
                ->get();
            }

        $route_item_grouping_array = array();
        if (is_object($route_item_grouping)) {
            foreach ($route_item_grouping as $key => $route_item_grouping1) {
                $route_item_grouping_array[] = $route_item_grouping[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($route_item_grouping_array[$offset])) {
                    $data_array[] = $route_item_grouping_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($route_item_grouping_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($route_item_grouping_array);
        } else {
            $data_array = $route_item_grouping_array;
        }

        return prepareResult(true, $data_array, [], "Route Item Grouping listing", $this->success, $pagination);
    }
}
