<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Goodreceiptnote;
use App\Model\Goodreceiptnotedetail;
use App\Model\WarehouseDetail;
use App\Model\WarehouseDetailLog;
use App\Model\Storagelocation;
use App\Model\StoragelocationDetail;

class GoodreceiptnoteController extends Controller
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

		$goodreceiptnote_query = Goodreceiptnote::with(
			'goodreceiptnotedetail',
			'sourceWarehouse',
			'destinationWarehouse',
			'goodreceiptnotedetail.item:id,item_name,item_code',
			'goodreceiptnotedetail.itemUom:id,name,code'
		);

		if ($request->date) {
			$goodreceiptnote_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
		}

		if ($request->code) {
			$goodreceiptnote_query->where('grn_number', 'like', '%' . $request->code . '%');
		}

		if ($request->sourceWarehouse) {
			$warehouseName = $request->sourceWarehouse;
			$goodreceiptnote_query->whereHas('sourceWarehouse', function ($q) use ($warehouseName) {
				$q->where('name', 'like', '%' . $warehouseName . '%');
			});
		}

		if ($request->destinationWarehouse) {
			$warehouseName = $request->destinationWarehouse;
			$goodreceiptnote_query->whereHas('destinationWarehouse', function ($q) use ($warehouseName) {
				$q->where('name', 'like', '%' . $warehouseName . '%');
			});
		}

		$goodreceiptnote = $goodreceiptnote_query->orderBy('id', 'desc')
			->get();

		$goodreceiptnote_array = array();
		if (is_object($goodreceiptnote)) {
			foreach ($goodreceiptnote as $key => $goodreceiptnote1) {
				$goodreceiptnote_array[] = $goodreceiptnote[$key];
			}
		}

		$data_array = array();
		$page = (isset($request->page)) ? $request->page : '';
		$limit = (isset($request->page_size)) ? $request->page_size : '';
		$pagination = array();
		if ($page != '' && $limit != '') {
			$offset = ($page - 1) * $limit;
			for ($i = 0; $i < $limit; $i++) {
				if (isset($goodreceiptnote_array[$offset])) {
					$data_array[] = $goodreceiptnote_array[$offset];
				}
				$offset++;
			}

			$pagination['total_pages'] = ceil(count($goodreceiptnote_array) / $limit);
			$pagination['current_page'] = (int)$page;
			$pagination['total_records'] = count($goodreceiptnote_array);
		} else {
			$data_array = $goodreceiptnote_array;
		}

		return prepareResult(true, $data_array, [], "Good receipt note listing", $this->success, $pagination);
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating good receipt note", $this->unprocessableEntity);
		}

		if (is_array($request->items) && sizeof($request->items) < 1) {
			return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
		}

		\DB::beginTransaction();
		try {

			// $status = (isset($request->status))?$request->status:0;
			// if ($isActivate = checkWorkFlowRule('Goodreceiptnote', 'create')) {
			//     $status = 0;
			//     $this->createWorkFlowObject($isActivate, 'Goodreceiptnote',$request);
			// }

			$goodreceiptnote = new Goodreceiptnote;
			$goodreceiptnote->source_warehouse         = (!empty($request->source_warehouse)) ? $request->source_warehouse : null;
			$goodreceiptnote->destination_warehouse            = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
			$goodreceiptnote->grn_number            = nextComingNumber('App\Model\Goodreceiptnote', 'goodreceiptnote', 'grn_number', $request->grn_number);
			$goodreceiptnote->grn_date       = (!empty($request->grn_date)) ? $request->grn_date : null;
			$goodreceiptnote->grn_remark        = (!empty($request->grn_remark)) ? $request->grn_remark : null;
			$goodreceiptnote->save();

			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					$goodreceiptnotedetail = new Goodreceiptnotedetail;
					$goodreceiptnotedetail->good_receipt_note_id      = $goodreceiptnote->id;
					$goodreceiptnotedetail->item_id       	= $item['item_id'];
					$goodreceiptnotedetail->item_uom_id   	= $item['item_uom_id'];
					$goodreceiptnotedetail->reason   		= $item['reason'];
					$goodreceiptnotedetail->qty   			= $item['qty'];
					$goodreceiptnotedetail->save();
				}
			}

			$this->approve($request, $goodreceiptnote->uuid);

			\DB::commit();

			updateNextComingNumber('App\Model\Goodreceiptnote', 'goodreceiptnote');

			$goodreceiptnote->getSaveData();

			return prepareResult(true, $goodreceiptnote, [], "Good receipt note added successfully", $this->created);
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
			return prepareResult(false, [], [], "Error while validating good receipt note.", $this->unauthorized);
		}

		$goodreceiptnote = Goodreceiptnote::with(
			'goodreceiptnotedetail',
			'sourceWarehouse:id,name',
			'destinationWarehouse:id,name',
			'goodreceiptnotedetail.item:id,item_name,item_code',
			'goodreceiptnotedetail.itemUom:id,name,code'
		)
			->where('uuid', $uuid)
			->first();

		if (!is_object($goodreceiptnote)) {
			return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
		}

		return prepareResult(true, $goodreceiptnote, [], "Good receipt note Edit", $this->success);
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating good receipt note.", $this->unprocessableEntity);
		}

		if (is_array($request->items) && sizeof($request->items) < 1) {
			return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
		}


		\DB::beginTransaction();
		try {
			$goodreceiptnote = Goodreceiptnote::where('uuid', $uuid)->first();
			$goodreceiptnote->source_warehouse         = (!empty($request->source_warehouse)) ? $request->source_warehouse : null;
			$goodreceiptnote->destination_warehouse            = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
			$goodreceiptnote->grn_number            = (!empty($request->grn_number)) ? $request->grn_number : null;
			$goodreceiptnote->grn_date       = (!empty($request->grn_date)) ? $request->grn_date : null;
			$goodreceiptnote->grn_remark        = (!empty($request->grn_remark)) ? $request->grn_remark : null;
			$goodreceiptnote->save();

			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					if ($item['id'] > 0) {
						$goodreceiptnotedetail = Goodreceiptnotedetail::find($item['id']);
						if ($goodreceiptnotedetail->qty != $item['qty']) {
							
							if ($goodreceiptnotedetail->qty > $item['qty']) {
								
								$qty = ($item['qty'] - $goodreceiptnotedetail->qty);
								$warehousedetail = WarehouseDetail::where('warehouse_id', $request->source_warehouse)
									->where('item_id', $item['item_id'])
									->where('item_uom_id', $item['item_uom_id'])
									->first();

								if ($warehousedetail) {
									$update_qty = ($warehousedetail->qty - $qty);
									$warehousedetail = WarehouseDetail::find($warehousedetail->id);
									$warehousedetail->qty = $update_qty;
									$warehousedetail->save();
								} else {
									$warehousedetail = new WarehouseDetail;
									$warehousedetail->warehouse_id         = $request->source_warehouse;
									$warehousedetail->item_id         = $item['item_id'];
									$warehousedetail->item_uom_id            = $item['item_uom_id'];
									$warehousedetail->qty            = (0 - $qty);
									$warehousedetail->batch       = '';
									$warehousedetail->save();
								}
								//add log
								$warehousedetail_log = new WarehouseDetailLog;
								$warehousedetail_log->warehouse_id = (!empty($request->source_warehouse)) ? $request->source_warehouse : null;
								$warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
								$warehousedetail_log->item_uom_id = $item['item_uom_id'];
								$warehousedetail_log->qty = $qty;
								$warehousedetail_log->action_type = 'Unload';
								$warehousedetail_log->save();
								//add log

								//update destination warehouse
								$warehousedetail_dest = WarehouseDetail::where('warehouse_id', $request->destination_warehouse)
									->where('item_id', $item['item_id'])
									->where('item_uom_id', $item['item_uom_id'])
									->first();
								if ($warehousedetail_dest) {
									$warehousedetail_dest->qty            = ($warehousedetail_dest->qty + $qty);
									$warehousedetail_dest->save();
								} else {
									$warehousedetail_dest = new WarehouseDetail;
									$warehousedetail_dest->warehouse_id         = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
									$warehousedetail_dest->item_id         = $item['item_id'];
									$warehousedetail_dest->item_uom_id            = $item['item_uom_id'];
									$warehousedetail_dest->qty            = $qty;
									$warehousedetail_dest->batch       = '';
									$warehousedetail_dest->save();
								}

								//add log
								$warehousedetail_log = new WarehouseDetailLog;
								$warehousedetail_log->warehouse_id = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
								$warehousedetail_log->warehouse_detail_id = $warehousedetail_dest->id;
								$warehousedetail_log->item_uom_id = $item['item_uom_id'];
								$warehousedetail_log->qty = $qty;
								$warehousedetail_log->action_type = 'Load';
								$warehousedetail_log->save();
								//add log
							} else {
								$qty = ($goodreceiptnotedetail->qty - $item['qty']);
								$warehousedetail = WarehouseDetail::where('warehouse_id', $request->source_warehouse)
									->where('item_id', $item['item_id'])
									->where('item_uom_id', $item['item_uom_id'])
									->first();
								if ($warehousedetail) {
									$update_qty = ($warehousedetail->qty + $qty);
									$warehousedetail_dest = WarehouseDetail::find($warehousedetail->id);
									$warehousedetail_dest->qty = $update_qty;
									$warehousedetail_dest->save();
								} else {
									$warehousedetail_dest = new WarehouseDetail;
									$warehousedetail_dest->warehouse_id         = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
									$warehousedetail_dest->item_id         = $item['item_id'];
									$warehousedetail_dest->item_uom_id            = $item['item_uom_id'];
									$warehousedetail_dest->qty            = $qty;
									$warehousedetail_dest->batch       = '';
									$warehousedetail_dest->save();
								}

								//add log
								$warehousedetail_log = new WarehouseDetailLog;
								$warehousedetail_log->warehouse_id = (!empty($request->source_warehouse)) ? $request->source_warehouse : null;
								$warehousedetail_log->warehouse_detail_id = $warehousedetail_dest->id;
								$warehousedetail_log->item_uom_id = $item['item_uom_id'];
								$warehousedetail_log->qty = $qty;
								$warehousedetail_log->action_type = 'Load';
								$warehousedetail_log->save();
								//add log

								//update destination warehouse
								$warehousedetail_dest = WarehouseDetail::where('warehouse_id', $request->destination_warehouse)
									->where('item_id', $item['item_id'])
									->where('item_uom_id', $item['item_uom_id'])
									->first();
								if ($warehousedetail_dest) {
									$warehousedetail_dest->qty    = ($warehousedetail_dest->qty - $qty);
									$warehousedetail_dest->save();
								} else {
									$warehousedetail_dest = new WarehouseDetail;
									$warehousedetail_dest->warehouse_id         = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
									$warehousedetail_dest->item_id         = $item['item_id'];
									$warehousedetail_dest->item_uom_id            = $item['item_uom_id'];
									$warehousedetail_dest->qty            = $qty;
									$warehousedetail_dest->batch       = '';
									$warehousedetail_dest->save();
								}

								//add log
								$warehousedetail_log = new WarehouseDetailLog;
								$warehousedetail_log->warehouse_id = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
								$warehousedetail_log->warehouse_detail_id = $warehousedetail_dest->id;
								$warehousedetail_log->item_uom_id = $item['item_uom_id'];
								$warehousedetail_log->qty = $qty;
								$warehousedetail_log->action_type = 'Unload';
								$warehousedetail_log->save();
								//add log
							}

							$goodreceiptnotedetail = Goodreceiptnotedetail::find($item['id']);
							$goodreceiptnotedetail->good_receipt_note_id      = $goodreceiptnote->id;
							$goodreceiptnotedetail->item_id       	= $item['item_id'];
							$goodreceiptnotedetail->item_uom_id   	= $item['item_uom_id'];
							$goodreceiptnotedetail->reason   		= $item['reason'];
							$goodreceiptnotedetail->qty				= $item['qty'];
							$goodreceiptnotedetail->created_at      = date('Y-m-d H:i:s');
							$goodreceiptnotedetail->updated_at      = date('Y-m-d H:i:s');
							$goodreceiptnotedetail->save();
						}
					} else {
						$goodreceiptnotedetail = new Goodreceiptnotedetail;
						$goodreceiptnotedetail->good_receipt_note_id      = $goodreceiptnote->id;
						$goodreceiptnotedetail->item_id       = $item['item_id'];
						$goodreceiptnotedetail->item_uom_id   = $item['item_uom_id'];
						$goodreceiptnotedetail->qty   = $item['qty'];
						$goodreceiptnotedetail->reason   		= $item['reason'];
						$goodreceiptnotedetail->created_at        = date('Y-m-d H:i:s');
						$goodreceiptnotedetail->updated_at        = date('Y-m-d H:i:s');
						$goodreceiptnotedetail->save();

						// update source warehouse
						$warehousedetail = WarehouseDetail::where('warehouse_id', $request->source_warehouse)
							->where('item_id', $item['item_id'])
							->where('item_uom_id', $item['item_uom_id'])
							->first();
						if ($warehousedetail) {
							$update_qty = ($warehousedetail->qty - $item['qty']);
							$warehousedetail_update = WarehouseDetail::find($warehousedetail->id);
							$warehousedetail_update->qty = $update_qty;
							$warehousedetail_update->save();
						} else {
							$warehousedetail = new WarehouseDetail;
							$warehousedetail->warehouse_id         = (!empty($request->source_warehouse)) ? $request->source_warehouse : null;
							$warehousedetail->item_id         = $item['item_id'];
							$warehousedetail->item_uom_id            = $item['item_uom_id'];
							$warehousedetail->qty            = (0 - $item['qty']);
							$warehousedetail->batch       = '';
							$warehousedetail->save();
						}
						//add log
						$warehousedetail_log = new WarehouseDetailLog;
						$warehousedetail_log->warehouse_id = (!empty($request->source_warehouse)) ? $request->source_warehouse : null;
						$warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
						$warehousedetail_log->item_uom_id = $item['item_uom_id'];
						$warehousedetail_log->qty = $item['qty'];
						$warehousedetail_log->action_type = 'Unload';
						$warehousedetail_log->created_at       = date('Y-m-d H:i:s');
						$warehousedetail_log->updated_at       = date('Y-m-d H:i:s');
						$warehousedetail_log->save();
						//add log

						//update source warehouse

						//update destination warehouse
						$warehousedetail_dest = WarehouseDetail::where('warehouse_id', $request->destination_warehouse)
							->where('item_id', $item['item_id'])
							->where('item_uom_id', $item['item_uom_id'])
							->first();
						if ($warehousedetail_dest) {
							$warehousedetail_dest->qty            = ($warehousedetail_dest->qty + $item['qty']);
							$warehousedetail_dest->updated_at       = date('Y-m-d H:i:s');
							$warehousedetail_dest->save();
						} else {
							$warehousedetail_dest = new WarehouseDetail;
							$warehousedetail_dest->warehouse_id         = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
							$warehousedetail_dest->item_id         = $item['item_id'];
							$warehousedetail_dest->item_uom_id            = $item['item_uom_id'];
							$warehousedetail_dest->qty            = $item['qty'];
							$warehousedetail_dest->batch       = '';
							$warehousedetail_dest->save();
						}

						//add log
						$warehousedetail_log = new WarehouseDetailLog;
						$warehousedetail_log->warehouse_id = (!empty($request->destination_warehouse)) ? $request->destination_warehouse : null;
						$warehousedetail_log->warehouse_detail_id = $warehousedetail_dest->id;
						$warehousedetail_log->item_uom_id = $item['item_uom_id'];
						$warehousedetail_log->qty = $item['qty'];
						$warehousedetail_log->action_type = 'Load';
						$warehousedetail_log->created_at       = date('Y-m-d H:i:s');
						$warehousedetail_log->updated_at       = date('Y-m-d H:i:s');
						$warehousedetail_log->save();
						//add log

						//update destination warehouse
					}
				}
			}

			\DB::commit();

			$goodreceiptnote->getSaveData();

			return prepareResult(true, $goodreceiptnote, [], "Good receipt note updated successfully", $this->created);
		} catch (\Exception $exception) {
			\DB::rollback();
			return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
		} catch (\Throwable $exception) {
			\DB::rollback();
			return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
		}
	}
	/**
	 * approve the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $uuid
	 * @return \Illuminate\Http\Response
	 */
	public function approve(Request $request, $uuid)
	{
		if (!$this->isAuthorized) {
			return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
		}

		\DB::beginTransaction();
		try {

			$goodreceiptnote = Goodreceiptnote::where('uuid', $uuid)->first();
			$goodreceiptnotedetail = Goodreceiptnotedetail::where('good_receipt_note_id', $goodreceiptnote->id)
			->first();

			if (is_object($goodreceiptnotedetail)) {
				//----------------
				$routestoragelocation_id = $goodreceiptnote->source_warehouse;
				$warehousestoragelocation_id = $goodreceiptnote->destination_warehouse;
				$routelocation_detail = StoragelocationDetail::where('storage_location_id', $routestoragelocation_id)
					->where('item_id', $goodreceiptnotedetail->item_id)
					->where('item_uom_id', $goodreceiptnotedetail->item_uom_id)
					->first();

				$warehouselocation_detail = StoragelocationDetail::where('storage_location_id', $warehousestoragelocation_id)
					->where('item_id', $goodreceiptnotedetail->item_id)
					->where('item_uom_id', $goodreceiptnotedetail->item_uom_id)
					->first();


				if (is_object($warehouselocation_detail)) {
					$warehouselocation_detail->qty = ($warehouselocation_detail->qty + $goodreceiptnotedetail->qty);
					$warehouselocation_detail->save();
				} else {
					$storagewarehousedetail = new StoragelocationDetail;
					$storagewarehousedetail->storage_location_id = $routestoragelocation_id;
					$storagewarehousedetail->item_id      = $goodreceiptnotedetail->item_id;
					$storagewarehousedetail->item_uom_id  = $goodreceiptnotedetail->item_uom_id;
					$storagewarehousedetail->qty          = $goodreceiptnotedetail->qty;
					$storagewarehousedetail->save();
				}

				if (is_object($routelocation_detail)) {
					$routelocation_detail->qty = ($routelocation_detail->qty - $goodreceiptnotedetail->qty);
					$routelocation_detail->save();
				} else {
					$routestoragedetail = new StoragelocationDetail;
					$routestoragedetail->storage_location_id = $routestoragelocation_id;
					$routestoragedetail->item_id      = $goodreceiptnotedetail->item_id;
					$routestoragedetail->item_uom_id  = $goodreceiptnotedetail->item_uom_id;
					$routestoragedetail->qty          = $goodreceiptnotedetail->qty;
					$routestoragedetail->save();
				}
			}
			//----------------
			\DB::commit();

			return prepareResult(true, $goodreceiptnote, [], "Good receipt note Approved successfully", $this->created);
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
			return prepareResult(false, [], [], "Error while validating good receipt note.", $this->unauthorized);
		}

		$goodreceiptnote = Goodreceiptnote::where('uuid', $uuid)->first();
		if (is_object($goodreceiptnote)) {
			$goodreceiptnoteId = $goodreceiptnote->id;
			$source_warehouse = $goodreceiptnote->source_warehouse;
			$destination_warehouse = $goodreceiptnote->destination_warehouse;
			$goodreceiptnote->delete();
			if ($goodreceiptnote) {
				$goodreceiptnotedetail = Goodreceiptnotedetail::where('good_receipt_note_id', $goodreceiptnoteId)->orderBy('id', 'desc')->get();
				if ($goodreceiptnotedetail) {
					foreach ($goodreceiptnotedetail as $notedetail) {
						$warehousedetail_dest = WarehouseDetail::where('warehouse_id', $destination_warehouse)
							->where('item_id', $notedetail->item_id)
							->where('item_uom_id', $notedetail->item_uom_id)
							->first();
						if ($warehousedetail_dest) {
							$warehousedetail_dest->qty = ($warehousedetail_dest->qty - $notedetail->qty);
							$warehousedetail_dest->save();

							//add log
							$warehousedetail_log = new WarehouseDetailLog;
							$warehousedetail_log->warehouse_id = $destination_warehouse;
							$warehousedetail_log->warehouse_detail_id = $warehousedetail_dest->id;
							$warehousedetail_log->item_uom_id = $notedetail->item_uom_id;
							$warehousedetail_log->qty = $notedetail->qty;
							$warehousedetail_log->action_type = 'Unload';
							$warehousedetail_log->save();
							//add log
						}

						$warehousedetail = WarehouseDetail::where('warehouse_id', $source_warehouse)
							->where('item_id', $notedetail->item_id)
							->where('item_uom_id', $notedetail->item_uom_id)
							->first();
						if ($warehousedetail) {
							$warehousedetail->qty = ($warehousedetail->qty + $notedetail->qty);
							$warehousedetail->save();

							//add log
							$warehousedetail_log = new WarehouseDetailLog;
							$warehousedetail_log->warehouse_id = $source_warehouse;
							$warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
							$warehousedetail_log->item_uom_id = $notedetail->item_uom_id;
							$warehousedetail_log->qty = $notedetail->qty;
							$warehousedetail_log->action_type = 'Load';
							$warehousedetail_log->save();
							//add log
						}
					}
				}
				Goodreceiptnotedetail::where('good_receipt_note_id', $goodreceiptnoteId)->delete();
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating good receipt note", $this->unprocessableEntity);
		}

		$action = $request->action;
		$uuids = $request->goodreceiptnote_ids;

		if (empty($action)) {
			return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
		}

		if ($action == 'delete') {
			foreach ($uuids as $uuid) {
				$goodreceiptnote = Goodreceiptnote::where('uuid', $uuid)->first();
				if (is_object($goodreceiptnote)) {
					$goodreceiptnoteId = $goodreceiptnote->id;
					$source_warehouse = $goodreceiptnote->source_warehouse;
					$destination_warehouse = $goodreceiptnote->destination_warehouse;
					$goodreceiptnote->delete();
					if ($goodreceiptnote) {
						$goodreceiptnotedetail = Goodreceiptnotedetail::where('good_receipt_note_id', $goodreceiptnoteId)->get();
						if ($goodreceiptnotedetail) {
							foreach ($goodreceiptnotedetail as $notedetail) {
								$warehousedetail_dest = WarehouseDetail::where('warehouse_id', $destination_warehouse)
									->where('item_id', $notedetail->item_id)
									->where('item_uom_id', $notedetail->item_uom_id)
									->first();
								if ($warehousedetail_dest) {
									$warehousedetail_dest->qty = ($warehousedetail_dest->qty - $notedetail->qty);
									$warehousedetail_dest->save();

									//add log
									$warehousedetail_log = new WarehouseDetailLog;
									$warehousedetail_log->warehouse_id = $destination_warehouse;
									$warehousedetail_log->warehouse_detail_id = $warehousedetail_dest->id;
									$warehousedetail_log->item_uom_id = $notedetail->item_uom_id;
									$warehousedetail_log->qty = $notedetail->qty;
									$warehousedetail_log->action_type = 'Unload';
									$warehousedetail_log->save();
									//add log
								}

								$warehousedetail = WarehouseDetail::where('warehouse_id', $source_warehouse)
									->where('item_id', $notedetail->item_id)
									->where('item_uom_id', $notedetail->item_uom_id)
									->first();
								if ($warehousedetail) {
									$warehousedetail->qty = ($warehousedetail->qty + $notedetail->qty);
									$warehousedetail->save();

									//add log
									$warehousedetail_log = new WarehouseDetailLog;
									$warehousedetail_log->warehouse_id = $source_warehouse;
									$warehousedetail_log->warehouse_detail_id = $warehousedetail->id;
									$warehousedetail_log->item_uom_id = $notedetail->item_uom_id;
									$warehousedetail_log->qty = $notedetail->qty;
									$warehousedetail_log->action_type = 'Load';
									$warehousedetail_log->save();
									//add log
								}
							}
						}
						Goodreceiptnotedetail::where('good_receipt_note_id', $goodreceiptnoteId)->delete();
					}
				}
			}
			return prepareResult(true, [], [], "Record delete successfully", $this->success);
			$goodreceiptnote = $this->index();
			return prepareResult(true, $goodreceiptnote, [], "good receipt note deleted success", $this->success);
		}
	}
	private function validations($input, $type)
	{
		$errors = [];
		$error = false;
		if ($type == "add") {
			$validator = \Validator::make($input, [
				'source_warehouse' => 'required',
				'destination_warehouse' => 'required',
				'grn_number' => 'required',
				'grn_date' => 'required',
			]);
		}

		if ($type == 'bulk-action') {
			$validator = \Validator::make($input, [
				'action'        => 'required',
				'goodreceiptnote_ids'     => 'required'
			]);
		}

		if ($validator->fails()) {
			$error = true;
			$errors = $validator->errors();
		}

		return ["error" => $error, "errors" => $errors];
	}

	// public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request)
	// {
	//     $createObj = new WorkFlowObject;
	//     $createObj->work_flow_rule_id   = $work_flow_rule_id;
	//     $createObj->module_name         = $module_name;
	//     $createObj->request_object      = $request->all();
	//     $createObj->save();
	// }
}
