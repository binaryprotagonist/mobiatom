<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Collection;
use App\Model\CreditNote;
use Illuminate\Http\Request;
use App\Model\WorkFlowRule;
use App\Model\WorkFlowRuleApprovalRole;
use App\Model\WorkFlowRuleApprovalUser;
use App\Model\CollectionDetails;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\CustomerInfo;
use App\Model\DebitNote;
use App\Model\Delivery;
use App\Model\Invoice;
use App\Model\JourneyPlan;
use App\Model\LoadRequest;
use App\Model\Order;
use App\Model\SalesmanInfo;
use Ixudra\Curl\Facades\Curl;

class WFMApprovalRequestController extends Controller
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

		$workFlowRules = WorkFlowObject::select(
			'work_flow_objects.id as id',
			'work_flow_objects.uuid as uuid',
			'work_flow_objects.work_flow_rule_id',
			'work_flow_objects.module_name',
			'work_flow_objects.request_object',
			'work_flow_objects.currently_approved_stage',
			'work_flow_rules.work_flow_rule_name',
			'work_flow_rules.description',
			'work_flow_rules.event_trigger'
		)
			->withoutGlobalScope('organisation_id')
			->join('work_flow_rules', function ($join) {
				$join->on('work_flow_objects.work_flow_rule_id', '=', 'work_flow_rules.id');
			})
			->where('work_flow_objects.organisation_id', auth()->user()->organisation_id)
			->where('status', '1')
			->where('is_approved_all', '0')
			->where('is_anyone_reject', '0')
			->get();

		$results = [];
		foreach ($workFlowRules as $key => $obj) {
			$checkCondition = WorkFlowRuleApprovalRole::query();
			if ($obj->currently_approved_stage > 0) {
				$checkCondition->skip($obj->currently_approved_stage);
			}

			$getResult = $checkCondition->where('work_flow_rule_id', $obj->work_flow_rule_id)
				->orderBy('id', 'ASC')
				->first();
			$userIds = [];
			if (is_object($getResult) && $getResult->workFlowRuleApprovalUsers->count() > 0) {
				//User based approval
				foreach ($getResult->workFlowRuleApprovalUsers as $prepareUserId) {
					$userIds[] = $prepareUserId->user_id;
				}

				if (in_array(auth()->id(), $userIds)) {
					$results[] = [
						'object'	=> $obj,
						'Action'	=> 'User'
					];
				}
			} else {
				//Roles based approval
				if (is_object($getResult) && $getResult->organisation_role_id == auth()->user()->role_id)
					$results[] = [
						'object'	=> $obj,
						'Action'	=> 'Role'
					];
			}
		}

		return prepareResult(true, $results, [], "Request for approval.", $this->success);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $uuid
	 * @return \Illuminate\Http\Response
	 */
	public function action(Request $request, $uuid)
	{
		if (!$this->isAuthorized) {
			return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
		}

		$input = $request->json()->all();
		$validate = $this->validations($input, "add");
		if ($validate["error"]) {
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating approval action", $this->unprocessableEntity);
		}

		\DB::beginTransaction();
		try {
			$actionPerformed = WorkFlowObject::where('uuid', $uuid)
				->first();

			if (is_object($actionPerformed)) {
				if (request()->user()->usertype == 1) {
					if ($request->action == 1) {
						if (is_object($actionPerformed->workFlowRule->workFlowRuleApprovalUsers)) {
							foreach ($actionPerformed->workFlowRule->workFlowRuleApprovalUsers as $approve_user) {
								$actionPerformed->currently_approved_stage = $actionPerformed->currently_approved_stage + 1;
							}
						}
					} else {
						$actionPerformed->is_anyone_reject = 1;
					}
					$actionPerformed->save();

					if (is_object($actionPerformed->workFlowRule->workFlowRuleApprovalUsers)) {
						foreach ($actionPerformed->workFlowRule->workFlowRuleApprovalUsers as $approve_user) {
							//Add log
							$addLog = new WorkFlowObjectAction;
							$addLog->work_flow_object_id = $actionPerformed->id;
							$addLog->user_id = $approve_user->user_id;
							$addLog->approved_or_rejected = $request->action;
							$addLog->save();
						}
					}

					if ($actionPerformed->module_name == 'Collection') {
						$this->collection($actionPerformed);
					}
				} else {
					if ($request->action == 1) {
						$actionPerformed->currently_approved_stage = $actionPerformed->currently_approved_stage + 1;
					} else {
						$actionPerformed->is_anyone_reject = 1;
					}
					$actionPerformed->save();

					//Add log
					$addLog = new WorkFlowObjectAction;
					$addLog->work_flow_object_id = $actionPerformed->id;
					$addLog->user_id = auth()->id();
					$addLog->approved_or_rejected = $request->action;
					$addLog->save();
				}

				$totalLevelDefine = $actionPerformed->workFlowRule->workFlowRuleApprovalRoles->count();
				$countActionTotal = $actionPerformed->workFlowObjectActions->count();

				if ($totalLevelDefine <= $countActionTotal) {
					$actionPerformed->is_approved_all = 1;
					$actionPerformed->save();

					$getObj = $actionPerformed->request_object;
					if ($actionPerformed->workFlowRule->event_trigger == 'deleted') {
						//delete logic here according to module
					} else {

						//add && update logic here according to module
						$wfrau = WorkFlowRuleApprovalUser::where('work_flow_rule_id', $actionPerformed->work_flow_rule_id)->get();
						if ($request->action == 1) {
							if ($actionPerformed->module_name == 'Customer') {
								$CustomerInfo = CustomerInfo::find($actionPerformed->raw_id);
								$CustomerInfo->current_stage = 'Approved';
								$CustomerInfo->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $CustomerInfo);
							} else if ($actionPerformed->module_name == 'Journey Plan') {

								$JourneyPlan = JourneyPlan::find($actionPerformed->raw_id);
								$JourneyPlan->current_stage = 'Approved';
								$JourneyPlan->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $JourneyPlan);
							} else if ($actionPerformed->module_name == 'Credit Note') {

								$CreditNote = CreditNote::find($actionPerformed->raw_id);
								$CreditNote->current_stage = 'Approved';
								$CreditNote->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $CreditNote);
							} else if ($actionPerformed->module_name == 'Invoice') {

								$Invoice = Invoice::find($actionPerformed->raw_id);
								$Invoice->current_stage = 'Approved';
								$Invoice->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Invoice);
							} else if ($actionPerformed->module_name == 'Deliviery') {

								$Delivery = Delivery::find($actionPerformed->raw_id);
								$Delivery->current_stage = 'Approved';
								$Delivery->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Delivery);
							} else if ($actionPerformed->module_name == 'Order') {

								$Order = Order::find($actionPerformed->raw_id);
								$Order->current_stage = 'Approved';
								$Order->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Order);
							} else if ($actionPerformed->module_name == 'Salesman') {

								$SalesmanInfo = SalesmanInfo::find($actionPerformed->raw_id);
								$SalesmanInfo->current_stage = 'Approved';
								$SalesmanInfo->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $SalesmanInfo);
							} else if ($actionPerformed->module_name == 'Debit Note') {

								$DebitNote = DebitNote::find($actionPerformed->raw_id);
								$DebitNote->current_stage = 'Approved';
								$DebitNote->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $DebitNote);
							} else if ($actionPerformed->module_name == 'Collection') {
								$this->collection($actionPerformed, $wfrau);
								if ($totalLevelDefine == $countActionTotal) {
									$this->sendToOdooCollection($actionPerformed);
								}
								/* same process of credit and edit end */
							} else if ($actionPerformed->module_name == 'Load Request') {
								$load_request = LoadRequest::find($actionPerformed->raw_id);
								$load_request->current_stage = 'Approved';
								$load_request->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $load_request);
							}
						} else {
							if ($actionPerformed->module_name == 'Customer') {
								$CustomerInfo = CustomerInfo::find($actionPerformed->raw_id);
								$CustomerInfo->current_stage = 'Rejected';
								$CustomerInfo->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $CustomerInfo);
							} else if ($actionPerformed->module_name == 'Journey Plan') {
								$JourneyPlan = JourneyPlan::find($actionPerformed->raw_id);
								$JourneyPlan->current_stage = 'Rejected';
								$JourneyPlan->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $JourneyPlan);
							} else if ($actionPerformed->module_name == 'Credit Note') {
								$CreditNote = CreditNote::find($actionPerformed->raw_id);
								$CreditNote->current_stage = 'Rejected';
								$CreditNote->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $CreditNote);
							} else if ($actionPerformed->module_name == 'Invoice') {

								$Invoice = Invoice::find($actionPerformed->raw_id);
								$Invoice->current_stage = 'Rejected';
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Invoice);
								$Invoice->save();
							} else if ($actionPerformed->module_name == 'Deliviery') {
								$Delivery = Delivery::find($actionPerformed->raw_id);
								$Delivery->current_stage = 'Rejected';
								$Delivery->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Delivery);
							} else if ($actionPerformed->module_name == 'Order') {
								$Order = Order::find($actionPerformed->raw_id);
								$Order->current_stage = 'Rejected';
								$Order->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Order);
							} else if ($actionPerformed->module_name == 'Salesman') {
								$SalesmanInfo = SalesmanInfo::find($actionPerformed->raw_id);
								$SalesmanInfo->current_stage = 'Rejected';
								$SalesmanInfo->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $SalesmanInfo);
							} else if ($actionPerformed->module_name == 'Debit Note') {
								$DebitNote = DebitNote::find($actionPerformed->raw_id);
								$DebitNote->current_stage = 'Rejected';
								$DebitNote->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $DebitNote);
							} else if ($actionPerformed->module_name == 'Collection') {
								$Collection = Collection::find($actionPerformed->raw_id);
								$Collection->current_stage = 'Rejected';
								$Collection->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Collection);
							} else if ($actionPerformed->module_name == 'Load Request') {
								$load_request = LoadRequest::find($actionPerformed->raw_id);
								$load_request->current_stage = 'Rejected';
								$load_request->save();
								$this->sendNotificationToNextUser($wfrau, $actionPerformed, $load_request);
							}
						}
					}
				}
				\DB::commit();
				return prepareResult(true, $addLog, [], "Action completed successfully", $this->success);
			} else {
				return prepareResult(false, [], "Record not found", "Record not found", $this->internal_server_error);
			}
		} catch (\Exception $exception) {
			\DB::rollback();
			return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
		} catch (\Throwable $exception) {
			\DB::rollback();
			return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
		}
	}

	private function collection($actionPerformed, $wfrau = null)
	{
		$Collection = Collection::with('collectiondetails')->find($actionPerformed->raw_id);

		$Collection->current_stage = 'Approved';

		// Collection Status
		if ($Collection->payemnt_type == 1) {
			$Collection->collection_status = 'Posted';
		} else if ($Collection->payemnt_type == 2) {
			$Collection->collection_status = 'PDC';
		}

		$Collection->save();
		if (is_array($wfrau)) {
			$this->sendNotificationToNextUser($wfrau, $actionPerformed, $Collection);
		}

		/* same process of credit and edit start */
		if (count($Collection->collectiondetails)) {
			foreach ($Collection->collectiondetails as $item) {
				if ($item->type == 1) {
					$invoice = Invoice::find($item->invoice_id);
					if (is_object($invoice)) {

						// Minues
						$pending_amount = $invoice->pending_credit - $item->amount;

						if ($pending_amount == "0") {
							$invoice->payment_received = '1';
						} else {
							$invoice->payment_received = '0';
						}
						$invoice->pending_credit = $pending_amount;
						// $invoice->pending_credit = (!empty($Collection->payemnt_type) && $Collection->payemnt_type == '2') ? null : $invoice->pending_credit - $item['amount'];
						// $invoice->pending_credit = $invoice->pending_credit - $item['amount'];
						$invoice->save();
					} else {
						$pending_amount = 0.00;
					}
				} else if ($item['type'] == 2) {
					$invoice = DebitNote::find($item->invoice_id);
					if (is_object($invoice)) {
						$pending_amount = $invoice->grand_total - $item->amount;
						// $invoice->pending_credit = $invoice->pending_credit - $item['amount'];
						$invoice->pending_credit = $pending_amount;
						// $invoice->pending_credit = (!empty($Collection->payemnt_type) && $Collection->payemnt_type == '2') ? null : $invoice->pending_credit - $item['amount'];
						$invoice->save();
					} else {
						$pending_amount = 0.00;
					}
				} else if ($item['type'] == 3) {
					$invoice = CreditNote::find($item['invoice_id']);
					if (is_object($invoice)) {
						$pending_amount = $invoice->grand_total - $item->amount;
						// $invoice->pending_credit = $invoice->pending_credit - $item['amount'];
						$invoice->pending_credit = $pending_amount;
						// $invoice->pending_credit = (!empty($Collection->payemnt_type) && $Collection->payemnt_type == '1') ? null : $invoice->pending_credit - $item['amount'];
						// $invoice->pending_credit = (!empty($Collection->payemnt_type) && $Collection->payemnt_type == '2') ? null : $invoice->pending_credit - $item['amount'];
						$invoice->save();
					} else {
						$pending_amount = 0.00;
					}
				}

				// $collectiondetail = new CollectionDetails;
				// $collectiondetail->collection_id = $actionPerformed->raw_id;
				// $collectiondetail->customer_id = (!empty($item['customer_id'])) ? $item['customer_id'] : null;
				// $collectiondetail->lob_id     = (!empty($request->lob_id)) ? $request->lob_id : null;
				// $collectiondetail->invoice_id = $item['invoice_id'];
				// $collectiondetail->amount = $item['amount'];
				// $collectiondetail->type = $item['type'];
				// $collectiondetail->pending_amount = $pending_amount;
				// $collectiondetail->save();
			}
		}
	}

	private function validations($input, $type)
	{
		$errors = [];
		$error = false;
		if ($type == "add") {
			$validator = \Validator::make($input, [
				'action' => 'required'
			]);
		}

		if ($validator->fails()) {
			$error = true;
			$errors = $validator->errors();
		}

		return ["error" => $error, "errors" => $errors];
	}

	/*
	*
	* This function send to the next user to notification
	* Created By Hardik Solanki
	*
	*/

	private function sendNotificationToNextUser($wfrau, $actionPerformed, $obj)
	{
		if (count($wfrau)) {
			foreach ($wfrau as $user) {
				$wfoa = WorkFlowObjectAction::where('user_id', $user->user_id)->first();
				if (!is_object($wfoa)) {
					// Send Notification
					$data = array(
						'uuid' => (is_object($obj)) ? $obj->uuid : 0,
						'user_id' => $user->user_id,
						'type' => $actionPerformed->module_name,
						'message' => "Approve the New " . $actionPerformed->module_name,
						'status' => 1,
					);
					saveNotificaiton($data);
				}
			}
		}
	}

	private function sendToOdooCollection($actionPerformed)
	{
		$collection = Collection::with(
			'invoice',
			'customer:id,firstname,lastname',
			'customer.customerInfo:id,user_id,customer_code',
			'salesman:id,firstname,lastname',
			'salesman.salesmanInfo:id,user_id,salesman_code',
			'lob',
			'collectiondetails',
			'collectiondetails.customer:id,firstname,lastname',
			'collectiondetails.customer.customerInfo:id,user_id,customer_code',
			'collectiondetails.invoice:id,grand_total,invoice_number,total_net',
			'collectiondetails.debit_note:id,debit_note_number,total_net,grand_total',
			'collectiondetails.credit_note:id,credit_note_number,total_net,grand_total',
			'collectiondetails.lob:id,name',
		)->find($actionPerformed->raw_id);

		$response = Curl::to('http://rfctest.dyndns.org:11214/api/create/payment')
			->withData(array('params' => $collection))
			->asJson(true)
			->post();

		if (isset($response['result'])) {
			$data = json_decode($response['result']);
			if ($data->response[0]->state == "success") {
				$collection->oddo_post_id = $data->response[0]->inv_id;
			} else {
				$collection->odoo_failed_response = $response['result'];
			}
		}

		if (isset($response['error'])) {
			$collection->odoo_failed_response = $response['error'];
		}

		$collection->save();

		if (!empty($collection->oddo_post_id)) {
			return prepareResult(true, $collection, [], "Collection posted sucessfully", $this->success);
		}

		return prepareResult(false, $collection, [], "Collection not posted", $this->unprocessableEntity);
	}
}
