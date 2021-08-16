<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\SalesTarget;
use App\Model\SalesTargetDetail;
use App\Model\SalesItemTargetDetail;
use App\Model\Item;
use App\Model\Route;
use App\Model\OrderType;
use DB;

class SalesTargetController extends Controller
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

		$salestargets = SalesTarget::select('id', 'uuid', 'organisation_id', 'TargetEntity', 'TargetName', 'TargetOwnerId', 'StartDate', 'EndDate', 'Applyon', 'TargetType', 'TargetVariance', 'CommissionType', 'status')
			->with(
				'SalesItemTargetDetail:id,uuid,sales_target_id,item_id,item_uom_id,ApplyOn,status',
				'SalesItemTargetDetail.salesTargetDetails:id,uuid,sales_target_id,item_table_id,Applyon,fixed_qty,fixed_value,from_qty,to_qty,from_value,to_value,commission,status',
				'SalesItemTargetDetail.item:id,item_name,item_code',
				'SalesItemTargetDetail.itemUom:id,name,code'
			)
			//->where('order_date', date('Y-m-d'))
			->orderBy('id', 'desc')
			->get();

		$salestargets_array = array();
		if (is_object($salestargets)) {
			foreach ($salestargets as $key => $salestargets1) {
				$salestargets_array[] = $salestargets[$key];
			}
		}

		$data_array = array();
		$page = (isset($request->page)) ? $request->page : '';
		$limit = (isset($request->page_size)) ? $request->page_size : '';
		$pagination = array();
		if ($page != '' && $limit != '') {
			$offset = ($page - 1) * $limit;
			for ($i = 0; $i < $limit; $i++) {
				if (isset($salestargets_array[$offset])) {
					$data_array[] = $salestargets_array[$offset];
				}
				$offset++;
			}

			$pagination['total_pages'] = ceil(count($salestargets_array) / $limit);
			$pagination['current_page'] = (int)$page;
			$pagination['total_records'] = count($salestargets_array);
		} else {
			$data_array = $salestargets_array;
		}

		return prepareResult(true, $data_array, [], "Sales target listing", $this->success, $pagination);

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
			$salestarget = new SalesTarget;
			$salestarget->TargetEntity = (!empty($request->TargetEntity)) ? $request->TargetEntity : null;
			$salestarget->TargetName = (!empty($request->TargetName)) ? $request->TargetName : null;
			$salestarget->TargetOwnerId = (!empty($request->TargetOwnerId)) ? $request->TargetOwnerId : null;
			$salestarget->StartDate = date('Y-m-d', strtotime($request->StartDate));
			$salestarget->EndDate = date('Y-m-d', strtotime($request->EndDate));
			$salestarget->Applyon = $request->ApplyOn;
			$salestarget->TargetType = $request->TargetType;
			$salestarget->TargetVariance = $request->TargetVariance;
			$salestarget->CommissionType = $request->CommissionType;
			$salestarget->save();

			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					$salesitemtargetDetail = new SalesItemTargetDetail;
					$salesitemtargetDetail->sales_target_id = $salestarget->id;
					$salesitemtargetDetail->item_id = $item['item_id'];
					$salesitemtargetDetail->item_uom_id = $item['item_uom_id'];
					$salesitemtargetDetail->ApplyOn = $request->ApplyOn;
					$salesitemtargetDetail->save();
					if ($item['saleitem']) {

						foreach ($item['saleitem'] as $saleitem) {
							$salestargetDetail = new SalesTargetDetail;
							$salestargetDetail->sales_target_id = $salestarget->id;
							$salestargetDetail->item_table_id = $salesitemtargetDetail->id;
							$salestargetDetail->ApplyOn = $request->ApplyOn;
							$salestargetDetail->fixed_qty = $saleitem['fixed_qty'];
							$salestargetDetail->fixed_value = $saleitem['fixed_value'];
							$salestargetDetail->from_qty = $saleitem['from_qty'];
							$salestargetDetail->to_qty = $saleitem['to_qty'];
							$salestargetDetail->from_value = $saleitem['from_value'];
							$salestargetDetail->to_value = $saleitem['to_value'];
							$salestargetDetail->commission = $saleitem['commission'];
							$salestargetDetail->save();
						}
					}
					//print_r($item['saleitem']);
				}
			}

			\DB::commit();

			$salestarget->getSaveData();

			return prepareResult(true, $salestarget, [], "Sales target successfully", $this->created);
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

		$salestarget = SalesTarget::select('id', 'uuid', 'organisation_id', 'TargetEntity', 'TargetName', 'TargetOwnerId', 'StartDate', 'EndDate', 'Applyon', 'TargetType', 'TargetVariance', 'CommissionType', 'status')
			->with(
				'SalesItemTargetDetail:id,uuid,sales_target_id,item_id,item_uom_id,ApplyOn,status',
				'SalesItemTargetDetail.salesTargetDetails:id,uuid,sales_target_id,item_table_id,Applyon,fixed_qty,fixed_value,from_qty,to_qty,from_value,to_value,commission,status',
				'SalesItemTargetDetail.item:id,item_name',
				'SalesItemTargetDetail.itemUom:id,name,code'
			)
			->where('uuid', $uuid)
			->first();

		if (!is_object($salestarget)) {
			return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
		}

		return prepareResult(true, $salestarget, [], "Sales Target Edit", $this->success);
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
			$salestarget = SalesTarget::where('uuid', $uuid)->first();

			SalesItemTargetDetail::where('sales_target_id', $salestarget->id)->delete();
			//Delete old record
			SalesTargetDetail::where('sales_target_id', $salestarget->id)->delete();

			$salestarget->TargetEntity = (!empty($request->TargetEntity)) ? $request->TargetEntity : null;
			$salestarget->TargetName = (!empty($request->TargetName)) ? $request->TargetName : null;
			$salestarget->TargetOwnerId = (!empty($request->TargetOwnerId)) ? $request->TargetOwnerId : null;
			$salestarget->StartDate = date('Y-m-d', strtotime($request->StartDate));
			$salestarget->EndDate = date('Y-m-d', strtotime($request->EndDate));
			$salestarget->Applyon = $request->ApplyOn;
			$salestarget->TargetType = $request->TargetType;
			$salestarget->TargetVariance = $request->TargetVariance;
			$salestarget->CommissionType = $request->CommissionType;
			$salestarget->save();

			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					$salesitemtargetDetail = new SalesItemTargetDetail;
					$salesitemtargetDetail->sales_target_id = $salestarget->id;
					$salesitemtargetDetail->item_id = $item['item_id'];
					$salesitemtargetDetail->item_uom_id = $item['item_uom_id'];
					$salesitemtargetDetail->ApplyOn = $request->ApplyOn;
					$salesitemtargetDetail->save();
					if ($item['saleitem']) {
						foreach ($item['saleitem'] as $saleitem) {
							$salestargetDetail = new SalesTargetDetail;
							$salestargetDetail->sales_target_id = $salestarget->id;
							$salestargetDetail->item_table_id = $salesitemtargetDetail->id;
							$salestargetDetail->ApplyOn = $request->ApplyOn;
							$salestargetDetail->fixed_qty = $saleitem['fixed_qty'];
							$salestargetDetail->fixed_value = $saleitem['fixed_value'];
							$salestargetDetail->from_qty = $saleitem['from_qty'];
							$salestargetDetail->to_qty = $saleitem['to_qty'];
							$salestargetDetail->from_value = $saleitem['from_value'];
							$salestargetDetail->to_value = $saleitem['to_value'];
							$salestargetDetail->commission = $saleitem['commission'];
							$salestargetDetail->save();
						}
					}
					//print_r($item['saleitem']);
				}
			}

			\DB::commit();

			$salestarget->getSaveData();

			return prepareResult(true, $salestarget, [], "Sales target updated successfully", $this->created);
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
		$salestarget = SalesTarget::where('uuid', $uuid)->first();
		if (is_object($salestarget)) {
			$salestargetId = $salestarget->id;
			$salestarget->delete();
			if ($salestarget) {
				SalesTargetDetail::where('sales_target_id', $salestargetId)->delete();
				SalesItemTargetDetail::where('sales_target_id', $salestargetId)->delete();
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
				'TargetEntity' => 'required',
				'TargetOwnerId' => 'required',
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
	public function salesachived($uuid)
	{

		if (!$this->isAuthorized) {
			return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
		}

		if (!$uuid) {
			return prepareResult(false, [], [], "Error while validating sales target.", $this->unauthorized);
		}

		$salestarget = SalesTarget::select('id', 'uuid', 'TargetEntity', 'TargetName', 'TargetOwnerId', 'Applyon', 'TargetType', 'TargetVariance', 'CommissionType')
			->where('uuid', $uuid)
			->first();

		if ($salestarget->Applyon == 2) {
			if ($salestarget->TargetEntity == '2' & $salestarget->TargetType == '1') {
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
			} else if ($salestarget->TargetEntity == '2' & $salestarget->TargetType == '2') {

				if ($salestarget->TargetType == '1') {
					$attendanceColumnSelect = DB::raw('sales_target_details.fixed_qty', 'invoices.total_qty');
				} else if ($salestarget->TargetType == '1') {
					$attendanceColumnSelect = DB::raw('sales_target_details.fixed_value', 'invoices.total_gross');
				}

				$targetheader = DB::table('sales_target_details')
					->join('sales_targets', 'sales_targets.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('sales_item_target_details', 'sales_item_target_details.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('items', 'items.id', '=', 'sales_item_target_details.item_id', 'left')
					->join('trips', 'trips.salesmancode', '=', 'sales_targets.TargetOwnerId', 'left')
					->join('invoices', 'invoices.trip_id', '=', 'trips.id', 'left')
					->select('sales_target_details.id', 'sales_target_details.fixed_value', 'invoices.total_gross')
					->whereBetween('trips.trip_start_date', ['sales_targets.StartDate', 'sales_targets.EndDate'])
					->where('sales_target_details.sales_target_id', $salestarget->id)
					->get();

				$invoicedetial = DB::table('sales_target_details')
					->join('sales_targets', 'sales_targets.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('sales_item_target_details', 'sales_item_target_details.id', '=', 'sales_target_details.sales_target_id', 'left')
					->join('items', 'items.id', '=', 'sales_item_target_details.item_id', 'left')
					->join('trips', 'trips.salesmancode', '=', 'sales_targets.TargetOwnerId', 'left')
					->join('invoices', 'invoices.trip_id', '=', 'trips.id', 'left')
					->select('sales_target_details.id', 'invoices.invoice_date', 'invoices.total_gross')
					->whereBetween('trips.trip_start_date', ['sales_targets.StartDate', 'sales_targets.EndDate'])
					->where('sales_target_details.sales_target_id', $salestarget->id)
					->get();

				$data = array('Targetheader' => $targetheader, 'Invoicedetail' => $invoicedetial);

				if (!is_object($salestarget)) {
					return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
				}

				return prepareResult(true, $data, [], "Sales Target Achived", $this->success);
			}
		}

		if ($salestarget->Applyon == 1) {
			$salestargetitem = DB::table('sales_item_target_details')->join('items', 'items.id', '=', 'sales_item_target_details.item_id')->select('sales_item_target_details.id', 'sales_item_target_details.item_id', 'items.item_name')
				->where('sales_target_id', $salestarget->id)
				->get();

			$salestarget->items = $salestargetitem;
			$toalarray = array();
			foreach ($salestargetitem as $asd) {
				if ($salestarget->Applyon == 1 && $salestarget->TargetType == 1 && $salestarget->TargetVariance == 1 && $salestarget->CommissionType == 1) {
					$salestargetdetails = DB::table('sales_target_details')->select(DB::raw('SUM(fixed_qty) as totalprice'))->where('sales_target_id', $salestarget->id)->where('item_table_id', $asd->id)->get();

					$asd->totalprice = $salestargetdetails[0]->totalprice;
					$toalarray[] = $asd->totalprice;
				}

				if ($salestarget->Applyon == 1 && $salestarget->TargetType == 2 && $salestarget->TargetVariance == 1 && $salestarget->CommissionType == 1) {
					$salestargetdetails = DB::table('sales_target_details')->select(DB::raw('SUM(fixed_value) as totalprice'))
						->where('sales_target_id', $salestarget->id)
						->where('item_table_id', $asd->id)
						->get();

					$asd->totalprice = $salestargetdetails[0]->totalprice;
					$toalarray[] = $asd->totalprice;
				}

				if ($salestarget->Applyon == 1 && $salestarget->TargetType == 2 && $salestarget->TargetVariance == 2 && $salestarget->CommissionType == 1) {
					$salestargetdetails = DB::table('sales_target_details')->select(DB::raw('SUM(to_value) as totalprice'))->where('sales_target_id', $salestarget->id)
						->where('item_table_id', $asd->id)
						->get();

					$asd->totalprice = $salestargetdetails[0]->totalprice;
					$toalarray[] = $asd->totalprice;
					//print_R($salestargetdetails);
				}
				if ($salestarget->Applyon == 1 && $salestarget->TargetType == 1 && $salestarget->TargetVariance == 2 && $salestarget->CommissionType == 1) {
					$salestargetdetails = DB::table('sales_target_details')->select(DB::raw('SUM(to_qty) as totalprice'))->where('sales_target_id', $salestarget->id)
						->where('item_table_id', $asd->id)
						->get();

					$asd->totalprice = $salestargetdetails[0]->totalprice;
					$toalarray[] = $asd->totalprice;
					//print_R($salestargetdetails);
				}
			}
			$salestarget->montlytarget = array_sum($toalarray);
			// print_R($toalarray);
		}



		//$salestarget = 
		/* 	foreach($salestarget as $k=>$st){
			
		}
		$finalresultarray = (object)array();
		//print_r($salestarget->uuid);
		 if(is_object($salestarget)) 
		 {
			 
			 $finalresultarray->TargetName = $salestarget->TargetName;
			 $finalresultarray->TargetOwnerId = $salestarget->TargetOwnerId;
			 $finalresultarray->TargetOwnerId1 = $salestarget->sales_item_target_detail;
			
			 
			 //echo $salestarget->TargetName;
		 }
		 print_r($finalresultarray); */

		if (!is_object($salestarget)) {
			return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
		}

		return prepareResult(true, $salestarget, [], "Sales achive", $this->success);
	}
}
