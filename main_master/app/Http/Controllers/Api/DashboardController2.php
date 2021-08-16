<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Channel;
use App\Model\CustomerInfo;
use App\Model\CustomerVisit;
use App\Model\Reason;
use App\Model\SalesmanInfo;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController2 extends Controller
{
    public function index(Request $request)
    {
        if ($request->start_date && $request->end_date) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }

        if (!$request->start_date && $request->end_date) {
            $start_date = date('Y-m-d', strtotime('-7 days', strtotime($request->end_date)));
            $end_date = $request->end_date;
        }

        if ($request->start_date && !$request->end_date) {
            $start_date = $request->start_date;
            $end_date = date('Y-m-d', strtotime('+7 days', strtotime($request->start_date)));
        }

        if (!$request->start_date && !$request->end_date) {
            $end_date = date('Y-m-d');
            $start_date = date('Y-m-d', strtotime('-7 days', strtotime($end_date)));
        }

        $coverage = $this->coverage($request, $start_date, $end_date);
        $execution = $this->execution($request, $start_date, $end_date);
        $visitPerDay = $this->visitPerDay($request, $start_date, $end_date);
        $activeOutlets = $this->activeOutlets($request, $start_date, $end_date);
        $strikeRate = $this->strikeRate($request, $start_date, $end_date);
        $visitFrequency = $this->visitFrequency($request, $start_date, $end_date);
        $timeSpent = $this->timeSpent($request, $start_date, $end_date);
        $routeCompliance = $this->routeCompliance($request, $start_date, $end_date);

        $data = array(
            'coverage' => $coverage,
            'execution' => $execution,
            'visitPerDay' => $visitPerDay,
            'activeOutlets' => $activeOutlets,
            'strikeRate' => $strikeRate,
            'visitFrequency' => $visitFrequency,
            'timeSpent' => $timeSpent,
            'routeCompliance' => $routeCompliance
        );

        return prepareResult(true, $data, [], "dashboard listing", $this->success);
    }

    private function routeCompliance($request, $start_date, $end_date)
    {
        $routeCompliance = new \stdClass();
        $routeCompliance->title = "Route Compliance";
        $routeCompliance->text = "Compliance to route plan";
        $routeCompliance->percentage = "88%";
        $routeCompliance->trends = [];
        $routeCompliance->comparison = [];
        $routeCompliance->contribution = [];
        $routeCompliance->details = [];

        return $routeCompliance;
    }

    private function timeSpent($request, $start_date, $end_date)
    {
        $visitFrequency = new \stdClass();
        $visitFrequency->title = "Time Spent";
        $visitFrequency->text = "Avg. time spent per visit";
        $visitFrequency->percentage = "24.2";
        $visitFrequency->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $visitFrequency->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $visitFrequency->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $visitFrequency->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];

        return $visitFrequency;
    }

    private function visitFrequency($request, $start_date, $end_date)
    {
        $visitFrequency = new \stdClass();
        $visitFrequency->title = "Visit Frequency";
        $visitFrequency->text = "Visit frequencey per outlet";
        $visitFrequency->percentage = "0.9";
        $visitFrequency->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $visitFrequency->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $visitFrequency->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $visitFrequency->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];

        return $visitFrequency;
    }

    private function strikeRate($request, $start_date, $end_date)
    {
        $strikeRate = new \stdClass();
        $strikeRate->title = "Strike Rate";
        $strikeRate->text = "Order received vs visit per day";
        $strikeRate->percentage = "54%";
        $strikeRate->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $strikeRate->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $strikeRate->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $strikeRate->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];

        return $strikeRate;
    }

    private function activeOutlets1($request, $start_date, $end_date)
    {
        $activeOutlets = new \stdClass();
        $activeOutlets->title = "Active Outlets";
        $activeOutlets->text = "Where atleast one invoice was made from a visit this month";
        $activeOutlets->percentage = "75%";
        $activeOutlets->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $activeOutlets->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $activeOutlets->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $activeOutlets->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];

        return $activeOutlets;
    }

    private function visitPerDay($request, $start_date, $end_date)
    {
        $visit_per_day = new \stdClass();
        $visit_per_day->title = "Active Outlets";
        $visit_per_day->text = "Where atleast one invoice was made from a visit this month";
        $visit_per_day->percentage = "75%";
        $visit_per_day->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $visit_per_day->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $visit_per_day->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $visit_per_day->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];
        return $visit_per_day;
    }

    private function execution($request, $start_date, $end_date)
    {
        $execution = new \stdClass();
        $execution->title = "Execution";
        $execution->text = "Outlets Incluenced by a sales rep";
        $execution->percentage = "40%";
        $execution->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $execution->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $execution->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $execution->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];
        return $execution;
    }

    private function coverage($request, $start_date, $end_date)
    {
        $trends_data = [];
        $comparison = [];
        $listing = array();
        // start here
        $customer_visit_query = CustomerVisit::select('id', 'route_id', 'trip_id', 'customer_id', 'salesman_id', 'journey_plan_id', 'latitude', 'longitude', 'shop_status', 'start_time', 'end_time', 'is_sequnece', 'date', 'reason', 'added_on')
            ->with(
                'customer:id,firstname,lastname',
                'salesman:id,firstname,lastname',
                'route:id,route_name',
                'journeyPlan:id,name',
                'customerActivity:id,customer_visit_id,customer_id,activity_name,activity_action,start_time,end_time'
            )
            ->where('shop_status', '!=', 'close')
            ->whereNotNull('reason')
            ->whereBetween('added_on', [$start_date, $end_date]);

        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            $all_customer = DB::table('customer_infos')->select('customer_infos.id', 'customer_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->whereIn('customer_infos.channel_id', $request->channel_ids)
                ->where('customer_infos.current_stage', 'Approved')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_infos.organisation_id', $request->user()->organisation_id)
                ->groupBy('customer_infos.user_id')
                ->get();

            $comparison = DB::table('customer_infos')
                ->select('channels.name', DB::raw('count(customer_visits.id) as steps'))
                ->join('channels', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->whereIn('customer_infos.channel_id', $request->channel_ids)
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->groupBy('channels.id')
                ->get();

            $customer_ids = $all_customer->pluck('user_id')->toArray();

            $customer_visit_query->whereIn('customer_id', $customer_ids);

            //Details
            $customer_details = DB::table('channels')
                ->select(
                    DB::raw('DISTINCT name as RES'),
                    DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                    DB::raw('(COUNT(DISTINCT customer_visits.id) / COUNT(DISTINCT
                customer_infos.id
            )) * 100 AS EXECUTION')
                )
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->groupBy('channels.id')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->where('channels.organisation_id', $request->user()->organisation_id)
                ->get();

            $listing = DB::table('trips')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as visit_date,
                salesman.firstname AS salesmanName,
                customerInfo.firstname AS customerName,
                customer_categories.customer_category_name AS category,
                channels.name AS channelName,
                customer_visits.total_task AS total_tasks_planned,
                SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                COUNT(DISTINCT customer_visits.id) as total_visits
                ')
                )
                ->join('users as salesman', 'salesman.id', '=', 'trips.salesman_id')
                ->join('customer_visits', 'customer_visits.trip_id', '=', 'trips.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_visits.customer_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customerInfo.id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('channels.id', $request->channel_ids)
                ->where('trips.organisation_id', $request->user()->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('customerName')
                ->get();
        } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
            $salesman_info = SalesmanInfo::whereIn('salesman_supervisor', $request->supervisor)
                ->where('current_stage', 'Approved')
                ->get();

            $all_customer = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
                ->with('user:id,firstname,lastname')
                ->whereIn('merchandiser_id', $salesman_info->pluck('user_id')->toArray())
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })
                ->get();

            $comparison = DB::table('customer_infos')
                ->select('users.firstname as name', DB::raw('count(customer_visits.id) as steps'))
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->groupBy('customer_infos.merchandiser_id')
                ->get();

            $customer_visit_query->whereIn('customer_id', $all_customer->pluck('user_id')->toArray());
        } elseif (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
            $salesman_info = SalesmanInfo::select('id', 'user_id', 'current_stage')
                ->whereIn('user_id', $request->salesman_ids)
                ->where('current_stage', 'Approved')
                ->get();

            $all_customer = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
                ->with('user:id,firstname,lastname')
                ->whereIn('merchandiser_id', $salesman_info->pluck('user_id')->toArray())
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })
                ->get();

            $comparison = DB::table('customer_infos')
                ->select('users.firstname as name', DB::raw('count(customer_visits.id) as steps'))
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->groupBy('customer_infos.merchandiser_id')
                ->get();

            $customer_visit_query->whereIn('customer_id', $all_customer->pluck('user_id')->toArray());

            //Details

            $customer_details = DB::table('salesman_infos')
                ->select(
                    DB::raw('DISTINCT users.firstname as RES'),
                    DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) / COUNT(DISTINCT
                customer_infos.id
            ) * 100 AS EXECUTION')
                )
                ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'users.id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->where('users.organisation_id', $request->user()->organisation_id)
                ->groupBy('users.id')
                ->get();

            $listing = DB::table('trips')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as visit_date,
                salesman.firstname AS salesmanName,
                customerInfo.firstname AS customerName,
                customer_categories.customer_category_name AS category,
                customer_visits.total_task AS total_tasks_planned,
                SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                COUNT(DISTINCT customer_visits.id) as total_visits
                ')
                )
                ->join('users as salesman', 'salesman.id', '=', 'trips.salesman_id')
                ->join('customer_visits', 'customer_visits.trip_id', '=', 'trips.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_visits.customer_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customerInfo.id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('salesman.id', $request->salesman_ids)
                ->where('trips.organisation_id', $request->user()->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('customerName')
                ->get();
        } elseif (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {

            $comparison = DB::table('customer_infos')
                ->select('regions.region_name as name', DB::raw('count(customer_visits.id) as steps'))
                ->join('regions', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('customer_infos.region_id', $request->region_ids)
                ->groupBy('customer_infos.user_id')
                ->get();

            $all_customer = CustomerInfo::whereIn('region_id', $request->region_ids)
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })->get();

            $customer_visit_query->whereIn('customer_id', $all_customer->pluck('user_id')->toArray());

            //Details
            $customer_details = DB::table('regions')
                ->select(
                    DB::raw('DISTINCT region_name as RES'),
                    DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) / COUNT(DISTINCT
                customer_infos.id
            ) * 100 AS EXECUTION')
                )
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->groupBy('regions.id')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->where('regions.organisation_id', $request->user()->organisation_id)
                ->get();

            $listing = DB::table('trips')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as visit_date,
                salesman.firstname AS salesmanName,
                customerInfo.firstname AS customerName,
                customer_categories.customer_category_name AS category,
                regions.region_name AS regionName,
                customer_visits.total_task AS total_tasks_planned,
                SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                COUNT(DISTINCT customer_visits.id) as total_visits
                ')
                )
                ->join('users as salesman', 'salesman.id', '=', 'trips.salesman_id')
                ->join('customer_visits', 'customer_visits.trip_id', '=', 'trips.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_visits.customer_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customerInfo.id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('regions.id', $request->region_ids)
                ->where('trips.organisation_id', $request->user()->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('customerName')
                ->get();
        } else {

            $salesman_info = SalesmanInfo::select('id', 'user_id', 'current_stage')
                ->where('current_stage', 'Approved')
                ->get();

            $all_customer = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
                ->with('user:id,firstname,lastname')
                ->whereIn('merchandiser_id', $salesman_info->pluck('user_id')->toArray())
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date])
                        ->where('customer_visits.shop_status', '!=', 'close')
                        ->whereNotNull('customer_visits.reason');
                })
                ->get();

            $comparison = DB::table('customer_infos')
                ->select('users.firstname as name', DB::raw('count(customer_visits.id) as steps'))
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->groupBy('customer_infos.merchandiser_id')
                ->get();

            $customer_visit_query->whereIn('customer_id', $all_customer->pluck('user_id')->toArray());

            $customer_details = DB::table('salesman_infos')
                ->select(
                    DB::raw('DISTINCT users.firstname as RES'),
                    DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) / COUNT(DISTINCT
                customer_infos.id
            ) * 100 AS EXECUTION')
                )
                ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'users.id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customer->pluck('user_id')->toArray())
                ->where('users.organisation_id', $request->user()->organisation_id)
                ->groupBy('users.id')
                ->get();

            $listing = DB::table('trips')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as visit_date,
                salesman.firstname AS salesmanName,
                customerInfo.firstname AS customerName,
                customer_categories.customer_category_name AS category,
                customer_visits.total_task AS total_tasks_planned,
                SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                COUNT(DISTINCT customer_visits.id) as total_visits
                ')
                )
                ->join('users as salesman', 'salesman.id', '=', 'trips.salesman_id')
                ->join('customer_visits', 'customer_visits.trip_id', '=', 'trips.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_visits.customer_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customerInfo.id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', '!=', 'close')
                ->whereNotNull('customer_visits.reason')
                ->whereIn('salesman.id', $salesman_info->pluck('user_id')->toArray())
                ->where('trips.organisation_id', $request->user()->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('customerName')
                ->get();
        }

        $customer_visit = $customer_visit_query->get();

        //end here
        if (count($customer_visit) != 0 && count($all_customer) != 0) {
            $coverage_count = round(count($customer_visit) / count($all_customer), 2) * 100;
        } else {
            $coverage_count = 0;
        }

        $coverage = new \stdClass();
        $coverage->title = "Coverage";
        $coverage->text = "Outlets Visited atleast once this month vs all outlet in the market";
        $coverage->percentage = $coverage_count . "%";

        if ($customer_visit->count()) {
            foreach ($customer_visit as $customerVisit) {
                $trends_data[] = array(
                    "date" => strtotime($customerVisit->added_on),
                    "date2" => $customerVisit->added_on,
                    "value" => count($customerVisit->customerActivity)
                );
            }
        }


        $coverage->trends = $trends_data;
        $coverage->comparison = $comparison;
        $coverage->contribution = $comparison;
        $coverage->details = $customer_details;
        $coverage->listing = $listing;

        return $coverage;
    }

    private function activeOutlets($request, $start_date, $end_date)
    {
        $trends_data = [];
        $comparison = [];
        $listingData = array();
        // start here
        $customer_visit_query = DB::table('customer_infos')->select(DB::raw('COUNT(DISTINCT customer_infos.id) as acitve_outlets'))
            ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
            ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
            ->where('customer_visits.shop_status', '!=', 'close')
            ->whereNotNull('customer_visits.reason')
            ->whereRaw(DB::raw("DATE(customer_visits.added_on) BETWEEN '$start_date' AND'$end_date'"))
            ->where('customer_infos.organisation_id', $request->user()->organisation_id);
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            $all_customer = DB::table('customer_infos')->select('customer_infos.id', 'customer_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->whereIn('customer_infos.channel_id', $request->channel_ids)
                ->where('customer_infos.current_stage', 'Approved')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_infos.organisation_id', $request->user()->organisation_id)
                ->groupBy('customer_infos.user_id')
                ->get();

            $customer_ids = $all_customer->pluck('user_id')->toArray();

            $customer_visit_query->whereIn('customer_infos.channel_id', $request->channel_ids);
            $customer_visit_query->whereIn('orders.customer_id', $customer_ids);


        } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
            $salesman_info = SalesmanInfo::whereIn('salesman_supervisor', $request->supervisor)
                ->where('current_stage', 'Approved')
                ->get();

            $all_customer = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
                ->with('user:id,firstname,lastname')
                ->whereIn('merchandiser_id', $salesman_info->pluck('user_id')->toArray())
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })
                ->get();

            $customer_visit_query->whereIn('orders.customer_id', $all_customer->pluck('user_id')->toArray());

        } elseif (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
            $salesman_info = SalesmanInfo::select('id', 'user_id', 'current_stage')
                ->whereIn('user_id', $request->salesman_ids)
                ->where('current_stage', 'Approved')
                ->get();

            $all_customer = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
                ->with('user:id,firstname,lastname')
                ->whereIn('merchandiser_id', $salesman_info->pluck('user_id')->toArray())
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })
                ->get();



            $customer_visit_query->whereIn('orders.customer_id', $all_customer->pluck('user_id')->toArray());

        } elseif (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {

            $all_customer = CustomerInfo::whereIn('region_id', $request->region_ids)
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })->get();

            $customer_visit_query->whereIn('orders.customer_id', $all_customer->pluck('user_id')->toArray());


        } else {
            $salesman_info = SalesmanInfo::select('id', 'user_id', 'current_stage')
                ->where('current_stage', 'Approved')
                ->get();

            $all_customer = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
                ->with('user:id,firstname,lastname')
                ->whereIn('merchandiser_id', $salesman_info->pluck('user_id')->toArray())
                ->whereHas('customerVisit', function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('added_on', [$start_date, $end_date]);
                })
                ->get();

