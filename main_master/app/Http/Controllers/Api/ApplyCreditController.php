<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CreditNote;
use App\Model\CreditNoteDetail;
use App\Model\Invoice;
use App\Model\Collection;
use App\Model\CollectionDetails;
use App\Model\CustomerInfo;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\InvoiceDetail;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\Trip;

class ApplyCreditController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index($creditnote_number)
	{
		if (!$this->isAuthorized) {
			return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
		}
		$invoices_array = array();
		$creditnotes = CreditNote::where('credit_note_number', $creditnote_number)->get();
		if (is_object($creditnotes)) {
			foreach ($creditnotes as $creditnote) {
				$invoice = Invoice::find($creditnote->invoice_id);
				if (is_object($invoice)) {
					$CollectionDetails = CollectionDetails::where('invoice_id', $invoice->id)->first();
					if (is_object($CollectionDetails)) {
						$invoice->balance = $CollectionDetails->pending_amount;
					} else {
						$invoice->balance = 0.00;
					}

					$invoices_array[] = $invoice;
				}
			}
		}

		$data_array = array();
		$page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
		$limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
		$pagination = array();
		if ($page != '' && $limit != '') {
			$offset = ($page - 1) * $limit;
			for ($i = 0; $i < $limit; $i++) {
				if (isset($invoices_array[$offset])) {
					$data_array[] = $invoices_array[$offset];
				}
				$offset++;
			}

			$pagination['total_pages'] = ceil(count($invoices_array) / $limit);
			$pagination['current_page'] = (int)$page;
			$pagination['total_records'] = count($invoices_array);
		} else {
			$data_array = $invoices_array;
		}
		
		return prepareResult(true, $data_array, [], "Invoice listing", $this->success, $pagination);
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
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating apply credit", $this->unprocessableEntity);
		}

		if (is_array($request->items) && sizeof($request->items) < 1) {
			return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
		}

		\DB::beginTransaction();
		try {
			if (is_array($request->items)) {
				foreach ($request->items as $item) {
					$invoice = Invoice::where('id', $item['invoice_id'])->first();
					if (is_object($invoice)) {
						$pending_amount = $item['balance'] - $item['amount'];
						if ($item['balance'] <= $item['amount']) {
							$invoice->payment_received = '1';
						} else {
							$invoice->payment_received = '0';
						}
						$invoice->save();

						$trip = Trip::find($invoice->trip_id);
						if (is_object($trip)) {
							$salesman_id = $trip->salesmancode;
						} else {
							$salesman_id = 0;
						}
					} else {
						$pending_amount = 0.00;
						$salesman_id = 0;
					}

					$collection = new Collection;
					$collection->invoice_id         = $item['invoice_id'];
					$collection->customer_id            = $invoice->customer_id;
					$collection->salesman_id            = $salesman_id;
					$collection->collection_type            = '1';
					$collection->collection_number            = (!empty($request->credit_note_number)) ? $request->credit_note_number : null;
					$collection->payemnt_type            = '1';
					$collection->invoice_amount            = $item['amount'];
					$collection->cheque_number            = null;
					$collection->cheque_date            = null;
					$collection->bank_info            = null;
					$collection->transaction_number            = null;
					$collection->source            = $item['source'];
					$collection->current_stage            = 'Pending';
					$collection->current_stage_comment            = $request->current_stage_comment;
					$collection->status            = 1;
					$collection->save();

					$collectiondetail = new CollectionDetails();
					$collectiondetail->collection_id = $collection->id;
					$collectiondetail->invoice_id = $item['invoice_id'];
					$collectiondetail->amount = $item['amount'];
					$collectiondetail->pending_amount = $pending_amount;
					$collectiondetail->save();
				}
			}


			\DB::commit();
			return prepareResult(true, $collection, [], "Apply Credit added successfully", $this->created);
		} catch (\Exception $exception) {
			\DB::rollback();
			return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
		} catch (\Throwable $exception) {
			\DB::rollback();
			return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
		}
	}
	private function validations($input, $type)
	{
		$errors = [];
		$error = false;
		if ($type == "add") {
			$validator = \Validator::make($input, [
				'credit_note_number' => 'required'

			]);
		}
		return ["error" => $error, "errors" => $errors];
	}
}
