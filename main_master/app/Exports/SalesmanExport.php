<?php

namespace App\Exports;

use App\User;
use App\Model\SalesmanInfo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesmanExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $StartDate, $EndDate;
	public function __construct(String  $StartDate, String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
	public function collection()
	{
		$start_date = date('Y-m-d', strtotime('-1 days', strtotime($this->StartDate)));
		$end_date = $this->EndDate;

		$users = SalesmanInfo::select('id', 'user_id', 'route_id', 'region_id', 'salesman_type_id', 'salesman_role_id', 'category_id', 'salesman_helper_id', 'salesman_code', 'salesman_supervisor', 'date_of_joning', 'block_start_date', 'block_end_date', 'profile_image', 'incentive', 'status', 'current_stage', 'current_stage_comment', 'is_lob')
			->with(
				'user:id,usertype,firstname,lastname,email,mobile',
				'route:id,route_code,route_name',
				'salesmanRole:id,name,code',
				'salesmanType:id,name,code',
				'salesmanRange:id,salesman_id,customer_from,customer_to,order_from,order_to,invoice_from,invoice_to,collection_from,collection_to,credit_note_from,credit_note_to,unload_from,unload_to',
				'salesmanlob',
				'salesmanlob.lob',
				'salesmanHelper'
			);

		if ($start_date != '' && $end_date != '') {
			$users = $users->whereBetween('created_at', [$start_date, $end_date]);
		}

		$users = $users->get();


		if (is_object($users)) {
			foreach ($users as $key => $user) {
				if (count($user->salesmanlob)) {
					foreach ($user->salesmanlob as $lob) {
						$this->data($users, $key, $lob);
					}
				} else {
					$this->data($users, $key);
				}
			}
		}

		return $users;
	}

	private function data($users, $key, $lobData = null)
	{
		$salesmanCode = $users[$key]->salesman_code;
		// $salesmanSupervisor = $users[$key]->salesman_supervisor;
		$status = $users[$key]->status;
		$currentStage = $users[$key]->current_stage;
		$category = $users[$key]->category_id;

		$routeName = "";
		if (is_object($users[$key]->route)) {
			$routeName = $users[$key]->route->route_name;
		}

		$salesmanSupervisor = "";
		if (is_object($users[$key]->salesmanSupervisor)) {
			$salesmanSupervisor = $users[$key]->salesmanSupervisor->getName();
		}

		$regionName = "";
		if (is_object($users[$key]->region)) {
			$regionName = $users[$key]->region->region_name;
		}

		$salesman_Role = '';
		if (is_object($users[$key]->salesmanRole)) {
			$salesman_Role = $users[$key]->salesmanRole->name;
		}

		$salesmanType = '-';
		if (is_object($users[$key]->salesmanType)) {
			$salesmanType = $users[$key]->salesmanType->name;
		}
		$lobName = '-';
		if (is_object($lobData)) {
			$lobName = $lobData->lob->name;
		}

		unset($users[$key]->id);
		unset($users[$key]->uuid);
		unset($users[$key]->organisation_id);
		unset($users[$key]->parent_id);
		unset($users[$key]->api_token);
		unset($users[$key]->email_verified_at);
		unset($users[$key]->date_of_joning);
		unset($users[$key]->country_id);
		unset($users[$key]->is_approved_by_admin);
		unset($users[$key]->role_id);
		unset($users[$key]->user_id);
		unset($users[$key]->route_id);
		unset($users[$key]->region_id);
		unset($users[$key]->salesman_type_id);
		unset($users[$key]->salesman_helper_id);
		unset($users[$key]->category_id);
		unset($users[$key]->salesman_code);
		unset($users[$key]->salesman_supervisor);
		unset($users[$key]->date_of_joning);
		unset($users[$key]->block_start_date);
		unset($users[$key]->block_end_date);
		unset($users[$key]->salesman_role_id);
		unset($users[$key]->current_stage_comment);
		unset($users[$key]->profile_image);
		unset($users[$key]->current_stage);
		unset($users[$key]->created_at);
		unset($users[$key]->updated_at);
		unset($users[$key]->deleted_at);
		unset($users[$key]->status);
		unset($users[$key]->is_lob);
		unset($users[$key]->incentive);

		if (is_object($users[$key]->user)) {
			$users[$key]->firstname = $users[$key]->user->firstname;
			$users[$key]->lastname = $users[$key]->user->lastname;
			$users[$key]->email = $users[$key]->user->email;
			$users[$key]->mobile = $users[$key]->user->mobile;
		} else {
			$users[$key]->firstname = "-";
			$users[$key]->lastname = "-";
			$users[$key]->email = "-";
			$users[$key]->mobile = "-";
		}

		$users[$key]->route = "-";
		if ($routeName) {
			$users[$key]->route = $routeName;
		}

		$users[$key]->region = "-";
		if ($regionName) {
			$users[$key]->region = $regionName;
		}

		$users[$key]->status = "No";
		if ($status) {
			$users[$key]->status = "Yes";
		}

		$users[$key]->salesman_role = "-";
		if ($salesman_Role) {
			$users[$key]->salesman_role = $salesman_Role;
		}

		$users[$key]->salesman_type = "-";
		if ($salesmanType) {
			$users[$key]->salesman_type = $salesmanType;
		}

		$users[$key]->salesman_code = "-";
		if ($salesmanCode) {
			$users[$key]->salesman_code = $salesmanCode;
		}

		$users[$key]->salesman_supervisor = "-";
		if ($salesmanSupervisor) {
			$users[$key]->salesman_supervisor = $salesmanSupervisor;
		}

		$users[$key]->category = "-";
		if ($category) {
			$users[$key]->category = $category;
		}

		$users[$key]->lob_name = "-";
		if ($lobName) {
			$users[$key]->lob_name = $lobName;
		}

		if (is_object($users[$key]->salesmanRange)) {
			$users[$key]->customer_from = $users[$key]->salesmanRange->customer_from;
			$users[$key]->customer_to = $users[$key]->salesmanRange->customer_to;
			$users[$key]->order_from = $users[$key]->salesmanRange->order_from;
			$users[$key]->order_to = $users[$key]->salesmanRange->order_to;
			$users[$key]->invoice_from = $users[$key]->salesmanRange->invoice_from;
			$users[$key]->invoice_to = $users[$key]->salesmanRange->invoice_to;
			$users[$key]->collection_from = $users[$key]->salesmanRange->collection_from;
			$users[$key]->collection_to = $users[$key]->salesmanRange->collection_to;
			$users[$key]->credit_note_from = $users[$key]->salesmanRange->credit_note_from;
			$users[$key]->credit_note_to = $users[$key]->salesmanRange->credit_note_to;
			$users[$key]->unload_from = $users[$key]->salesmanRange->unload_from;
			$users[$key]->unload_to = $users[$key]->salesmanRange->unload_to;
		} else {
			$users[$key]->customer_from = "-";
			$users[$key]->customer_to = "-";
			$users[$key]->order_from = "-";
			$users[$key]->order_to = "-";
			$users[$key]->invoice_from = "-";
			$users[$key]->invoice_to = "-";
			$users[$key]->collection_from = "-";
			$users[$key]->collection_to = "-";
			$users[$key]->credit_note_from = "-";
			$users[$key]->credit_note_to = "-";
			$users[$key]->unload_from = "-";
			$users[$key]->unload_to = "-";
		}

		$users[$key]->current_stage = $currentStage;
		return $users;
	}

	public function headings(): array
	{
		return [
			"First Name",
			"Last Name",
			"Email",
			"Mobile",
			"Route",
			"Region",
			"Status",
			"Salesman Type",
			"Salesman Role",
			"Salesman Code",
			"Salesman Supervisor",
			"Category",
			"LOB",
			'customer_from',
			'customer_to',
			"Order From",
			"Order To",
			"Invoice From",
			"Invoice To",
			"Collection From",
			"Collection To",
			"Return From",
			"Return To",
			"Unload From",
			"Unload To",
			"Salesman Status"
		];
	}
}
