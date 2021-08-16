<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use URL;
use Illuminate\Http\Request;
use App\User;
use App\Model\CustomerInfo;
use App\Model\CustomerType;
use App\Model\PaymentTerm;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use App\Exports\SalesmanExport;
use App\Exports\RegionExport;
use App\Exports\DepotExport;
use App\Exports\VanExport;
use App\Exports\RouteExport;
use App\Exports\ItemExport;
use App\Exports\OrderExport;
use App\Exports\DeliveryExport;
use App\Exports\CreditnoteExport;
use App\Exports\ItemuomExport;
use App\Exports\JourneyPlanExport;
use App\Exports\InvoiceExport;
use App\Exports\DebitnoteExport;
use App\Exports\WarehouseExport;
use App\Exports\CollectionExport;
use App\Exports\VendorExport;
use App\Exports\BankExport;
use App\Exports\PurchaseorderExport;
use App\Exports\ExpensesExport;
use App\Exports\EstimationExport;
use App\Exports\PlanogramExport;
use App\Exports\DistributionExport;
use App\Exports\CompetitorinfoExport;
use App\Exports\ComplaintfeedbackExport;
use App\Exports\CampaignPictureExport;
use App\Exports\AssetTrackingExport;
use App\Exports\ConsumerSurveyExport;
use App\Exports\PromotionalsExport;
use App\Exports\AssignInventoryExport;
use App\Exports\DailyActivityExport;
use App\Exports\DistributionModelStock;
use App\Exports\DistributionModelStockExport;
use App\Exports\JourneyPlanDetailsExport;
use App\Exports\TodoExport;
use App\Model\Distribution;

