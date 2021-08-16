<?php

namespace App\Exports;

use App\Http\Controllers\Api\JourneyPlanController;
use App\Model\CustomerInfo;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanCustomer;
use App\Model\JourneyPlanDay;
use App\Model\Route;
use App\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class JourneyPlanDetailsExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $StartDate, $EndDate;

    public function __construct(String $StartDate, String $EndDate)
    {
        $this->StartDate         = $StartDate;
        $this->EndDate           = $EndDate;
        $this->JourneyController = app(JourneyPlanController::class);
    }

    public function collection()
    {

        $start_date  = $this->StartDate;
        $end_date    = $this->EndDate;
        $all_salesman = array();
//        $all_salesman = getSalesman(false);

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
            'route_id',
            'merchandiser_id',
        )->with(
            'salesManJourneyPlan',
            'journeyPlanWeeks',
            'merchandiser:id,firstname,lastname',
            'merchandiser.salesmanInfo:id,user_id,salesman_code'
        );

        if (count($all_salesman)) {
            $JourneyPlan->whereIn('merchandiser_id', $all_salesman);
        }

        if ($start_date != '' && $end_date != '') {
            $JourneyPlan = $JourneyPlan->whereBetween('created_at', [$start_date, $end_date]);
        }
        $JourneyPlan = $JourneyPlan->orderBy('id', 'desc')->get();

        $JourneyPlanCollection = new Collection();
        // $JourneyPlanCollection = [];
        if (is_object($JourneyPlan)) {
            foreach ($JourneyPlan as $key => $JourneyPlan1) {

                $customers    = JourneyPlanCustomer::with('customerInfo', 'journeyPlanDay')->where('journey_plan_id', $JourneyPlan1->id)->get();
                $salesManCode = null;
                if ($JourneyPlan1->salesManJourneyPlan != null) {
                    $salesManCode = $JourneyPlan1->salesManJourneyPlan->salesman_code;
                }
                $dayPlanType  = ($JourneyPlan1->plan_type == '1') ? 'Yes' : 'No';
                $weekPlanType = ($JourneyPlan1->plan_type == '2') ? 'Yes' : 'No';
                $getDayName   = $this->JourneyController->getDayNamefromVal($JourneyPlan1->start_day_of_the_week);
                $enForceFlag  = ($JourneyPlan1->is_enforce == 1) ? 'Yes' : 'No';

                $route                    = Route::find($JourneyPlan[$key]->route_id);
                $JourneyPlan[$key]->route = (is_object($route)) ? $route->route_name : '';

                $customers = $customers->groupBy('customer_id')->all();

                $__week_day_get = [];
                $__jouney_days  = [];
                $__week_days    = [];

                for ($i = 1; $i <= 5; $i++) {
                    $get_week_number    = 'week' . $i;
                    $__week_day_get[$i] = $JourneyPlan1->journeyPlanWeeks->where('week_number', $get_week_number)->pluck('week_number', 'id')->toArray();

                    $__jouney_days[$i] = JourneyPlanDay::whereIn('journey_plan_week_id', array_keys($__week_day_get[$i]))->where('journey_plan_id', $JourneyPlan1->id)->get();

                    $__week_days[$i] = $__jouney_days[$i]->pluck('day_number', 'id');
                }

                // $customers    = JourneyPlanCustomer::with('customerInfo', 'journeyPlanDay')->where('journey_plan_id', $JourneyPlan1->id)->get();

                foreach ($customers as $c_key => $customer) {
                    $get_first_customer = $customer->first();
                    $first_week_entry   = $customer->pluck('id', 'journey_plan_day_id');
                    // dd($first_week_entry, $__week_days[1],$get_first_customer);

                    $__days_for_week = [];
                    for ($j = 1; $j <= 5; $j++) {
                        $fetch_days_for_week[$j] = $__week_days[$j]->intersectByKeys($customer->pluck('id', 'journey_plan_day_id'));

                        $__days_for_week[$j] = $fetch_days_for_week[$j]->toArray();
                    }
                    // dd($__days_for_week);

                    $customer = User::find($get_first_customer->customerInfo->user_id);
                    if ($JourneyPlan[$key]->plan_type == 2) {
                        $JourneyPlanCollection->push((object) [
                            'name'              => $JourneyPlan[$key]->name,
                            'description'       => $JourneyPlan[$key]->description,
                            // 'start_date'        => $JourneyPlan[$key]->start_date,
                            // // 'no_end_date' => $JourneyPlan[$key]->no_end_date,
                            // 'end_date'          => $JourneyPlan[$key]->end_date,
                            // 'start_time'        => $JourneyPlan[$key]->start_time,
                            // 'end_time'          => $JourneyPlan[$key]->end_time,
                            // 'day_name' => $planday->day_name,
                            // 'day_number' => $planday->day_number,
                            // 'customer_email' => (is_object($customer)) ? $customer->email : '',
                            'day_wise'          => $dayPlanType,
                            'week_wise'         => $weekPlanType,
                            'first_day_of_week' => $getDayName,
                            'enforce_flag'      => $enForceFlag,
                            'merchandiser'      => $salesManCode,
                            'customer_trnno'    => $get_first_customer->customerInfo->trn_no,
                            'customer_code'     => $get_first_customer->customerInfo->customer_code,
                            'customer'          => ($customer) ? $customer->firstname . ' ' . $customer->lastname : null,

                            'Week1_Sunday'      => (count($__days_for_week[1]) > 0 && in_array(1, $__days_for_week[1])) ? '1' : '0',
                            'Week1_Monday'      => (count($__days_for_week[1]) > 0 && in_array(2, $__days_for_week[1])) ? '1' : '0',
                            'Week1_Tuesday'     => (count($__days_for_week[1]) > 0 && in_array(3, $__days_for_week[1])) ? '1' : '0',
                            'Week1_Wednesday'   => (count($__days_for_week[1]) > 0 && in_array(4, $__days_for_week[1])) ? '1' : '0',
                            'Week1_Thrusday'    => (count($__days_for_week[1]) > 0 && in_array(5, $__days_for_week[1])) ? '1' : '0',
                            'Week1_Friday'      => (count($__days_for_week[1]) > 0 && in_array(6, $__days_for_week[1])) ? '1' : '0',
                            'Week1_Saturday'    => (count($__days_for_week[1]) > 0 && in_array(7, $__days_for_week[1])) ? '1' : '0',
                            // second weeek
                            'Week2_Sunday'      => (count($__days_for_week[2]) > 0 && in_array(1, $__days_for_week[2])) ? '1' : '0',
                            'Week2_Monday'      => (count($__days_for_week[2]) > 0 && in_array(2, $__days_for_week[2])) ? '1' : '0',
                            'Week2_Tuesday'     => (count($__days_for_week[2]) > 0 && in_array(3, $__days_for_week[2])) ? '1' : '0',
                            'Week2_Wednesday'   => (count($__days_for_week[2]) > 0 && in_array(4, $__days_for_week[2])) ? '1' : '0',
                            'Week2_Thrusday'    => (count($__days_for_week[2]) > 0 && in_array(5, $__days_for_week[2])) ? '1' : '0',
                            'Week2_Friday'      => (count($__days_for_week[2]) > 0 && in_array(6, $__days_for_week[2])) ? '1' : '0',
                            'Week2_Saturday'    => (count($__days_for_week[2]) > 0 && in_array(7, $__days_for_week[2])) ? '1' : '0',

                            // third weeek
                            'Week3_Sunday'      => (count($__days_for_week[3]) > 0 && in_array(1, $__days_for_week[3])) ? '1' : '0',
                            'Week3_Monday'      => (count($__days_for_week[3]) > 0 && in_array(3, $__days_for_week[3])) ? '1' : '0',
                            'Week3_Tuesday'     => (count($__days_for_week[3]) > 0 && in_array(3, $__days_for_week[3])) ? '1' : '0',
                            'Week3_Wednesday'   => (count($__days_for_week[3]) > 0 && in_array(4, $__days_for_week[3])) ? '1' : '0',
                            'Week3_Thrusday'    => (count($__days_for_week[3]) > 0 && in_array(5, $__days_for_week[3])) ? '1' : '0',
                            'Week3_Friday'      => (count($__days_for_week[3]) > 0 && in_array(6, $__days_for_week[3])) ? '1' : '0',
                            'Week3_Saturday'    => (count($__days_for_week[3]) > 0 && in_array(7, $__days_for_week[3])) ? '1' : '0',

                            // forth weeek
                            'Week4_Sunday'      => (count($__days_for_week[4]) > 0 && in_array(1, $__days_for_week[4])) ? '1' : '0',
                            'Week4_Monday'      => (count($__days_for_week[4]) > 0 && in_array(4, $__days_for_week[4])) ? '1' : '0',
                            'Week4_Tuesday'     => (count($__days_for_week[4]) > 0 && in_array(4, $__days_for_week[4])) ? '1' : '0',
                            'Week4_Wednesday'   => (count($__days_for_week[4]) > 0 && in_array(4, $__days_for_week[4])) ? '1' : '0',
                            'Week4_Thrusday'    => (count($__days_for_week[4]) > 0 && in_array(5, $__days_for_week[4])) ? '1' : '0',
                            'Week4_Friday'      => (count($__days_for_week[4]) > 0 && in_array(6, $__days_for_week[4])) ? '1' : '0',
                            'Week4_Saturday'    => (count($__days_for_week[4]) > 0 && in_array(7, $__days_for_week[4])) ? '1' : '0',

                            // third weeek
                            'Week5_Sunday'      => (count($__days_for_week[5]) > 0 && in_array(1, $__days_for_week[5])) ? '1' : '0',
                            'Week5_Monday'      => (count($__days_for_week[5]) > 0 && in_array(5, $__days_for_week[5])) ? '1' : '0',
                            'Week5_Tuesday'     => (count($__days_for_week[5]) > 0 && in_array(5, $__days_for_week[5])) ? '1' : '0',
                            'Week5_Wednesday'   => (count($__days_for_week[5]) > 0 && in_array(5, $__days_for_week[5])) ? '1' : '0',
                            'Week5_Thrusday'    => (count($__days_for_week[5]) > 0 && in_array(5, $__days_for_week[5])) ? '1' : '0',
                            'Week5_Friday'      => (count($__days_for_week[5]) > 0 && in_array(6, $__days_for_week[5])) ? '1' : '0',
                            'Week5_Saturday'    => (count($__days_for_week[5]) > 0 && in_array(7, $__days_for_week[5])) ? '1' : '0',
                        ]);
                    } else {
                        $JourneyPlanCollection->push((object) [
                            'name'              => $JourneyPlan[$key]->name,
                            'description'       => $JourneyPlan[$key]->description,
                            'day_wise'          => $dayPlanType,
                            'week_wise'         => $weekPlanType,
                            'first_day_of_week' => $getDayName,
                            'enforce_flag'      => $enForceFlag,
                            'merchandiser'      => $salesManCode,
                            'customer_trnno'    => $get_first_customer->customerInfo->trn_no,
                            'customer_code'     => $get_first_customer->customerInfo->customer_code,
                            'customer'          => ($customer) ? $customer->firstname . ' ' . $customer->lastname : null,
                        ]);
                    }
                }
            }
        }
        // dd($JourneyPlanCollection);
        return $JourneyPlanCollection;
    }

    public function headings(): array
    {
        $final_array = [];
        for ($i = 1; $i <= 5; $i++) {
            $final_arrayd = [
                'Week' . $i . ' Sunday',
                'Week' . $i . ' Monday',
                'Week' . $i . ' Tuesday',
                'Week' . $i . ' Wednesday',
                'Week' . $i . ' Thrusday',
                'Week' . $i . ' Friday',
                'Week' . $i . ' Saturday',
            ];
            $final_array = array_merge($final_array, $final_arrayd);
        }
        $commen_array = [
            'Journey Name',
            'Desc',
            // 'Start Date',
            // 'End Date',
            // 'Start Time',
            // 'End Time',
            'Day Wise',
            'Week Wise',
            'First Day Of Week',
            'Enforce Flag',
            'Merchandiser',
            'Customer TRN No.',
            'Customer Code',
            'Customer',
        ];
        return array_merge($commen_array, $final_array);
    }
}
