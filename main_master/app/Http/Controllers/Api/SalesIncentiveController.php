<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\RouteTarget;
use App\Model\RouteTargetDetail;
use App\Model\Item;
use App\Model\Route;
use App\Model\OrderType;
use App\Model\SalesIncentive;
use DB;

class SalesIncentiveController extends Controller
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

		$routetargets = SalesIncentive::select('id', 'uuid', 'organisation_id', 'user_id', 'incentive_value', 'startdate', 'enddate')
			->with(
				'SalesIncentive.users:id,name'
				)
			//->where('order_date', date('Y-m-d'))
			->orderBy('id', 'desc')
			->get();

		

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

		return prepareResult(true, $data_array, [], "Sales Incentive listing", $this->success, $pagination);

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
			$salesincentive = new SalesIncentive;
			$salesincentive->user_id = (!empty($request->user_id)) ? $request->user_id : null;
			$salesincentive->incentive_value = (!empty($request->incentive_value)) ? $request->incentive_value : null;
			$salesincentive->startdate = date('Y-m-d', strtotime($request->startdate));
			$salesincentive->enddate = date('Y-m-d', strtotime($request->enddate));
			$salesincentive->save();

			

			\DB::commit();

			return prepareResult(true, $salesincentive, [], "Route target successfully", $this->created);
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

		$routestargets = SalesIncentive::select('id', 'uuid', 'organisation_id', 'user_id', 'incentive_value', 'startdate', 'enddate', 'Applyon', 'Targetvalue', 'status')
			->with(
				'SalesIncentive.users:id,name'
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
			$salesincentive = SalesIncentive::where('uuid', $uuid)->first();

			//RouteTargetDetail::where('sales_target_id', $salestarget->id)->delete();
			
			$salesincentive->user_id = (!empty($request->user_id)) ? $request->user_id : null;
			$salesincentive->incentive_value = (!empty($request->incentive_value)) ? $request->incentive_value : null;
			$salesincentive->startdate = date('Y-m-d', strtotime($request->startdate));
			$salesincentive->enddate = date('Y-m-d', strtotime($request->enddate));
			$salesincentive->save();

		

			\DB::commit();
			
			//$salestarget->getSaveData();

			return prepareResult(true, $salesincentive, [], "Sales target updated successfully", $this->created);
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

	/**
	 * Get price specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $item_id, $item_uom_id, $item_qty
	 * @return \Illuminate\Http\Response
	 */
	public function SalesIncentive(Request $request)
	{

		if (!$this->isAuthorized) {
			return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
		}

		//Check for Eligable
		$invoiceamount = DB::table('route_targets')
					->join('salesman_infos', 'salesman_infos.route_id', '=', 'route_targets.route_id', 'left')
					->join('sales_item_target_details', 'sales_item_target_details.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('items', 'items.id', '=', 'sales_item_target_details.item_id', 'left')
					->join('trips', 'trips.salesmancode', '=', 'sales_targets.TargetOwnerId', 'left')
					->join('invoices', 'invoices.salesman_id', '=', 'salesman_infos.salesman_id', 'left')
					->select('sales_targets.TargetOwnerId', 'sales_target_details.id', 'invoices.total_qty', 'invoices.invoice_date')
					->whereBetween('invoices.invoice_date', ['sales_targets.StartDate', 'sales_targets.EndDate'])
					->where('sales_target_details.sales_target_id', $salestarget->id)bdg
					->get();
		

				$targetheader = DB::table('sales_target_details')
					->join('sales_targets', 'sales_targets.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('sales_item_target_details', 'sales_item_target_details.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('items', 'items.id', '=', 'sales_item_target_details.item_id', 'left')
					->join('trips', 'trips.salesmancode', '=', 'sales_targets.TargetOwnerId', 'left')
					->join('invoices', 'invoices.trip_id', '=', 'trips.id', 'left')
					->select('sales_targets.TargetOwnerId', 'sales_target_details.id', 'sales_target_details.fixed_qty', 'invoices.total_qty')
					->whereBetween('trips.trip_start_date', ['sales_targets.StartDate', 'sales_targets.EndDate'])
					->where('sales_target_details.sales_target_id', $salestarget->id)
					->get();

				$invoicedetial = DB::table('sales_target_details')
					->join('sales_targets', 'sales_targets.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('sales_item_target_details', 'sales_item_target_details.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('items', 'items.id', '=', 'sales_item_target_details.item_id', 'left')
					->join('trips', 'trips.salesmancode', '=', 'sales_targets.TargetOwnerId', 'left')
					->join('invoices', 'invoices.trip_id', '=', 'trips.id', 'left')
					->select('sales_targets.TargetOwnerId', 'sales_target_details.id', 'invoices.total_qty', 'invoices.invoice_date')
					->whereBetween('trips.trip_start_date', ['sales_targets.StartDate', 'sales_targets.EndDate'])
					->where('sales_target_details.sales_target_id', $salestarget->id)
					->get();

				$data = array('Targetheader' => $targetheader, 'Invoicedetail' => $invoicedetial);

				if (!is_object($data)) {
					return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
				}

				return prepareResult(true, $data, [], "Sales Target Achived", $this->success);
			

		if (!is_object($salestarget)) {
			return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
		}

		return prepareResult(true, $salestarget, [], "Sales achive", $this->success);
	}
}