//            pre($all_customer);


            $customer_visit_query->whereIn('orders.customer_id', $all_customer->pluck('user_id')->toArray());


        }

        $customer_visit = $customer_visit_query->first();
        //end here
        if ($customer_visit->acitve_outlets != 0 && count($all_customer) != 0) {
            $activeOutlets_count = round($customer_visit->acitve_outlets / count($all_customer), 2) * 100;
        } else {
            $activeOutlets_count = 0;
        }

        $activeOutlets = new \stdClass();
        $activeOutlets->title = "Active Outlets";
        $activeOutlets->text = "Where atleast one invoice was made from a visit this month";
        $activeOutlets->percentage = $activeOutlets_count."%";
        $activeOutlets->trends = [
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 16
            ),
            array(
                "date" => 1606121850690,
                "value" => 17
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 18
            ),
            array(
                "date" => 1606121850690,
                "value" => 19
            ),
            array(
                "date" => 1606121850690,
                "value" => 20
            ),
            array(
                "date" => 1606121850690,
                "value" => 121
            )
        ];
        $activeOutlets->comparison = [
            array(
                "name" => "Monica",
                "steps" => 45688
            ),
            array(
                "name" => "Joey",
                "steps" => 5454
            ),
            array(
                "name" => "Ross",
                "steps" => 4545
            ),
            array(
                "name" => "Adam",
                "steps" => 4898
            ),
            array(
                "name" => "Ali",
                "steps" => 42588
            )
        ];
        $activeOutlets->contribution = [
            array(
                "country" => "USA",
                "visits" => 23725
            ),
            array(
                "country" => "Germany",
                "visits" => 54685
            ),
            array(
                "country" => "Japan",
                "visits" => 44568
            ),
            array(
                "country" => "UK",
                "visits" => 12345
            )
        ];
        $activeOutlets->details = [
            array(
                "RES" => "C-Store",
                "VISITS" => "1",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Hypermarket",
                "VISITS" => "3",
                "TOTAL OUTLETS" => "8",
                "EXECUTION" => "67%"
            ),
            array(
                "RES" => "Discounter",
                "VISITS" => "4",
                "TOTAL OUTLETS" => "7",
                "EXECUTION" => "13%"
            ),
            array(
                "RES" => "Kiosks",
                "VISITS" => "5",
                "TOTAL OUTLETS" => "6",
                "EXECUTION" => "50%"
            )
        ];

        return $activeOutlets;
    }
}
