<?php

namespace App\Exports;

use App\User;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanWeek;
use App\Model\JourneyPlanDay;
use App\Model\Route;
use App\Model\CustomerInfo;
use App\Model\JourneyPlanCustomer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class JourneyPlanExport implements FromCollection, WithHeadings
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
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;
		$JourneyPlan = JourneyPlan::select(
			'id',
			'name',
			'description',
			'start_date',
			'no_end_date',
			'end_date',
			'start_time',
			'end_time',
			'start_day_of_the_week',
			'plan_type',
			'week_1',
			'week_2',
			'week_3',
			'week_4',
			'week_5',
			'route_id'
		);
		if ($start_date != '' && $end_date != '') {
			$JourneyPlan = $JourneyPlan->whereBetween('created_at', [$start_date, $end_date]);
		}
		$JourneyPlan = $JourneyPlan->get();

		$JourneyPlanCollection = new Collection();

		if (is_object($JourneyPlan)) {
			foreach ($JourneyPlan as $key => $JourneyPlan1) {
				/* unset($JourneyPlan[$key]->id);
				unset($JourneyPlan[$key]->uuid);
				unset($JourneyPlan[$key]->organisation_id);
				unset($JourneyPlan[$key]->current_stage);
				unset($JourneyPlan[$key]->current_stage_comment);
				unset($JourneyPlan[$key]->status); */

				$route = Route::find($JourneyPlan[$key]->route_id);
				$JourneyPlan[$key]->route = (is_object($route)) ? $route->route_name : '';

				if ($JourneyPlan[$key]->plan_type == 2) {
					$JourneyPlanWeek = JourneyPlanWeek::where('journey_plan_id', $JourneyPlan[$key]->id)->get();
					if (is_object($JourneyPlanWeek)) {
						foreach ($JourneyPlanWeek as $planweek) {
							$JourneyPlanDay = JourneyPlanDay::where('journey_plan_id', $JourneyPlan[$key]->id)
								->where('journey_plan_week_id', $planweek->id)->get();
							if (is_object($JourneyPlanDay)) {
								foreach ($JourneyPlanDay as $planday) {
									$JourneyPlanCustomer = JourneyPlanCustomer::where('journey_plan_id', $JourneyPlan[$key]->id)
										->where('journey_plan_day_id', $planday->id)->get();
									if (is_object($JourneyPlanCustomer)) {
										foreach ($JourneyPlanCustomer as $plancustomer) {
											$customer = User::find($plancustomer->customer_id);
											$JourneyPlanCollection->push((object)[
												'name' => $JourneyPlan[$key]->name,
												'description' => $JourneyPlan[$key]->description,
												'start_date' => $JourneyPlan[$key]->start_date,
												'no_end_date' => $JourneyPlan[$key]->no_end_date,
												'end_date' => $JourneyPlan[$key]->end_date,
												'start_time' => $JourneyPlan[$key]->start_time,
												'end_time' => $JourneyPlan[$key]->end_time,
												'route' => (is_object($route)) ? $route->route_name : '',
												'plan_type' => $JourneyPlan[$key]->plan_type,
												'week_1' => $JourneyPlan[$key]->week_1,
												'week_2' => $JourneyPlan[$key]->week_2,
												'week_3' => $JourneyPlan[$key]->week_3,
												'week_4' => $JourneyPlan[$key]->week_4,
												'week_5' => $JourneyPlan[$key]->week_5,
												'start_day_of_the_week' => $JourneyPlan[$key]->start_day_of_the_week,
												'week_number' => $planweek->week_number,
												'day_name' => $planday->day_name,
												'day_number' => $planday->day_number,
												'customer_email' => (is_object($customer)) ? $customer->email : '',
												'day_customer_sequence' => $plancustomer->day_customer_sequence,
												'day_start_time' => $plancustomer->day_start_time,
												'day_end_time' => $plancustomer->day_end_time,
											]);
										}
									}
								}
							}
						}
					}
				} else {
					$JourneyPlanDay = JourneyPlanDay::where('journey_plan_id', $JourneyPlan[$key]->id)->get();
					if (is_object($JourneyPlanDay)) {
						foreach ($JourneyPlanDay as $planday) {
							$JourneyPlanCustomer = JourneyPlanCustomer::where('journey_plan_id', $JourneyPlan[$key]->id)
								->where('journey_plan_day_id', $planday->id)->get();
							if (is_object($JourneyPlanCustomer)) {
								foreach ($JourneyPlanCustomer as $plancustomer) {
									$customer = User::find($plancustomer->customer_id);
									$JourneyPlanCollection->push((object)[
										'name' => $JourneyPlan[$key]->name,
										'description' => $JourneyPlan[$key]->description,
										'start_date' => $JourneyPlan[$key]->start_date,
										'no_end_date' => $JourneyPlan[$key]->no_end_date,
										'end_date' => $JourneyPlan[$key]->end_date,
										'start_time' => $JourneyPlan[$key]->start_time,
										'end_time' => $JourneyPlan[$key]->end_time,
										'route' => (is_object($route)) ? $route->route_name : '',
										'plan_type' => $JourneyPlan[$key]->plan_type,
										'week_1' => $JourneyPlan[$key]->week_1,
										'week_2' => $JourneyPlan[$key]->week_2,
										'week_3' => $JourneyPlan[$key]->week_3,
										'week_4' => $JourneyPlan[$key]->week_4,
										'week_5' => $JourneyPlan[$key]->week_5,
										'start_day_of_the_week' => $JourneyPlan[$key]->start_day_of_the_week,
										'week_number' => '',
										'day_name' => $planday->day_name,
										'day_number' => $planday->day_number,
										'customer_email' => (is_object($customer)) ? $customer->email : '',
										'day_customer_sequence' => $plancustomer->day_customer_sequence,
										'day_start_time' => $plancustomer->day_start_time,
										'day_end_time' => $plancustomer->day_end_time,
									]);
								}
							}
						}
					}
				}
			}
		}


		return $JourneyPlanCollection;
	}
	public function headings(): array
	{
		return [
			'Name',
			'Description',
			'Start Date',
			'No End Date',
			'End Date',
			'Start Time',
			'End Time',
			'Route',
			'Plan Type',
			'Week One',
			'Week Two',
			'Week Three',
			'Week Four',
			'Week Five',
			'Start day of week',
			'Week Number',
			'Day Name',
			'Day Number',
			'Customer Email',
			'Day Customer Sequence',
			'Day start time',
			'Day end time',
		];
	}
}
