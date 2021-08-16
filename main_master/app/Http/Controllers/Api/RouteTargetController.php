<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\RouteTarget;
use App\Model\RouteTargetDetail;
use App\Model\Item;
use App\Model\Route;
use App\Model\OrderType;
use DB;

class RouteTargetController extends Controller
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

		$routetargets = RouteTarget::select('id', 'uuid', 'organisation_id', 'div_id', 'route_id', 'StartDate', 'EndDate', 'Applyon', 'TargetType', 'status')
			->with(
				'RouteTargetDetail:id,uuid,sales_target_id,category_id,fixed_value,ApplyOn,status',
				'RouteTargetDetail.item_major_categories:id,name',
				'RouteTargetDetail.routes:id,route_code,route_name'
			)
			//->where('order_date', date('Y-m-d'))
			->orderBy('id', 'desc')
			->get();

		$routetargets_array = array();
		if (is_object($routetargets)) {
			foreach ($routetargets as $key => $routetargets1) {
				$routetargets_array[] = $routetargets[$key];
			}
		}

		$data_array = array();
		$page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
		$limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
		$pagination = array();
		if ($page != '' && $limit != '') {
			$offset = ($page - 1) * $limit;
			for ($i = 0; $i < $limit; $i++) {
				if (isset($routetargets_array[$offset])) {
					$data_array[] = $routetargets_array[$offset];
				}
				$offset++;
			}

			$pagination['total_pages'] = ceil(count($routetargets_array) / $limit);
			$pagination['current_page'] = (int)$page;
			$pagination['total_records'] = count($routetargets_array);
		} else {
			$data_array = $routetargets_array;
		}

		return prepareResult(true, $data_array, [], "Route target listing", $this->success, $pagination);

		// return prepareResult(true, $salestargets, [], "Sales target listing", $this->success);
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit not", $this->unprocessableEntity);
		}

		if (is_array($request->items) && sizeof($request->items) < 1) {
			return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
		}

		\DB::beginTransaction();
		try {
			$routetarget = new RouteTarget;
			$routetarget->div_id = (!empty($request->div_id)) ? $request->div_id : null;
			$routetarget->route_id = (!empty($request->route_id)) ? $request->route_id : null;
			$routetarget->StartDate = date('Y-m-d', strtotime($request->StartDate));
			$routetarget->EndDate = date('Y-m-d', strtotime($request->EndDate));
			$routetarget->Applyon = $request->ApplyOn;
			$routetarget->Targetvalue = $request->Targetvalue;
			$routetarget->save();

			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					$routestargetDetail = new RouteTargetDetail;
					$routestargetDetail->routes_target_id = $routetarget->id;
					$routestargetDetail->category_id = $item['categ_id'];
					$routestargetDetail->fixed_value = $item['target_qty'];
					$routestargetDetail->ApplyOn = $request->ApplyOn;
					$routestargetDetail->save();
					//print_r($item['saleitem']);
				}
			}

			\DB::commit();

			return prepareResult(true, $routetarget, [], "Route target successfully", $this->created);
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
			return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
		}

		$routestargets = RouteTarget::select('id', 'uuid', 'organisation_id', 'div_id', 'route_id', 'StartDate', 'EndDate', 'Applyon', 'Targetvalue', 'status')
			->with(
				'RouteTargetDetail:id,uuid,routes_target_id,category_id,fixed_value,ApplyOn,status',
				'RouteTargetDetail.item_major_categories:id,name',
				'RouteTargetDetail.routes:id,route_code,route_name'
			)
			->where('uuid', $uuid)
			->first();

		if (!is_object($routestargets)) {
			return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
		}

		return prepareResult(true, $routestargets, [], "Route Target Edit", $this->success);
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating sales target.", $this->unprocessableEntity);
		}

		if (is_array($request->items) && sizeof($request->items) < 1) {
			return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
		}

		\DB::beginTransaction();
		try {
			$routetarget = RouteTarget::where('uuid', $uuid)->first();

			//RouteTargetDetail::where('sales_target_id', $salestarget->id)->delete();

			$routetarget->div_id = (!empty($request->div_id)) ? $request->div_id : null;
			$routetarget->route_id = (!empty($request->route_id)) ? $request->route_id : null;
			$routetarget->StartDate = date('Y-m-d', strtotime($request->StartDate));
			$routetarget->EndDate = date('Y-m-d', strtotime($request->EndDate));
			$routetarget->Applyon = $request->ApplyOn;
			$routetarget->Targetvalue = $request->Targetvalue;
			$routetarget->save();

			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					$rourtetargetDetail = new RouteTargetDetail;
					$rourtetargetDetail->routes_target_id = $routetarget->id;
					$rourtetargetDetail->category_id = $item['category_id'];
					$rourtetargetDetail->fixed_value = $item['fixed_value'];
					$rourtetargetDetail->ApplyOn = $request->ApplyOn;
					$rourtetargetDetail->save();

					//print_r($item['saleitem']);
				}
			}

			\DB::commit();



			return prepareResult(true, $routetarget, [], "route target updated successfully", $this->created);
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
			return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
		}
		$salestarget = RouteTarget::where('uuid', $uuid)->first();
		if (is_object($salestarget)) {
			$salestargetId = $salestarget->id;
			$salestarget->delete();
			if ($salestarget) {
				RouteTarget::where('sales_target_id', $salestargetId)->delete();
				RouteTargetDetail::where('sales_target_id', $salestargetId)->delete();
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
		}
		$action = $request->action;
		$uuids = $request->sales_target_ids;
		if (empty($action)) {
			return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
		}
		if ($action == 'active' || $action == 'inactive') {
			foreach ($uuids as $uuid) {
				SalesTarget::where('uuid', $uuid)->update([
					'status' => ($action == 'active') ? 1 : 0
				]);
			}
			$salestarget = $this->index();
			return prepareResult(true, $salestarget, [], "Sales target status updated", $this->success);
		} else if ($action == 'delete') {
			foreach ($uuids as $uuid) {
				$salestarget = SalesTarget::where('uuid', $uuid)
					->first();
				$salestargetId = $salestarget->id;
				$salestarget->delete();
				if ($salestarget) {
					SalesTargetDetail::where('sales_target_id', $salestargetId)->delete();
					SalesItemTargetDetail::where('sales_target_id', $salestargetId)->delete();
				}
			}
			$salestarget = $this->index();
			return prepareResult(true, $salestarget, [], "Sales target deleted success", $this->success);
		}
	}

	private function validations($input, $type)
	{
		$errors = [];
		$error = false;
		if ($type == "add") {
			$validator = \Validator::make($input, [
				'div_id' => 'required',
				'route_id' => 'required',
				'Targetvalue' => 'required',
				'StartDate' => 'required|date',
				'EndDate' => 'required|date'
			]);
		}
		if ($type == 'bulk-action') {
			$validator = \Validator::make($input, [
				'action' => 'required',
				'sales_target_ids' => 'required'
			]);
		}
		if ($validator->fails()) {
			$error = true;
			$errors = $validator->errors();
		}
		return ["error" => $error, "errors" => $errors];
	}
}