class ExportController extends Controller
{
	public function export(Request $request)
	{
		if (!$this->isAuthorized) {
			return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
		}
		$input = $request->json()->all();

		$validate = $this->validations($input, "export");
		if ($validate["error"]) {
			return prepareResult(false, [], $validate['errors']->first(), "Error while validating export", $this->unprocessableEntity);
		}

		$org_id = $request->user()->organisation_id;

		$module = $request->module;
		$criteria = $request->criteria;
		$file_type = $request->file_type;
		$is_password_protected = $request->is_password_protected;
		$start_date = '';
		$end_date = '';
		if ($criteria != 'all') {
			$start_date = $request->start_date;
			$end_date = $request->end_date;
			if ($start_date == '' || $end_date == '') {
				return prepareResult(false, [], [], "Start date and End date required", $this->unauthorized);
			}
		}
		if ($module == 'customer') {
			Excel::store(new UsersExport($start_date, $end_date), 'customer_export.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/customer_export.' . $request->file_type));
		} else if ($module == 'salesman') {
			Excel::store(new SalesmanExport($start_date, $end_date), $org_id . '_' . time() . '_salesman.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/export/' . $org_id . '_' . time() . '_salesman.' . $request->file_type));
		} else if ($module == 'region') {
			Excel::store(new RegionExport($start_date, $end_date), $org_id . '_region.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_region.' . $request->file_type));
		} else if ($module == 'depot') {
			Excel::store(new DepotExport($start_date, $end_date), $org_id . '_depot.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_depot.' . $request->file_type));
		} else if ($module == 'van') {
			Excel::store(new VanExport($start_date, $end_date), $org_id . '_van.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_van.' . $request->file_type));
		} else if ($module == 'route') {
			Excel::store(new RouteExport($start_date, $end_date), $org_id . '_route.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_route.' . $request->file_type));
		} else if ($module == 'item') {
			Excel::store(new ItemExport($start_date, $end_date), $org_id . '_' . time() . '_item.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_' . time() . '_item.' . $request->file_type));
		} else if ($module == 'order') {
			Excel::store(new OrderExport($start_date, $end_date), $org_id . '_order.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_order.' . $request->file_type));
		} else if ($module == 'delivery') {
			Excel::store(new DeliveryExport($start_date, $end_date), $org_id . '_delivery.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_delivery.' . $request->file_type));
		} else if ($module == 'creditnote') {
            Excel::store(new CreditnoteExport($start_date, $end_date), $org_id . '_creditnote.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_creditnote.' . $request->file_type));
		} else if ($module == 'itemuom') {
			Excel::store(new ItemuomExport($start_date, $end_date), $org_id . '_item_uom.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_item_uom.' . $request->file_type));
		} else if ($module == 'journeyplan') {
			Excel::store(new JourneyPlanDetailsExport($start_date, $end_date), $org_id . '_journey_plan.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_journey_plan.' . $request->file_type));
		} else if ($module == 'invoice') {
			Excel::store(new InvoiceExport($start_date, $end_date), 'invoice.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/invoice.' . $request->file_type));
		} else if ($module == 'debitnote') {
			Excel::store(new DebitnoteExport($start_date, $end_date), 'debit_note.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/debit_note.' . $request->file_type));
		} else if ($module == 'warehouse') {
			Excel::store(new WarehouseExport($start_date, $end_date), 'warehouse.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/warehouse.' . $request->file_type));
		} else if ($module == 'collection') {
			Excel::store(new CollectionExport($start_date, $end_date), 'collection.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/collection.' . $request->file_type));
		} else if ($module == 'vendor') {
			Excel::store(new VendorExport($start_date, $end_date), 'vendor.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/vendor.' . $request->file_type));
		} else if ($module == 'bank') {
			Excel::store(new BankExport($start_date, $end_date), 'bank.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/bank.' . $request->file_type));
		} else if ($module == 'purchaseorder') {
			Excel::store(new PurchaseorderExport($start_date, $end_date), 'purchaseorder.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/purchaseorder.' . $request->file_type));
		} else if ($module == 'expenses') {
			Excel::store(new ExpensesExport($start_date, $end_date), 'expenses.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/expenses.' . $request->file_type));
		} else if ($module == 'estimation') {
			Excel::store(new EstimationExport($start_date, $end_date), 'estimation.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/estimation.' . $request->file_type));
		} else if ($module == 'planogram') {
			Excel::store(new PlanogramExport($start_date, $end_date), 'planogram.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/planogram.' . $request->file_type));
		} else if ($module == 'distribution') {
			Excel::store(new DistributionExport($start_date, $end_date), 'distribution.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/distribution.' . $request->file_type));
		} else if ($module == 'competitorinfo') {
			Excel::store(new CompetitorinfoExport($start_date, $end_date), 'competitorinfo.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/competitorinfo.' . $request->file_type));
		} else if ($module == 'complaintfeedback') {
			Excel::store(new ComplaintfeedbackExport($start_date, $end_date), 'complaintfeedback.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/complaintfeedback.' . $request->file_type));
		} else if ($module == 'campaignpictures') {
			Excel::store(new CampaignPictureExport($start_date, $end_date), 'campaignpictures.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/campaignpictures.' . $request->file_type));
		} else if ($module == 'assettracking') {
			Excel::store(new AssetTrackingExport($start_date, $end_date), 'assettracking.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/assettracking.' . $request->file_type));
		} else if ($module == 'consumersurvey') {
			Excel::store(new ConsumerSurveyExport($start_date, $end_date), 'consumersurvey.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/consumersurvey.' . $request->file_type));
		} else if ($module == 'promotionals') {
			Excel::store(new PromotionalsExport($start_date, $end_date), 'promotionals.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/promotionals.' . $request->file_type));
		} else if ($module == 'stockinstore') {
			Excel::store(new AssignInventoryExport($start_date, $end_date), 'stockinstore.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/stockinstore.' . $request->file_type));
		} else if ($module == 'distributinModelStock') {
			Excel::store(new DistributionModelStockExport($start_date, $end_date), 'distributionModelStock.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/distributionModelStock.' . $request->file_type));
		} else if ($module == 'todo') {
			Excel::store(new TodoExport($start_date, $end_date), $org_id . '_todo.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_todo.' . $request->file_type));
		} else if ($module == 'daily-activity') {
			Excel::store(new DailyActivityExport($start_date, $end_date), $org_id . '_daily_activity.' . $request->file_type);
			$result['file_url'] = str_replace('public/', '', URL::to('/storage/app/' . $org_id . '_daily_activity.' . $request->file_type));
		}

		return prepareResult(true, $result, [], "Data successfully exported", $this->created);
	}
	private function validations($input, $type)
	{
		$errors = [];
		$error = false;
		if ($type == "export") {
			$validator = \Validator::make($input, [
				'module' => 'required',
				'criteria' => 'required',
				'file_type' => 'required',
				'is_password_protected' => 'required'
			]);
		}
		if ($validator->fails()) {
			$error = true;
			$errors = $validator->errors();
		}

		return ["error" => $error, "errors" => $errors];
	}
}
