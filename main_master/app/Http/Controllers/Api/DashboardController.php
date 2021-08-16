<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Channel;
use App\Model\CustomerInfo;
use App\Model\CustomerMerchandizer;
use App\Model\CustomerVisit;
use App\Model\Order;
use App\Model\Reason;
use App\Model\Region;
use App\Model\SalesmanInfo;
use App\Model\Trip;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanDay;
use App\Model\JourneyPlanCustomer;
use App\Model\JourneyPlanWeek;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;
use DateTime;

class DashboardController extends Controller
{
    private $organisation_id;

    public function index(Request $request)
    {
        if ($request->start_date && $request->end_date) {
            $start_date = $request->start_date;
            $end_date = date('Y-m-d', strtotime('+1 days', strtotime($request->end_date)));
            // $end_date = $request->end_date;

        }

        if (!$request->start_date && $request->end_date) {
            $end_date = date('Y-m-d', strtotime('+1 days', strtotime($request->end_date)));
            $start_date = date('Y-m-d', strtotime('-7 days', strtotime($end_date)));
            // $end_date = $end_date;

        }

        if ($request->start_date && !$request->end_date) {
            $start_date = $request->start_date;
            $end_date = date('Y-m-d', strtotime('+1 days', strtotime($request->end_date)));
            $end_date = date('Y-m-d', strtotime('+7 days', strtotime($end_date)));
        }

        if (!$request->start_date && !$request->end_date) {
            $end_date = date('Y-m-d', strtotime('+1 days', strtotime(date('Y-m-d'))));
            $start_date = date('Y-m-d', strtotime('-7 days', strtotime($end_date)));
        }

        $this->organisation_id = $request->user()->organisation_id;

        $coverage = $this->coverage($request, $start_date, $end_date);
        $execution = $this->execution($request, $start_date, $end_date);
        // $activeOutlets = $this->activeOutlets($request, $start_date, $end_date);
        // $visitPerDay = $this->visitPerDay($request, $start_date, $end_date);
        // $visitFrequency = $this->visitFrequency($request, $start_date, $end_date);
        // $timeSpent = $this->timeSpent($request, $start_date, $end_date);
        // $routeCompliance = $this->routeCompliance($request, $start_date, $end_date);
        // $strikeRate = $this->strikeRate($request, $start_date, $end_date);

        $data = array(
            'coverage' => $coverage,
            'execution' => $execution,
            // 'activeOutlets' => $activeOutlets,
            // 'visitPerDay' => $visitPerDay,
            // 'visitFrequency' => $visitFrequency,
            // 'timeSpent' => $timeSpent,
            // 'strikeRate' => $strikeRate,
            // 'routeCompliance' => $routeCompliance
        );

        return prepareResult(true, $data, [], "dashboard listing", $this->success);
    }

    private function execution($request, $start_date, $end_date)
    {
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            $completedTasks = array();
            $totalTasks = array();

            foreach ($request->channel_ids as $channel) {
                $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')
                    ->where('channel_id', $channel)
                    ->get();

                $customer_ids = array();
                if (count($customer_infos)) {
                    $customer_ids = $customer_infos->pluck('user_id')->toArray();
                }

                $customer_visits = DB::table('customer_visits')
                    ->select(
                        DB::raw('SUM(customer_visits.total_task) as totalTask'),
                        DB::raw('SUM(customer_visits.completed_task) as completedTask')
                    )
                    ->where('shop_status', 'open')
                    ->where('completed_task', '!=', 0)
                    ->whereNull('reason')
                    ->whereBetween('date', [$start_date, $end_date])
                    ->whereIn('customer_id', $customer_ids)
                    ->where('organisation_id', $this->organisation_id)
                    ->groupBy('customer_visits.salesman_id')
                    ->get();

                $completedTask = 0;
                $totalTask = 0;

                if (count($customer_visits)) {
                    $completedTask = $customer_visits->pluck('completedTask')->toArray();
                    $completedTask = array_sum($completedTask);

                    $totalTask = $customer_visits->pluck('totalTask')->toArray();
                    $totalTask = array_sum($totalTask);
                }

                if (count($customer_visits)) {
                    $completedTask = $customer_visits->pluck('completedTask')->toArray();
                    $totalTask = $customer_visits->pluck('totalTask')->toArray();
                    $completedTask = array_sum($completedTask);
                    $totalTask = array_sum($totalTask);
                }

                // if (isset($customer_visits[0]->completedTask)) {
                //     $completedTask = $customer_visits[0]->completedTask;
                // }

                // if (isset($customer_visits[0]->totalTask)) {
                //     $totalTask = $customer_visits[0]->totalTask;
                // }

                $completedTasks[] = $completedTask;
                $totalTasks[] = $totalTask;

                $channel_user = Channel::find($channel);

                $comparison[] = array(
                    'name' => $channel_user->name,
                    'steps' => $completedTask
                );

                $EXECUTION = 0;
                if ($completedTask != 0 && $totalTask != 0) {
                    $EXECUTION = round(($completedTask / $totalTask) * 100, 2);
                }

                $customer_details[] = array(
                    'RES' => $channel_user->name,
                    'TOTAL_OUTLETS' => $totalTask,
                    'VISITS' => $completedTask,
                    'EXECUTION' => $EXECUTION
                );
            }

            // $salesman_ids = array();
            // if (count($salesman_infos)) {
            //     $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
            // }
            //
            // if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
            //     $count_ss = count($request->supervisor);
            // } else {
            //     $count_ss = count($salesman_ids);
            // }

            $percentage = 0;
            if (array_sum($completedTasks) != 0 && array_sum($totalTasks) != 0) {
                $percentage = round((array_sum($completedTasks) / array_sum($totalTasks)) * 100, 2);
            }

            $customerInfos = CustomerInfo::select('id', 'user_id', 'channel_id')
                ->whereIn('channel_id', $request->channel_ids)
                ->get();

            $customer_ids = array();
            if (count($customerInfos)) {
                $customer_ids = $customerInfos->pluck('user_id')->toArray();
            }

            $trends_data = DB::table('channels')->select('customer_visits.added_on as date', DB::raw('SUM(customer_visits.completed_task) as value'))
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $listing = DB::table('channels')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_categories.customer_category_name AS category,
                salesman.firstname AS merchandiser,
                salesman_infos.salesman_supervisor AS supervisor,
                customer_infos.customer_code AS customerCode,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.start_time AS startTime,
                customer_visits.end_time AS endTime,
                customer_visits.total_task as totalTask,
                customer_visits.completed_task as completedTask,
                customer_visits.visit_total_time as timeSpent,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'merchandisers.merchandizer_id')
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                // ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.customer_id')
                // ->groupBy('customer_visits.date')
                ->get();
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
            $completedTasks = array();
            $totalTasks = array();

            foreach ($request->region_ids as $region) {

                $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                    ->where('region_id', $region)
                    ->get();

                $customer_ids = array();
                if (count($customer_infos)) {
                    $customer_ids = $customer_infos->pluck('user_id')->toArray();
                }

                $customer_visits = DB::table('customer_visits')
                    ->select(
                        DB::raw('SUM(customer_visits.total_task) as totalTask'),
                        DB::raw('SUM(customer_visits.completed_task) as completedTask')
                    )
                    ->where('shop_status', 'open')
                    ->where('completed_task', '!=', 0)
                    ->whereNull('reason')
                    ->whereBetween('date', [$start_date, $end_date])
                    ->whereIn('customer_id', $customer_ids)
                    ->where('organisation_id', $this->organisation_id)
                    ->groupBy('customer_visits.customer_id')
                    ->get();

                $completedTask = 0;
                $totalTask = 0;

                if (isset($customer_visits[0])) {
                    $completedTask = $customer_visits[0]->completedTask;
                    $totalTask = $customer_visits[0]->totalTask;
                }

                $completedTasks[] = $completedTask;
                $totalTasks[] = $totalTask;

                $region_user = Region::find($region);

                $comparison[] = array(
                    'name' => $region_user->region_name,
                    'steps' => $completedTask
                );

                $EXECUTIONS = 0;

                if ($completedTask != 0 && $totalTask != 0) {
                    $EXECUTIONS = round(($completedTask / $totalTask) * 100, 2);
                }

                $customer_details[] = array(
                    'RES' => $region_user->region_name,
                    'TOTAL_OUTLETS' => $totalTask,
                    'VISITS' => $completedTask,
                    'EXECUTION' => $EXECUTIONS
                );
            }

            $percentage = 0;
            if (array_sum($completedTasks) != 0 && array_sum($totalTasks) != 0) {
                $percentage = round((array_sum($completedTasks) / array_sum($totalTasks)) * 100, 2);
            }

            $customerInfos = CustomerInfo::select('id', 'user_id', 'region_id')
                ->whereIn('region_id', $request->region_ids)
                ->get();

            $customer_ids = array();
            if (count($customerInfos)) {
                $customer_ids = $customerInfos->pluck('user_id')->toArray();
            }

            $trends_data = DB::table('regions')->select('customer_visits.added_on as date', DB::raw('SUM(customer_visits.completed_task) as value'))
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $listing = DB::table('regions')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_categories.customer_category_name AS category,
                salesman.firstname AS merchandiser,
                customer_infos.customer_code AS customerCode,
                salesman_infos.salesman_supervisor AS supervisor,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.start_time AS startTime,
                customer_visits.end_time AS endTime,
                customer_visits.total_task as totalTask,
                customer_visits.completed_task as completedTask,
                customer_visits.visit_total_time as timeSpent,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'merchandisers.merchandizer_id')
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                // ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.customer_id')
                // ->groupBy('customer_visits.date')
                ->get();
        } else {
            if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->whereIn('user_id', $request->salesman_ids)
                    ->get();
            } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $supervisor = $request->supervisor;
                $salesman_infos = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor')
                    ->where(function ($query) use ($supervisor) {
                        if (!empty($supervisor)) {
                            foreach ($supervisor as $key => $filter_val) {
                                if ($key == 0) {
                                    $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                } else {
                                    $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                }
                            }
                        }
                    })
                    ->groupBy('salesman_supervisor')
                    ->get();
            } else {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->get();
            }

            $completedTasks = array();
            $totalTasks = array();
            foreach ($salesman_infos as $salesman) {
                $customer_visits = DB::table('customer_visits')
                    ->select(
                        DB::raw('SUM(customer_visits.total_task) as totalTask'),
                        DB::raw('SUM(customer_visits.completed_task) as completedTask')
                    )
                    ->where('shop_status', 'open')
                    ->where('completed_task', '!=', 0)
                    ->whereNull('reason')
                    ->whereBetween('date', [$start_date, $end_date])
                    ->where('salesman_id', $salesman->user_id)
                    ->where('organisation_id', $this->organisation_id)
                    ->groupBy('customer_visits.salesman_id')
                    ->get();

                $completedTask = 0;
                if (isset($customer_visits[0]) && $customer_visits[0]) {
                    $completedTask = $customer_visits[0]->completedTask;
                }

                $totalTask = 0;
                if (isset($customer_visits[0]) && $customer_visits[0]) {
                    $totalTask = $customer_visits[0]->totalTask;
                }


                $completedTasks[] = $completedTask;
                $totalTasks[] = $totalTask;

                $salesman_user = User::find($salesman->user_id);

                if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                    $comparison[] = array(
                        'name' => $salesman_user->salesmanInfo->salesman_supervisor,
                        'steps' => $completedTask
                    );
                } else {
                    $comparison[] = array(
                        'name' => $salesman_user->firstname,
                        'steps' => $completedTask
                    );
                }

                if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                    $customer_details[] = array(
                        'RES' => $salesman_user->salesmanInfo->salesman_supervisor,
                        'TOTAL_OUTLETS' => $totalTask,
                        'VISITS' => $completedTask,
                        'EXECUTION' => (!empty($completedTask)  ? round(($completedTask / $totalTask) * 100, 2) : 0)
                    );
                } else {
                    $customer_details[] = array(
                        'RES' => $salesman_user->firstname,
                        'TOTAL_OUTLETS' => $totalTask,
                        'VISITS' => $completedTask,
                        'EXECUTION' => (!empty($completedTask)  ? round(($completedTask / $totalTask) * 100, 2) : 0)
                    );
                }
            }

            $salesman_ids = array();
            if (count($salesman_infos)) {
                $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
            }

            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $count_ss = count($request->supervisor);
            } else {
                $count_ss = count($salesman_ids);
            }

            $percentage = 0;

            if (array_sum($completedTasks) != 0 && array_sum($totalTasks) != 0) {
                $percentage = round((array_sum($completedTasks) / array_sum($totalTasks)) * 100, 2);
            }

            $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->whereIn('merchandizer_id', $salesman_ids)
                ->get();

            $customerInfos = array();

            if (count($customer_merchandizers)) {
                $customer_ids = $customer_merchandizers->pluck('user_id')->toArray();
                $customerInfos = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $customer_ids)
                    ->get();
            }

            $customer_ids = array();
            if (count($customerInfos)) {
                $customer_ids = $customerInfos->pluck('user_id')->toArray();
            }

            $trends_data = DB::table('salesman_infos')->select(
                'customer_visits.added_on as date',
                DB::raw('SUM(customer_visits.completed_task) as value')
            )
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                // ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $listing = DB::table('salesman_infos')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_categories.customer_category_name AS category,
                salesman.firstname AS merchandiser,
                customer_infos.customer_code AS customerCode,
                salesman_infos.salesman_supervisor AS supervisor,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.start_time AS startTime,
                customer_visits.end_time AS endTime,
                customer_visits.total_task as totalTask,
                customer_visits.completed_task as completedTask,
                customer_visits.visit_total_time as timeSpent,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'merchandisers.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customerInfo.id')
                // ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.customer_id')
                // ->groupBy('customer_visits.date')
                ->get();
        }

        $visit_per_day = new \stdClass();
        $visit_per_day->title = "Execution";
        $visit_per_day->text = "Outlets Incluenced by a sales rep";
        $visit_per_day->percentage = $percentage;
        $visit_per_day->trends = $trends_data;
        $visit_per_day->comparison = $comparison;
        $visit_per_day->contribution = $comparison;
        $visit_per_day->details = $customer_details;
        $visit_per_day->listing = $listing;
        return $visit_per_day;
    }

    private function timeSpent($request, $start_date, $end_date)
    {
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            $totalTimeSpends = array();
            $salesman_visits = array();

            foreach ($request->channel_ids as $channel) {

                $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                    ->where('channel_id', $channel)
                    ->get();

                $customer_ids = array();
                if (count($customer_infos)) {
                    $customer_ids = $customer_infos->pluck('user_id')->toArray();
                }

                $customer_visits = DB::table('customer_visits')
                    ->select(DB::raw("COUNT(DISTINCT customer_visits.id) as visit"), DB::raw("SUM(TIME_TO_SEC(visit_total_time)) as sec"))
                    ->where('shop_status', 'open')
                    ->where('completed_task', '!=', 0)
                    ->whereNull('reason')
                    ->whereBetween('date', [$start_date, $end_date])
                    ->whereIn('customer_id', $customer_ids)
                    ->where('organisation_id', $this->organisation_id)
                    ->groupBy('customer_visits.customer_id')
                    ->get();

                $diff = date_diff(date_create($start_date), date_create($end_date));
                $date_diff = $diff->format("%a");

                $total_visit = 0;
                $total_sec = 0;

                if (count($customer_visits)) {
                    $total_visit = $customer_visits->pluck('visit')->toArray();
                    $total_visit = array_sum($total_visit);

                    $total_sec = $customer_visits->pluck('sec')->toArray();
                    $total_sec = array_sum($total_sec);
                }

                $salesman_time_spend = 0;
                if (count($customer_ids) && isset($total_sec)) {
                    $t = count($customer_ids) * $date_diff;
                    $salesman_time_spend = round($total_sec) / $t;
                }

                $salesman_visit = 0;
                if (isset($total_visit)) {
                    $salesman_visit = $total_visit;
                }

                $totalTimeSpend = round($salesman_time_spend);
                $totalTimeSpends[] = $totalTimeSpend;
                $salesman_visits[] = $salesman_visit;

                $channel_user = Channel::find($channel);

                $comparison[] = array(
                    'name' => $channel_user->name,
                    'steps' => getHours($totalTimeSpend)
                );

                $customer_details[] = array(
                    'RES' => $channel_user->name,
                    'TOTAL_OUTLETS' => count($customer_ids),
                    'VISITS' => getHours(round($total_sec)),
                    'EXECUTION' => getHours($totalTimeSpend)
                );
            }

            $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')
                ->whereIn('channel_id', $request->channel_ids)
                ->get();

            $customer_ids = array();
            if (count($customer_infos)) {
                $customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $percentage = 0;
            if (count($totalTimeSpends) != 0 && count($customer_ids) != 0) {
                $percentage_data = round(array_sum($totalTimeSpends) / count($customer_ids));
                $percentage = getHours($percentage_data);
            }

            $trends_data = DB::table('channels')->select('customer_visits.added_on as date', DB::raw("ROUND(SUM(TIME_TO_SEC(visit_total_time)) / 60) as value"))
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $listing = DB::table('channels')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_categories.customer_category_name AS category,
                customer_infos.customer_code AS customerCode,
                salesman.firstname AS merchandiser,
                salesman_infos.salesman_supervisor AS supervisor,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.is_sequnece AS sequence,
                customer_visits.start_time AS startTime,
                customer_visits.end_time AS endTime,
                customer_visits.total_task as totalTask,
                customer_visits.completed_task as completedTask,
                customer_visits.visit_total_time as timeSpent,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customerInfo.id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'merchandisers.merchandizer_id')
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                // ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.customer_id')
                // ->groupBy('customer_visits.date')
                ->get();
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
            $totalTimeSpends = array();
            $salesman_visits = array();

            foreach ($request->region_ids as $region) {

                $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                    ->where('region_id', $region)
                    ->get();

                $customer_ids = array();
                if (count($customer_infos)) {
                    $customer_ids = $customer_infos->pluck('user_id')->toArray();
                }

                $customer_visits = DB::table('customer_visits')
                    ->select(DB::raw("COUNT(DISTINCT customer_visits.id) as visit"), DB::raw("SUM(TIME_TO_SEC(visit_total_time)) as sec"))
                    ->where('shop_status', 'open')
                    ->where('completed_task', '!=', 0)
                    ->whereNull('reason')
                    ->whereBetween('date', [$start_date, $end_date])
                    ->whereIn('customer_id', $customer_ids)
                    ->where('organisation_id', $this->organisation_id)
                    ->groupBy('customer_visits.customer_id')
                    ->get();

                $diff = date_diff(date_create($start_date), date_create($end_date));
                $date_diff = $diff->format("%a");

                $total_visit = 0;
                $total_sec = 0;

                if (count($customer_visits)) {
                    $total_visit = $customer_visits->pluck('visit')->toArray();
                    $total_visit = array_sum($total_visit);

                    $total_sec = $customer_visits->pluck('sec')->toArray();
                    $total_sec = array_sum($total_sec);
                }

                $salesman_time_spend = 0;
                if (count($customer_ids) && isset($total_sec)) {
                    $t = count($customer_ids) * $date_diff;
                    $salesman_time_spend = round($total_sec) / $t;
                }

                $salesman_visit = 0;
                if (isset($total_visit)) {
                    $salesman_visit = $total_visit;
                }

                $totalTimeSpend = round($salesman_time_spend);
                $totalTimeSpends[] = $totalTimeSpend;
                $salesman_visits[] = $salesman_visit;

                $region_user = Region::find($region);

                $comparison[] = array(
                    'name' => $region_user->region_name,
                    'steps' => getHours($totalTimeSpend)
                );

                $customer_details[] = array(
                    'RES' => $region_user->region_name,
                    'TOTAL_OUTLETS' => count($customer_ids),
                    'VISITS' => getHours(round($total_sec)),
                    'EXECUTION' => getHours($totalTimeSpend)
                );
            }

            $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                ->whereIn('region_id', $request->region_ids)
                ->get();

            $customer_ids = array();
            if (count($customer_infos)) {
                $customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $percentage = 0;
            if (count($totalTimeSpends) != 0 && count($customer_ids) != 0) {
                $percentage_data = round(array_sum($totalTimeSpends) / count($customer_ids));
                $percentage = getHours($percentage_data);
            }

            $trends_data = DB::table('regions')->select('customer_visits.added_on as date', DB::raw("ROUND(SUM(TIME_TO_SEC(visit_total_time)) / 60) as value"))
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $listing = DB::table('regions')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_categories.customer_category_name AS category,
                salesman.firstname AS merchandiser,
                customer_infos.customer_code AS customerCode,
                salesman_infos.salesman_supervisor AS supervisor,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.is_sequnece AS sequence,
                customer_visits.start_time AS startTime,
                customer_visits.end_time AS endTime,
                customer_visits.total_task as totalTask,
                customer_visits.completed_task as completedTask,
                customer_visits.visit_total_time as timeSpent,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customerInfo.id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'merchandisers.merchandizer_id')
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                // ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                ->get();
        } else {
            if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->whereIn('user_id', $request->salesman_ids)
                    ->get();
            } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $supervisor = $request->supervisor;
                $salesman_infos = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor')
                    ->where(function ($query) use ($supervisor) {
                        if (!empty($supervisor)) {
                            foreach ($supervisor as $key => $filter_val) {
                                if ($key == 0) {
                                    $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                } else {
                                    $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                }
                            }
                        }
                    })
                    ->groupBy('salesman_supervisor')
                    ->get();
            } else {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->get();
            }

            $totalTimeSpends = array();
            $salesman_visits = array();

            foreach ($salesman_infos as $salesman) {

                $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                    ->where('merchandizer_id', $salesman->user_id)
                    ->get();

                $customer_info = array();
                if (count($customer_merchandizers)) {
                    $customer_ids = $customer_merchandizers->pluck('user_id')->toArray();

                    $customer_info = CustomerInfo::select('id', 'user_id')
                        ->where('user_id', $customer_ids)
                        ->get();
                }

                $customer_ids = array();
                if (count($customer_info)) {
                    $customer_ids = $customer_info->pluck('user_id')->toArray();
                }

                $customer_visits = DB::table('customer_visits')
                    ->select(DB::raw("COUNT(DISTINCT customer_visits.id) as visit"), DB::raw("SUM(TIME_TO_SEC(visit_total_time)) as sec"))
                    ->where('shop_status', 'open')
                    ->where('completed_task', '!=', 0)
                    ->whereNull('reason')
                    ->whereBetween('date', [$start_date, $end_date])
                    ->where('salesman_id', $salesman->user_id)
                    ->where('organisation_id', $this->organisation_id)
                    ->groupBy('customer_visits.salesman_id')
                    ->get();

                $diff = date_diff(date_create($start_date), date_create($end_date));
                $date_diff = $diff->format("%a");

                $salesman_time_spend = 0;

                // $completedTask = 0;
                // if (isset($customer_visits[0]) && $customer_visits[0]) {
                //     $completedTask = $customer_visits[0]->completedTask;
                // }
                //
                // $totalTask = 0;
                // if (isset($customer_visits[0]) && $customer_visits[0]) {
                //     $totalTask = $customer_visits[0]->totalTask;
                // }

                if (count($customer_ids) && isset($customer_visits[0]->sec)) {
                    $t = count($customer_ids) * $date_diff;
                    if (count($customer_visits)) {
                        $salesman_time_spend = round($customer_visits[0]->sec) / $t;
                    }
                }

                $salesman_visit = 0;
                if (isset($customer_visits[0]->visit)) {
                    if (count($customer_visits)) {
                        $salesman_visit = $customer_visits[0]->visit;
                    }
                }

                $totalTimeSpend = round($salesman_time_spend);
                $totalTimeSpends[] = $totalTimeSpend;
                $salesman_visits[] = $salesman_visit;

                $salesman_user = User::find($salesman->user_id);

                if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                    $comparison[] = array(
                        'name' => $salesman_user->salesmanInfo->salesman_supervisor,
                        'steps' => getHours($totalTimeSpend)
                    );
                } else {
                    $comparison[] = array(
                        'name' => $salesman_user->firstname,
                        'steps' => getHours($totalTimeSpend)
                    );
                }

                $VISITS = 0;
                if (count($customer_visits)) {
                    $VISITS = $customer_visits[0]->sec;
                }

                if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                    $customer_details[] = array(
                        'RES' => $salesman_user->salesmanInfo->salesman_supervisor,
                        'TOTAL_OUTLETS' => count($customer_ids),
                        'VISITS' => getHours($VISITS),
                        'EXECUTION' => getHours($totalTimeSpend)
                    );
                } else {
                    $customer_details[] = array(
                        'RES' => $salesman_user->firstname,
                        'TOTAL_OUTLETS' => count($customer_ids),
                        'VISITS' => getHours($VISITS),
                        'EXECUTION' => getHours($totalTimeSpend)
                    );
                }
            }

            $salesman_ids = array();
            if (count($salesman_infos)) {
                $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
            }

            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $count_ss = count($request->supervisor);
            } else {
                $count_ss = count($salesman_ids);
            }

            $percentage = 0;
            if (count($totalTimeSpends) != 0 && $salesman_ids != 0) {
                $percentage_data = round(array_sum($totalTimeSpends) / $count_ss);
                $percentage = gmdate("H:i:s", $percentage_data);
            }

            $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->whereIn('merchandizer_id', $salesman_ids)
                ->get();

            $customerInfos = array();
            if (count($customer_merchandizers)) {
                $customer_ids = $customer_merchandizers->pluck('user_id')->toArray();
                $customerInfos = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $customer_ids)
                    ->get();
            }

            $customer_ids = array();
            if (count($customerInfos)) {
                $customer_ids = $customerInfos->pluck('user_id')->toArray();
            }

            $trends_data = DB::table('salesman_infos')->select('customer_visits.added_on as date', DB::raw("ROUND(SUM(TIME_TO_SEC(visit_total_time)) / 60) as value"))
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $listing = DB::table('salesman_infos')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_categories.customer_category_name AS category,
                salesman.firstname AS merchandiser,
                customer_infos.customer_code AS customerCode,
                salesman_infos.salesman_supervisor AS supervisor,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.start_time AS startTime,
                customer_visits.is_sequnece AS sequence,
                customer_visits.end_time AS endTime,
                customer_visits.total_task as totalTask,
                customer_visits.completed_task as completedTask,
                customer_visits.visit_total_time as timeSpent,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.customer_id')
                // ->groupBy('customer_visits.date')
                ->get();
        }

        $visit_per_day = new \stdClass();
        $visit_per_day->title = "Time Spent";
        $visit_per_day->text = "Avg. time spent per visit";
        $visit_per_day->percentage = $percentage;
        $visit_per_day->trends = $trends_data;
        $visit_per_day->comparison = $comparison;
        $visit_per_day->contribution = $comparison;
        $visit_per_day->details = $customer_details;
        $visit_per_day->listing = $listing;
        return $visit_per_day;
    }

    private function routeCompliance($request, $start_date, $end_date)
    {
        $salesman_ids   = array();
        $isFilter       = false;
        $filter_type    = "salesman";
        if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
            $isFilter       = true;
            $filters        = $request->supervisor;
            $filter_type    = "supervisor";
            $salesmanInfos  = SalesmanInfo::where(function ($query) use ($filters) {
                if (!empty($filters)) {
                    foreach ($filters as $key => $filter_val) {
                        if ($key == 0) {
                            $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
                        } else {
                            $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
                        }
                    }
                }
            })
                ->groupBy('salesman_supervisor')
                ->get();
            $salesman_ids = $salesmanInfos->pluck('user_id')->toArray();
        } else if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
            $isFilter       = true;
            $salesman_ids   = $request->salesman_ids;
        }

        $customer_visit_query = CustomerVisit::select([DB::raw("SUM(CASE WHEN journey_plan_id > 0 THEN 1 ELSE 0 END) as total_journey"), DB::raw("SUM(CASE WHEN is_sequnece = '1' THEN 1 ELSE 0 END) as planed_journey"), DB::raw("SUM(CASE WHEN is_sequnece = '0' THEN 1 ELSE 0 END) as un_planed_journey"), 'id', 'customer_id', 'journey_plan_id', 'salesman_id', 'latitude', 'longitude', 'start_time', 'end_time', 'is_sequnece', 'date', 'created_at'])
            ->with(
                'customer:id,firstname,lastname',
                'salesman:id,firstname,lastname,email',
                'customer.customerInfo'
            )
            ->groupBy('salesman_id', 'customer_visits.date', 'customer_id');

        if (!empty($salesman_ids)) {
            $customer_visit_query->whereIn('salesman_id', $salesman_ids);
        }

        if ($end_date == $start_date) {
            $customer_visit_query->whereDate('date', $start_date)->get();
        } else if ($end_date) {
            $end_date = date('Y-m-d', strtotime('+1 days', strtotime($end_date)));
            $customer_visit_query->whereBetween('date', [$start_date, $end_date])->get();
        } else {
            $customer_visit_query->whereDate('date', $start_date)->get();
        }

        $customer_visit     = array();
        if (!empty($salesman_ids) || $isFilter == false) {
            $customer_visit     = $customer_visit_query->get();
        }

        $visit_report       = array();
        if (count($customer_visit)) {
            foreach ($customer_visit as $key => $visit) {
                $jp = 0;
                $salesman_id    = $visit->salesman_id;
                $journey_plans  = JourneyPlan::select([DB::raw('group_concat(id) as plan_ids')])
                    ->where('is_merchandiser', 1)
                    ->where('id', $visit->journey_plan_id)
                    ->where('merchandiser_id', $salesman_id)
                    ->get();

                foreach ($journey_plans as $j_plan) {
                    if (isset($j_plan->plan_ids) && ($j_plan->plan_ids != '')) {
                        $plan_id = explode(',', $j_plan->plan_ids);
                        if (!empty($plan_id)) {
                            $day = date('l', strtotime($visit->date));
                            //                            $week = (int)date('W', strtotime($visit->date));
                            $firstOfMonth   = date("Y-m-01", strtotime($visit->date));
                            $week           =  intval(date("W", strtotime($visit->date))) - intval(date("W", strtotime($firstOfMonth))) + 2;

                            $journey_plan_week = JourneyPlanWeek::select([DB::raw('group_concat(id) as week_ids')])
                                ->whereIn('journey_plan_id', $plan_id)
                                ->where('week_number', "week" . $week)
                                ->first();

                            if (!empty($journey_plan_week)) {
                                $week_ids = explode(',', $journey_plan_week['week_ids']);

                                $journey_plan_days = JourneyPlanDay::select('id', 'journey_plan_id')
                                    ->whereIn('journey_plan_id', $plan_id)
                                    ->whereIn('journey_plan_week_id', $week_ids)
                                    ->where('day_name', $day)
                                    ->get();
                                foreach ($journey_plan_days as $jp_day) {
                                    $jp += JourneyPlanCustomer::where('journey_plan_id', $jp_day->journey_plan_id)
                                        ->where('journey_plan_day_id', $jp_day->id)
                                        ->count();
                                }
                            }
                        }
                    }
                }

                $strike_calls           = 0;
                $strike_calls_percent   = 0;
                $total_customers        = 0;
                $total_visit_customers  = 0;
                if ($salesman_id > 0) {

                    // $m_customers = CustomerInfo::select([DB::raw('COUNT(DISTINCT user_id) as customers')])
                    // ->where('merchandiser_id', $salesman_id)
                    // ->first();

                    $m_customers = CustomerMerchandizer::select([DB::raw('COUNT(DISTINCT user_id) as customers')])->where('customer_merchandizers.merchandizer_id', $salesman_id)->first();

                    if (isset($m_customers->customers) && $m_customers->customers > 0) {
                        $total_customers = $m_customers->customers;
                    }
                }

                if (!isset($visit_report[$visit->date][$salesman_id])) {
                    $visit_report[$visit->date][$salesman_id]   = new \stdClass();
                }

                $visit_report[$visit->date][$salesman_id]->id           = $visit->id;
                $visit_report[$visit->date][$salesman_id]->date         = $visit->date;
                $visit_report[$visit->date][$salesman_id]->journeyPlan  = $jp;

                if (!isset($visit_report[$visit->date][$salesman_id]->totalJourney)) {
                    $visit_report[$visit->date][$salesman_id]->totalJourney = 0;
                }
                $visit_report[$visit->date][$salesman_id]->totalJourney           += 1;
                //$visit_report[$visit->date][$salesman_id]->planedJourney          = $jp;
                if (!isset($visit_report[$visit->date][$salesman_id]->planedJourney)) {
                    $visit_report[$visit->date][$salesman_id]->planedJourney = 0;
                }
                if ($visit->is_sequnece == 1) {
                    $visit_report[$visit->date][$salesman_id]->planedJourney += 1;
                }
                $visit_report[$visit->date][$salesman_id]->journeyPlanPercent     = ($visit_report[$visit->date][$salesman_id]->planedJourney > 0 && $jp > 0) ? (round(($visit_report[$visit->date][$salesman_id]->planedJourney / $jp), 2) * 100) . '%' : 0;

                if (!isset($visit_report[$visit->date][$salesman_id]->unPlanedJourney)) {
                    $visit_report[$visit->date][$salesman_id]->unPlanedJourney = 0;
                }
                if ($visit->is_sequnece == 0) {
                    $visit_report[$visit->date][$salesman_id]->unPlanedJourney += 1;
                }

                $visit_report[$visit->date][$salesman_id]->unPlanedJourneyPercent = ($visit_report[$visit->date][$salesman_id]->totalJourney > 0) ? (round(($visit_report[$visit->date][$salesman_id]->unPlanedJourney / $visit_report[$visit->date][$salesman_id]->totalJourney), 2) * 100) . '%' : 0;
                $visit_report[$visit->date][$salesman_id]->totalCustomers         = $total_customers;
                $visit_report[$visit->date][$salesman_id]->strike_calls           = "";
                $visit_report[$visit->date][$salesman_id]->strike_calls_percent   = "";
                $visit_report[$visit->date][$salesman_id]->merchandiserCode       = (is_object($visit->salesman->salesmanInfo)) ? $visit->salesman->salesmanInfo->salesman_code : "";
                $visit_report[$visit->date][$salesman_id]->merchandiserName       = $visit->salesman->getName();
                $visit_report[$visit->date][$salesman_id]->merchandiserFirstName  = $visit->salesman->firstname;
                $visit_report[$visit->date][$salesman_id]->salesmanSupervisor     = isset($visit->salesman->salesmanInfo->salesman_supervisor) ?  $visit->salesman->salesmanInfo->salesman_supervisor : "";
            }
        }

        $final_report       = array();
        $date_wise_report   = array();
        $startDate          = date('Y-m-d', strtotime($start_date));
        $endDate            = date('Y-m-d', strtotime($end_date));
        while ($startDate <= $endDate) {
            $report_date = $startDate;
            if (isset($visit_report[$report_date])) {
                $date_wise_report[$report_date] = $visit_report[$report_date];
            }
            $startDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
        }

        $count                      = 0;
        $trends_data                = array();
        $comparison_data            = array();
        $contribution_data          = array();
        $trend_array                = array();
        $merchandiser_array         = array();
        $merchandiser_name          = array();
        $salesman_customers         = array();
        $salesman_details           = array();
        $total_journey              = 0;

        foreach ($date_wise_report as $visit_date => $report) {
            foreach ($report as $key => $row) {
                if (isset($row->totalCustomers)) {
                    $strike_calls           = $row->totalCustomers - $row->totalJourney;
                    $strike_calls_percent   = 0;
                    if ($row->totalCustomers > 0) {
                        $strike_calls_percent   = ($strike_calls > 0) ? round($row->totalJourney / $row->totalCustomers, 2) * 100 : 0;
                    }
                    $report[$key]->strike_calls          = $strike_calls;
                    $report[$key]->strike_calls_percent  = $strike_calls_percent . "%";
                }
                $final_report[$count]       = $row;
                $final_report[$count]->date = $visit_date;
                if (!isset($trend_array[$visit_date])) {
                    $trend_array[$visit_date] = 0;
                }
                $trend_array[$visit_date] += $row->totalJourney;
                if ($filter_type == "salesman") {
                    if (!isset($merchandiser_array[$row->merchandiserCode]['planed_journey'])) {
                        $merchandiser_array[$row->merchandiserCode]['planed_journey'] = 0;
                    }
                    if (!isset($merchandiser_array[$row->merchandiserCode]['journey_plan'])) {
                        $merchandiser_array[$row->merchandiserCode]['journey_plan'] = 0;
                    }
                    $merchandiser_array[$row->merchandiserCode]['planed_journey']   += $row->planedJourney;
                    $merchandiser_array[$row->merchandiserCode]['journey_plan']     += $row->journeyPlan;
                    $merchandiser_name[$row->merchandiserCode]                      = $row->merchandiserFirstName;
                } else if ($filter_type == "supervisor") {
                    if (!isset($merchandiser_array[$row->salesmanSupervisor]['planed_journey'])) {
                        $merchandiser_array[$row->salesmanSupervisor]['planed_journey'] = 0;
                    }
                    if (!isset($merchandiser_array[$row->salesmanSupervisor]['journey_plan'])) {
                        $merchandiser_array[$row->salesmanSupervisor]['journey_plan'] = 0;
                    }
                    $merchandiser_array[$row->salesmanSupervisor]['planed_journey']   += $row->planedJourney;
                    $merchandiser_array[$row->salesmanSupervisor]['journey_plan']     += $row->journeyPlan;
                    $merchandiser_name[$row->salesmanSupervisor]                      = $row->salesmanSupervisor;
                }
                $total_journey += $row->totalJourney;

                $salesman_customers[$row->merchandiserCode]  = $row->totalCustomers;
                $count++;
            }
        }
        foreach ($trend_array as $key => $value) {
            $trends_data[] = array(
                'date'  => $key,
                'value' => $value,
            );
        }
        $routeCompliancePercentageAvg = 0;
        if (!empty($merchandiser_array)) {
            $total_planed_visit     = 0;
            $total_percent          = 0;
            $total_salesman         = count($merchandiser_array);
            foreach ($merchandiser_array as $key => $value) {
                $planed_visit           = isset($value['planed_journey']) ? $value['planed_journey'] : ''; // planed visit
                $journey_plan           = isset($value['journey_plan']) ? $value['journey_plan'] : ''; //journey_plan
                $total_planed_visit     = $total_planed_visit + $planed_visit;
                $execution              = 0;
                if ($journey_plan > 0) {
                    $execution  = round($planed_visit / $journey_plan * 100, 2);
                }
                $total_percent  = $total_percent + $execution;
                $salesman_details[] = array(
                    "RES"               => isset($merchandiser_name[$key]) ? $merchandiser_name[$key] : '', // salesman name
                    "VISITS"            => $planed_visit, // planed visit
                    "TOTAL_OUTLETS"     => $journey_plan, // journey plan
                    "EXECUTION"         => $execution . "%" //VISITS/ TOTAL OUTLETS *100
                );
                $comparison_data[] = array(
                    'name'  => isset($merchandiser_name[$key]) ? $merchandiser_name[$key] : '',
                    'steps' => $planed_visit,
                );
            }
            foreach ($merchandiser_array as $key => $value) {
                $planed_visit   = isset($value['planed_journey']) ? $value['planed_journey'] : 0; // planed visit
                $steps          = ($total_planed_visit > 0) ? round($planed_visit / $total_planed_visit * 100, 2) : 0;
                $contribution_data[] = array(
                    'name'  => isset($merchandiser_name[$key]) ? $merchandiser_name[$key] : '',
                    'steps' => $steps,
                );
            }
            if ($total_salesman > 0) {
                $routeCompliancePercentageAvg = $total_percent / $total_salesman;
            }
        }
        $routeCompliance                = new \stdClass();
        $routeCompliance->title         = "Route Compliance";
        $routeCompliance->text          = "Compliance to route plan";
        $routeCompliance->percentage    = round($routeCompliancePercentageAvg, 2) . "%";
        $routeCompliance->trends        = $trends_data;
        $routeCompliance->comparison    = $comparison_data;
        $routeCompliance->contribution  = $contribution_data;
        $routeCompliance->details       = $salesman_details;
        $routeCompliance->listing       = $final_report;
        return $routeCompliance;
    }

    // private function strikeRate2($request, $start_date, $end_date)
    // {
    //     $salesman_ids   = array();
    //     $isFilter       = false;
    //     $filter_type    = "salesman";
    //
    //     if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
    //         $isFilter       = true;
    //         $filters        = $request->supervisor;
    //         $filter_type    = "supervisor";
    //         $salesmanInfos  = SalesmanInfo::where(function ($query) use ($filters) {
    //             if (!empty($filters)) {
    //                 foreach ($filters as $key => $filter_val) {
    //                     if ($key == 0) {
    //                         $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
    //                     } else {
    //                         $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
    //                     }
    //                 }
    //             }
    //         })
    //             ->groupBy('salesman_supervisor')
    //             ->get();
    //
    //         $salesman_ids = $salesmanInfos->pluck('user_id')->toArray();
    //     } else if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
    //         $isFilter       = true;
    //         $salesman_ids   = $request->salesman_ids;
    //     }
    //
    //     $customer_visit_query = CustomerVisit::select([
    //         DB::raw("SUM(CASE WHEN journey_plan_id > 0 THEN 1 ELSE 0 END) as total_journey"),
    //         DB::raw("SUM(CASE WHEN is_sequnece = '1' THEN 1 ELSE 0 END) as planed_journey"),
    //         DB::raw("SUM(CASE WHEN is_sequnece = '0' THEN 1 ELSE 0 END) as un_planed_journey"),
    //         'id', 'customer_id', 'journey_plan_id', 'salesman_id', 'latitude', 'longitude', 'start_time', 'end_time', 'is_sequnece', 'date', 'created_at'
    //     ])
    //         ->with(
    //             'customer:id,firstname,lastname',
    //             'salesman:id,firstname,lastname,email',
    //             'customer.customerInfo'
    //         )
    //         ->groupBy('salesman_id', 'customer_visits.date', 'customer_id');
    //
    //     if (!empty($salesman_ids)) {
    //         $customer_visit_query->whereIn('salesman_id', $salesman_ids);
    //     }
    //
    //     if ($end_date == $start_date) {
    //         $customer_visit_query->whereDate('date', $start_date)->get();
    //     } else if ($end_date) {
    //         // $end_date = date('Y-m-d', strtotime('+1 days', strtotime($end_date)));
    //         $customer_visit_query->whereBetween('date', [$start_date, $end_date])->get();
    //     } else {
    //         $customer_visit_query->whereDate('date', $start_date)->get();
    //     }
    //
    //     $customer_visit     = array();
    //     if (!empty($salesman_ids) || $isFilter == false) {
    //         $customer_visit     = $customer_visit_query->get();
    //     }
    //
    //     $visit_report       = array();
    //     if (count($customer_visit)) {
    //         foreach ($customer_visit as $key => $visit) {
    //             $jp = 0;
    //             $salesman_id    = $visit->salesman_id;
    //             $journey_plans  = JourneyPlan::select([DB::raw('group_concat(id) as plan_ids')])
    //                 ->where('is_merchandiser', 1)
    //                 ->where('id', $visit->journey_plan_id)
    //                 ->where('merchandiser_id', $salesman_id)
    //                 ->get();
    //
    //             foreach ($journey_plans as $j_plan) {
    //                 if (isset($j_plan->plan_ids) && ($j_plan->plan_ids != '')) {
    //                     $plan_id = explode(',', $j_plan->plan_ids);
    //                     if (!empty($plan_id)) {
    //                         $day = date('l', strtotime($visit->date));
    //                         //                            $week = (int)date('W', strtotime($visit->date));
    //                         $firstOfMonth   = date("Y-m-01", strtotime($visit->date));
    //                         $week           =  intval(date("W", strtotime($visit->date))) - intval(date("W", strtotime($firstOfMonth))) + 2;
    //
    //                         $journey_plan_week = JourneyPlanWeek::select([DB::raw('group_concat(id) as week_ids')])
    //                             ->whereIn('journey_plan_id', $plan_id)
    //                             ->where('week_number', "week" . $week)
    //                             ->first();
    //
    //                         if (!empty($journey_plan_week)) {
    //                             $week_ids = explode(',', $journey_plan_week['week_ids']);
    //
    //                             $journey_plan_days = JourneyPlanDay::select('id', 'journey_plan_id')
    //                                 ->whereIn('journey_plan_id', $plan_id)
    //                                 ->whereIn('journey_plan_week_id', $week_ids)
    //                                 ->where('day_name', $day)
    //                                 ->get();
    //                             foreach ($journey_plan_days as $jp_day) {
    //                                 $jp += JourneyPlanCustomer::where('journey_plan_id', $jp_day->journey_plan_id)
    //                                     ->where('journey_plan_day_id', $jp_day->id)
    //                                     ->count();
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //
    //             $strike_calls           = 0;
    //             $strike_calls_percent   = 0;
    //             $total_customers        = 0;
    //             $total_visit_customers  = 0;
    //             if ($salesman_id > 0) {
    //                 // $m_customers = CustomerInfo::select([DB::raw('COUNT(DISTINCT user_id) as customers')])
    //                 //     ->where('merchandiser_id', $salesman_id)
    //                 //     ->first();
    //
    //                 $m_customers = CustomerMerchandizer::select([DB::raw('COUNT(DISTINCT user_id) as customers')])->where('customer_merchandizers.merchandizer_id', $salesman_id)
    //                     ->first();
    //
    //                 if (isset($m_customers->customers) && $m_customers->customers > 0) {
    //                     $total_customers = $m_customers->customers;
    //                 }
    //             }
    //
    //             if (!isset($visit_report[$visit->date][$salesman_id])) {
    //                 $visit_report[$visit->date][$salesman_id]   = new \stdClass();
    //             }
    //
    //             $visit_report[$visit->date][$salesman_id]->id           = $visit->id;
    //             $visit_report[$visit->date][$salesman_id]->date         = $visit->date;
    //             $visit_report[$visit->date][$salesman_id]->journeyPlan  = $jp;
    //
    //             if (!isset($visit_report[$visit->date][$salesman_id]->totalJourney)) {
    //                 $visit_report[$visit->date][$salesman_id]->totalJourney = 0;
    //             }
    //             $visit_report[$visit->date][$salesman_id]->totalJourney           += 1;
    //             //$visit_report[$visit->date][$salesman_id]->planedJourney          = $jp;
    //             if (!isset($visit_report[$visit->date][$salesman_id]->planedJourney)) {
    //                 $visit_report[$visit->date][$salesman_id]->planedJourney = 0;
    //             }
    //             if ($visit->is_sequnece == 1) {
    //                 $visit_report[$visit->date][$salesman_id]->planedJourney += 1;
    //             }
    //             $visit_report[$visit->date][$salesman_id]->journeyPlanPercent     = ($visit_report[$visit->date][$salesman_id]->planedJourney > 0 && $jp > 0) ? (round(($visit_report[$visit->date][$salesman_id]->planedJourney / $jp), 2) * 100) . '%' : 0;
    //
    //             if (!isset($visit_report[$visit->date][$salesman_id]->unPlanedJourney)) {
    //                 $visit_report[$visit->date][$salesman_id]->unPlanedJourney = 0;
    //             }
    //             if ($visit->is_sequnece == 0) {
    //                 $visit_report[$visit->date][$salesman_id]->unPlanedJourney += 1;
    //             }
    //
    //             $visit_report[$visit->date][$salesman_id]->unPlanedJourneyPercent = ($visit_report[$visit->date][$salesman_id]->totalJourney > 0) ? (round(($visit_report[$visit->date][$salesman_id]->unPlanedJourney / $visit_report[$visit->date][$salesman_id]->totalJourney), 2) * 100) . '%' : 0;
    //
    //             $visit_report[$visit->date][$salesman_id]->totalCustomers         = $total_customers;
    //
    //             $visit_report[$visit->date][$salesman_id]->strike_calls           = "";
    //
    //             $visit_report[$visit->date][$salesman_id]->strike_calls_percent   = "";
    //
    //             $visit_report[$visit->date][$salesman_id]->merchandiserCode       = (is_object($visit->salesman->salesmanInfo)) ? $visit->salesman->salesmanInfo->salesman_code : "";
    //             $visit_report[$visit->date][$salesman_id]->merchandiserName       = $visit->salesman->getName();
    //             $visit_report[$visit->date][$salesman_id]->merchandiserFirstName  = $visit->salesman->firstname;
    //             $visit_report[$visit->date][$salesman_id]->salesmanSupervisor     = isset($visit->salesman->salesmanInfo->salesman_supervisor) ?  $visit->salesman->salesmanInfo->salesman_supervisor : "";
    //         }
    //     }
    //
    //     $final_report       = array();
    //     $date_wise_report   = array();
    //     $startDate          = date('Y-m-d', strtotime($start_date));
    //     $endDate            = date('Y-m-d', strtotime($end_date));
    //     while ($startDate <= $endDate) {
    //         $report_date = $startDate;
    //         if (isset($visit_report[$report_date])) {
    //             $date_wise_report[$report_date] = $visit_report[$report_date];
    //         }
    //         $startDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
    //     }
    //
    //     $count                      = 0;
    //     $trends_data                = array();
    //     $comparison_data            = array();
    //     $contribution_data          = array();
    //     $trend_array                = array();
    //     $merchandiser_array         = array();
    //     $merchandiser_name          = array();
    //     $salesman_customers         = array();
    //     $salesman_details           = array();
    //     $total_journey              = 0;
    //
    //     foreach ($date_wise_report as $visit_date => $report) {
    //         foreach ($report as $key => $row) {
    //             if (isset($row->totalCustomers)) {
    //                 $strike_calls           = $row->totalCustomers - $row->totalJourney;
    //                 $strike_calls_percent   = 0;
    //                 if ($row->totalCustomers > 0) {
    //                     $strike_calls_percent   = ($strike_calls > 0) ? round($row->totalJourney / $row->totalCustomers * 100, 2) : 0;
    //                 }
    //                 $report[$key]->strike_calls          = $strike_calls;
    //                 $report[$key]->strike_calls_percent  = $strike_calls_percent . "%";
    //             }
    //             $final_report[$count]       = $row;
    //             $final_report[$count]->date = $visit_date;
    //             if (!isset($trend_array[$visit_date])) {
    //                 $trend_array[$visit_date] = 0;
    //             }
    //             $trend_array[$visit_date] += $row->totalJourney;
    //             if ($filter_type == "salesman") {
    //                 if (!isset($merchandiser_array[$row->merchandiserCode]['planed_journey'])) {
    //                     $merchandiser_array[$row->merchandiserCode]['planed_journey'] = 0;
    //                 }
    //                 if (!isset($merchandiser_array[$row->merchandiserCode]['unPlanedJourney'])) {
    //                     $merchandiser_array[$row->merchandiserCode]['unPlanedJourney'] = 0;
    //                 }
    //                 if (!isset($merchandiser_array[$row->merchandiserCode]['totalCustomers'])) {
    //                     $merchandiser_array[$row->merchandiserCode]['totalCustomers'] = 0;
    //                 }
    //                 if (!isset($merchandiser_array[$row->merchandiserCode]['journey_plan'])) {
    //                     $merchandiser_array[$row->merchandiserCode]['journey_plan'] = 0;
    //                 }
    //                 $merchandiser_array[$row->merchandiserCode]['planed_journey']   += $row->planedJourney;
    //                 $merchandiser_array[$row->merchandiserCode]['unPlanedJourney']   += $row->unPlanedJourney;
    //                 $merchandiser_array[$row->merchandiserCode]['totalCustomers']   += $row->totalCustomers;
    //                 $merchandiser_array[$row->merchandiserCode]['journey_plan']     += $row->journeyPlan;
    //                 $merchandiser_name[$row->merchandiserCode]                      = $row->merchandiserFirstName;
    //             } else if ($filter_type == "supervisor") {
    //                 if (!isset($merchandiser_array[$row->salesmanSupervisor]['planed_journey'])) {
    //                     $merchandiser_array[$row->salesmanSupervisor]['planed_journey'] = 0;
    //                 }
    //                 if (!isset($merchandiser_array[$row->salesmanSupervisor]['journey_plan'])) {
    //                     $merchandiser_array[$row->salesmanSupervisor]['journey_plan'] = 0;
    //                 }
    //                 $merchandiser_array[$row->salesmanSupervisor]['planed_journey']   += $row->planedJourney;
    //                 $merchandiser_array[$row->salesmanSupervisor]['journey_plan']     += $row->journeyPlan;
    //                 $merchandiser_array[$row->merchandiserCode]['unPlanedJourney']   += $row->unPlanedJourney;
    //                 $merchandiser_array[$row->merchandiserCode]['totalCustomers']   += $row->totalCustomers;
    //                 $merchandiser_name[$row->salesmanSupervisor]                      = $row->salesmanSupervisor;
    //             }
    //             $total_journey += $row->totalJourney;
    //
    //             $salesman_customers[$row->merchandiserCode]  = $row->totalCustomers;
    //             $count++;
    //         }
    //     }
    //     foreach ($trend_array as $key => $value) {
    //         $trends_data[] = array(
    //             'date'  => $key,
    //             'value' => $value,
    //         );
    //     }
    //     $routeCompliancePercentageAvg = 0;
    //     if (!empty($merchandiser_array)) {
    //         $total_planed_visit     = 0;
    //         $total_percent          = 0;
    //         $total_salesman         = count($merchandiser_array);
    //         foreach ($merchandiser_array as $key => $value) {
    //             $planed_visit           = isset($value['planed_journey']) ? $value['planed_journey'] : ''; // planed visit
    //             $unplaned_visit           = isset($value['unPlanedJourney']) ? $value['unPlanedJourney'] : ''; // unplaned visit
    //             $total_customer           = isset($value['totalCustomers']) ? $value['totalCustomers'] : ''; // totalCustomers
    //             $journey_plan           = isset($value['journey_plan']) ? $value['journey_plan'] : ''; //journey_plan
    //             $total_planed_visit     = $total_planed_visit + $planed_visit + $unplaned_visit;
    //             $execution              = 0;
    //             if ($journey_plan > 0) {
    //                 $execution  = round(($planed_visit + $unplaned_visit) / $total_customer * 100, 2);
    //             }
    //             $total_percent  = $total_percent + $execution;
    //             $salesman_details[] = array(
    //                 "RES"               => isset($merchandiser_name[$key]) ? $merchandiser_name[$key] : '', // salesman name
    //                 "VISITS"            => $planed_visit + $unplaned_visit, // planed visit
    //                 "TOTAL_OUTLETS"     => $total_customer, // journey plan
    //                 "EXECUTION"         => $execution . "%" //VISITS/ TOTAL OUTLETS *100
    //             );
    //             $comparison_data[] = array(
    //                 'name'  => isset($merchandiser_name[$key]) ? $merchandiser_name[$key] : '',
    //                 'steps' => $planed_visit + $unplaned_visit,
    //             );
    //         }
    //         foreach ($merchandiser_array as $key => $value) {
    //             $planed_visit   = isset($value['planed_journey']) ? $value['planed_journey'] : 0; // planed visit
    //             $unPlanedJourney   = isset($value['unPlanedJourney']) ? $value['unPlanedJourney'] : 0; // unplaned visit
    //             $steps          = ($total_planed_visit > 0) ? round(($planed_visit + $unPlanedJourney) / $total_planed_visit * 100, 2) : 0;
    //             $contribution_data[] = array(
    //                 'name'  => isset($merchandiser_name[$key]) ? $merchandiser_name[$key] : '',
    //                 'steps' => $steps,
    //             );
    //         }
    //         if ($total_salesman > 0) {
    //             $routeCompliancePercentageAvg = $total_percent / $total_salesman;
    //         }
    //     }
    //     $strike_rate                = new \stdClass();
    //     $strike_rate->title         = "Strike Rate";
    //     $strike_rate->text          = "Compliance to route plan";
    //     $strike_rate->percentage    = round($routeCompliancePercentageAvg, 2) . "%";
    //     $strike_rate->trends        = $trends_data;
    //     $strike_rate->comparison    = $comparison_data;
    //     $strike_rate->contribution  = $contribution_data;
    //     $strike_rate->details       = $salesman_details;
    //     $strike_rate->listing       = $final_report;
    //     return $strike_rate;
    // }

    private function strikeRate($request, $start_date, $end_date)
    {
        // if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
        //
        // } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
        //
        // } else {
        $salesman_ids   = array();
        if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
            $salesman_infos = SalesmanInfo::select('id', 'user_id')
                ->whereIn('user_id', $request->salesman_ids)
                ->get();
        } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
            $supervisor = $request->supervisor;
            $salesman_infos = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor')
                ->where(function ($query) use ($supervisor) {
                    if (!empty($supervisor)) {
                        foreach ($supervisor as $key => $filter_val) {
                            if ($key == 0) {
                                $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
                            } else {
                                $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
                            }
                        }
                    }
                })->get();;
        } else {
            $salesman_infos = SalesmanInfo::select('id', 'user_id')
                ->get();
        }

        $execution = array();
        foreach ($salesman_infos as $salesman) {

            $customer_visit = DB::select(
                "SELECT COUNT(*) as visit_count FROM (SELECT DISTINCT date, customer_id FROM `customer_visits` WHERE `shop_status` = 'OPEN' AND `reason` IS NULL AND `added_on` BETWEEN '$start_date' AND '$end_date' AND `salesman_id` = '$salesman->user_id') as x"
            );

            $visit = $customer_visit[0]->visit_count;
            $visits[] = $visit;

            // $visit_execution = ROUND(($customer_visit[0]->visit_count / $no_of_customers) * 100, 2);
            // $visit_executions[] = $visit_execution;

            $salesman_user = User::find($salesman->user_id);

            $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->where('merchandizer_id', $salesman->user_id)
                ->get();

            $customer_info = array();
            if (count($customer_merchandizers)) {
                $user_ids = $customer_merchandizers->pluck('user_id')->toArray();

                $customer_info = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $user_ids)
                    ->get();
            }


            $customer_ids = array();
            if (count($customer_info)) {
                $customer_ids = $customer_info->pluck('user_id')->toArray();
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_customers =  count($customer_ids) * $date_diff;

            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $comparison[] = array(
                    'name' => $salesman_user->salesmanInfo->salesman_supervisor,
                    'steps' => $visit
                );
            } else {
                $comparison[] = array(
                    'name' => $salesman_user->firstname,
                    'steps' => $visit
                );
            }

            if ($visit != 0 && $no_of_customers != 0) {
                $execution[] = number_format($visit / $no_of_customers * 100, 2);
            }

            $EXECUTIONS = 0;
            if ($visit != 0 && $no_of_customers != 0) {
                $EXECUTIONS = number_format($visit / $no_of_customers * 100, 2);
            }

            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $customer_details[] = array(
                    'RES' => $salesman_user->salesmanInfo->salesman_supervisor,
                    'TOTAL_OUTLETS' => count($customer_ids),
                    'VISITS' => $visit,
                    'EXECUTION' => $EXECUTIONS
                );
            } else {
                $customer_details[] = array(
                    'RES' => $salesman_user->firstname,
                    'TOTAL_OUTLETS' => count($customer_ids),
                    'VISITS' => $visit,
                    'EXECUTION' => $EXECUTIONS
                );
            }
        }


        $salesman_ids = array();
        if (count($salesman_infos)) {
            $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
        }

        $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
            ->whereIn('merchandizer_id', $salesman_ids)
            ->get();

        $customer_info = array();
        if (count($customer_merchandizers)) {
            $user_id = $customer_merchandizers->pluck('user_id')->toArray();
            $customer_info = CustomerInfo::select('id', 'user_id')
                ->whereIn('user_id', $user_id)
                ->get();
        }


        $customer_ids = array();
        if (count($customer_info)) {
            $customer_ids = $customer_info->pluck('user_id')->toArray();
        }

        $trends_datas = DB::table('salesman_infos')->select('customer_visits.added_on as date', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id)) as value'))
            // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
            // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
            // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
            // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
            ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
            ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
            ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
            ->where('customer_visits.shop_status', 'open')
            ->whereNull('customer_visits.reason')
            ->whereIn('customer_visits.customer_id', $customer_ids)
            ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
            ->where('salesman_infos.organisation_id', $this->organisation_id)
            ->groupBy('customer_visits.date')
            ->orderBy('customer_visits.added_on')
            // ->groupBy('customer_visits.salesman_id')
            ->get();

        $customer_visit = CustomerVisit::select([
            DB::raw("SUM(CASE WHEN journey_plan_id > 0 THEN 1 ELSE 0 END) as total_journey"),
            DB::raw("SUM(CASE WHEN is_sequnece = '1' THEN 1 ELSE 0 END) as planed_journey"),
            DB::raw("SUM(CASE WHEN is_sequnece = '0' THEN 1 ELSE 0 END) as un_planed_journey"),
            DB::raw("count(DISTINCT customer_id) as totalCustomers"),
            'id', 'customer_id', 'journey_plan_id', 'salesman_id', 'latitude', 'longitude', 'start_time', 'end_time', 'is_sequnece', 'date', 'created_at'
        ])
            ->with(
                'customer:id,firstname,lastname',
                'salesman:id,firstname,lastname,email',
                'customer.customerInfo'
            )
            ->where('customer_visits.shop_status', 'open')
            ->whereNull('customer_visits.reason')
            ->whereIn('customer_visits.salesman_id', $salesman_ids)
            ->whereBetween('customer_visits.date', [$start_date, $end_date])
            // ->where('salesman_infos.organisation_id', $this->organisation_id)
            ->groupBy('customer_id', 'salesman_id', 'customer_visits.date')
            ->get();
        // pre($customer_visit);

        if (count($customer_visit)) {
            foreach ($customer_visit as $key => $visit) {
                $jp = 0;
                $salesman_id    = $visit->salesman_id;

                $salesmanInfo = SalesmanInfo::where('user_id', $salesman_id)->first();

                if (is_object($salesmanInfo)) {
                    $customer_visit[$key]->merchandiserCode = $salesmanInfo->salesman_code;
                    $customer_visit[$key]->salesmanSupervisor = $salesmanInfo->salesman_supervisor;
                    if (is_object($salesmanInfo->user)) {
                        $customer_visit[$key]->merchandiserFirstName = $salesmanInfo->user->firstname;
                        $customer_visit[$key]->merchandiserName = $salesmanInfo->user->getName();
                    }
                }

                $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                    ->where('merchandizer_id', $salesman_id)
                    ->get();

                $merchandise_customer = array();
                if (count($customer_merchandizers)) {
                    $user_id = $customer_merchandizers->pluck('user_id')->toArray();

                    $merchandise_customer = CustomerInfo::select('id', 'user_id')
                        ->whereIn('user_id', $user_id)
                        ->get();
                }


                $day = date('l', strtotime($visit->date));
                $firstOfMonth   = date("Y-m-01", strtotime($visit->date));
                $week           =  intval(date("W", strtotime($visit->date))) - intval(date("W", strtotime($firstOfMonth))) + 2;
                $date = $visit->date;

                $journey_plans  = JourneyPlan::select('id')
                    ->where('is_merchandiser', 1)
                    ->where('merchandiser_id', $salesman_id)
                    ->first();

                $journey_plan_week = 0;
                if (is_object($journey_plans)) {
                    $journey_plan_week  = JourneyPlanWeek::select('id', 'journey_plan_id', 'week_number')
                        ->where('week_number', "week" . $week)
                        ->where('journey_plan_id', $journey_plans->id)
                        ->first();
                }

                $journey_plan_day = 0;
                if (is_object($journey_plan_week)) {
                    $journey_plan_day  = JourneyPlanDay::select('id', 'journey_plan_id', 'journey_plan_week_id', 'day_name')
                        ->where('day_name', $day)
                        ->where('journey_plan_id', $journey_plans->id)
                        ->where('journey_plan_week_id', $journey_plan_week->id)
                        ->first();
                }

                $count_customer = 0;
                if (is_object($journey_plan_day)) {
                    $JourneyPlanCustomer  = JourneyPlanCustomer::select('id', 'journey_plan_day_id')
                        ->where('journey_plan_day_id', $journey_plan_day->id)
                        ->get();
                    $count_customer = count($JourneyPlanCustomer);
                }

                $customer_visit[$key]->total_customer = count($merchandise_customer);
                $customer_visit[$key]->journeyPlan = $count_customer;
                $customer_visit[$key]->planedJourney = $customer_visit[$key]->planed_journey;
                $customer_visit[$key]->unPlanedJourney = $customer_visit[$key]->un_planed_journey;
                $customer_visit[$key]->totalJourney = $customer_visit[$key]->total_journey;
                if ($customer_visit[$key]->unPlanedJourney > 0 && $count_customer) {
                    $customer_visit[$key]->unPlanedJourneyPercent = round(($customer_visit[$key]->un_planed_journey / $customer_visit[$key]->total_journey) * 100, 2);
                } else {
                    $customer_visit[$key]->unPlanedJourneyPercent = "0%";
                }

                if ($customer_visit[$key]->planedJourney > 0 && $count_customer) {
                    $customer_visit[$key]->journeyPlanPercent = round(($customer_visit[$key]->planed_journey / $count_customer) * 100, 2);
                } else {
                    $customer_visit[$key]->journeyPlanPercent = "0%";
                }

                $customer_visit[$key]->strike_calls = count($merchandise_customer) - $customer_visit[$key]->total_customer;

                if ($customer_visit[$key]->strike_calls > 0 && $count_customer) {
                    $customer_visit[$key]->strike_calls_percent = round(($customer_visit[$key]->total_journey / $customer_visit[$key]->total_customer) * 100, 2);
                } else {
                    $customer_visit[$key]->strike_calls_percent = "0%";
                }

                unset($customer_visit[$key]->customer);
                unset($customer_visit[$key]->salesman);
            }
        }

        $details = "0";
        if (count($execution)) {
            $details = array_sum($execution) / count($salesman_ids);
        }
        // }

        $strike_rate                = new \stdClass();
        $strike_rate->title         = "Strike Rate";
        $strike_rate->text          = "Compliance to route plan";
        $strike_rate->percentage    = round($details, 2) . "%";
        $strike_rate->trends        = $trends_datas;
        $strike_rate->comparison    = $comparison;
        $strike_rate->contribution  = $comparison;
        $strike_rate->details       = $customer_details;
        $strike_rate->listing       = $customer_visit;
        return $strike_rate;
    }

    private function visitPerDay($request, $start_date, $end_date)
    {
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')
                ->whereIn('channel_id', $request->channel_ids)
                ->get();

            $customer_ids = array();
            if (count($customer_infos)) {
                $customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");

            $customer_visit = DB::table('customer_visits')->select('id', 'customer_id', 'salesman_id', DB::raw('ROUND(COUNT(customer_visits.id) /' . $date_diff . ') as visit'), DB::raw('COUNT(customer_visits.id) as no_visit'))
                ->where('shop_status', 'open')
                ->whereNull('reason')
                ->whereBetween('added_on', [$start_date, $end_date])
                ->whereIn('customer_id', $customer_ids)
                ->where('organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.salesman_id')
                ->get();

            $visit_avg = array();
            if (count($customer_visit)) {
                $visit_avg = $customer_visit->pluck('visit')->toArray();
            }

            $no_of_visits = round(array_sum($visit_avg), 2);
            $no_of_customers = $date_diff;

            if ($no_of_visits != 0 && count($request->channel_ids) != 0) {
                $percentage = round($no_of_visits / count($request->channel_ids), 2);
            } else {
                $percentage = "0";
            }

            $trends_data = DB::table('channels')->select('customer_visits.added_on as date', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . count($request->channel_ids) . ', 2) as value'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_infos', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                // ->groupBy('customer_visits.salesman_id')
                ->get();

            $comparison = DB::table('channels')
                ->select('channels.name as name', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . count($request->channel_ids) . ', 2) as steps'))
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->where('channels.organisation_id', $this->organisation_id)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                // ->groupBy('channels.id')
                ->get();

            $customer_details = DB::table('channels')->select(
                DB::raw('DISTINCT channels.name as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . count($request->channel_ids) . ', 2) AS EXECUTION')
            )
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                // ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'salesman_infos.user_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id', 'left')
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('channels.id')
                ->get();

            $listing = DB::table('channels')->select(
                DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    customer_visits.is_sequnece AS sequnece,
                    SUM(CASE WHEN customer_visits.is_sequnece > 0 THEN 1 ELSE 0 END) as unplanned,
                    SUM(CASE WHEN customer_visits.is_sequnece > 1 THEN 1 ELSE 0 END) as visit,
                    customer_visits.latitude AS latitude,
                    customer_visits.longitude AS longitude')
            )
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('users as salesman', 'salesman.id', '=', 'merchandisers.merchandizer_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'salesman.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->where('channels.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.added_on')
                ->groupBy('customer_visits.id')
                ->get();
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
            $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                ->whereIn('region_id', $request->region_ids)
                ->get();

            $customer_ids = array();
            if (count($customer_infos)) {
                $customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");

            $customer_visit = DB::table('customer_visits')->select('id', 'customer_id', DB::raw('ROUND(COUNT(customer_visits.id) /' . $date_diff . ') as visit'), DB::raw('COUNT(customer_visits.id) as no_visit'))
                ->where('shop_status', 'open')
                ->whereNull('reason')
                ->whereBetween('added_on', [$start_date, $end_date])
                ->whereIn('customer_id', $customer_ids)
                ->where('customer_visits.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.')
                ->get();

            $visit_avg = array();
            if (count($customer_visit)) {
                $visit_avg = $customer_visit->pluck('visit')->toArray();
            }

            $no_of_visits = round(array_sum($visit_avg), 2);
            $no_of_customers = $date_diff;

            if ($no_of_visits != 0 && count($request->region_ids) != 0) {
                $percentage = round($no_of_visits / count($request->region_ids));
            } else {
                $percentage = "0";
            }

            $trends_data = DB::table('regions')->select('customer_visits.added_on as date', DB::raw('ROUND(COUNT(DISTINCT customer_visits.customer_id)) as value'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_infos', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                // ->groupBy('customer_visits.salesman_id')
                ->get();

            $comparison = DB::table('regions')
                ->select('regions.region_name as name', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . count($request->region_ids) . ', 2) as steps'))
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('regions.id')
                ->get();

            $customer_details = DB::table('regions')->select(
                DB::raw('DISTINCT regions.region_name as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id)) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . count($request->region_ids) . ', 2) AS EXECUTION')
            )
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                // ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'salesman_infos.user_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id', 'left')
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('regions.id')
                // ->groupBy('customer_visits.customer_id')
                ->get();

            $listing = DB::table('regions')->select(
                DB::raw(
                    'DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    SUM(CASE WHEN customer_visits.is_sequnece > 0 THEN 1 ELSE 0 END) as unplanned,
                    SUM(CASE WHEN customer_visits.is_sequnece > 1 THEN 1 ELSE 0 END) as visit,
                    customer_visits.is_sequnece AS sequnece,
                    customer_visits.latitude AS latitude,
                    customer_visits.longitude AS longitude'
                )
            )
                // ->join('regions', 'regions.id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('users as salesman', 'salesman.id', '=', 'customer_merchandizers.merchandizer_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'salesman.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->where('customer_visits.shop_status', 'open')
                // ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_infos.user_id', $customer_ids)
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.id')
                // ->groupBy('regions.ids')
                ->get();
        } else {
            if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->whereIn('user_id', $request->salesman_ids)
                    ->get();
            } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $supervisor = $request->supervisor;
                $salesman_infos = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor')
                    ->where(function ($query) use ($supervisor) {
                        if (!empty($supervisor)) {
                            foreach ($supervisor as $key => $filter_val) {
                                if ($key == 0) {
                                    $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                } else {
                                    $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                }
                            }
                        }
                    })->get();;
            } else {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->get();
            }

            $salesman_ids = array();
            if (count($salesman_infos)) {
                $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
            }

            $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->whereIn('merchandizer_id', $salesman_ids)
                ->get();

            $customer_infos = array();
            if (count($customer_merchandizers)) {
                $user_ids = $customer_merchandizers->pluck('user_id')->toArray();
                $customer_infos = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $user_ids)
                    ->get();
            }

            $customer_ids = array();
            if (count($customer_infos)) {
                $customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");

            $customer_visit = DB::table('customer_visits')
                ->select('id', 'customer_id', 'salesman_id', DB::raw('ROUND(COUNT(customer_visits.id) /' . $date_diff . ') as visit'), DB::raw('COUNT(customer_visits.id) as no_visit'))
                ->where('shop_status', 'open')
                ->whereNull('reason')
                ->whereBetween('added_on', [$start_date, $end_date])
                ->whereIn('salesman_id', $salesman_ids)
                ->groupBy('customer_visits.salesman_id')
                ->get();

            $visit_avg = array();
            if (count($customer_visit)) {
                $visit_avg = $customer_visit->pluck('visit')->toArray();
            }

            $no_of_visits = round(array_sum($visit_avg), 2);
            $no_of_customers = $date_diff;

            if ($no_of_visits != 0 && count($salesman_ids) != 0) {
                $percentage = round($no_of_visits / count($salesman_ids));
            } else {
                $percentage = "0";
            }

            $trends_data = DB::table('salesman_infos')->select(
                'customer_visits.added_on as date',
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id)) as value')
            )
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                // ->groupBy('customer_visits.salesman_id')
                ->get();

            $comparison_query = DB::table('salesman_infos');
            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $comparison_query->select('salesman_infos.salesman_supervisor as name', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . $no_of_customers . ', 2) as steps'));
            } else {
                $comparison_query->select('users.firstname as name', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . $no_of_customers . ', 2) as steps'));
            }
            $comparison_query->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->where('customer_infos.organisation_id', $this->organisation_id);
            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $comparison_query->groupBy('salesman_infos.salesman_supervisor');
            } else {
                $comparison_query->groupBy('customer_visits.salesman_id');
            }

            $comparison = $comparison_query->get();

            if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $name = "DISTINCT salesman_infos.salesman_supervisor as RES";
                $gBy = 'salesman_infos.salesman_supervisor';
            } else {
                $name = "DISTINCT users.firstname as RES";
                $gBy = 'customer_visits.salesman_id';
            }

            $customer_details = DB::table('salesman_infos')
                ->select(
                    DB::raw($name),
                    DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                    DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                    DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . $no_of_customers . ', 2) AS EXECUTION')
                )
                ->join('users', 'users.id', '=', 'salesman_infos.user_id', 'left')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id', 'left')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id', 'left')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                // ->groupBy('customer_infos.id')
                ->groupBy($gBy)
                ->whereIn('customer_infos.user_id', $customer_ids)
                ->where('customer_infos.organisation_id', $this->organisation_id)
                ->get();


            $listing = DB::table('salesman_infos')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    SUM(CASE WHEN customer_visits.is_sequnece > 0 THEN 1 ELSE 0 END) as unplanned,
                    SUM(CASE WHEN customer_visits.is_sequnece > 1 THEN 1 ELSE 0 END) as visit,
                    customer_visits.latitude AS latitude,
                    customer_visits.longitude AS longitude')
                )
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('customer_visits.salesman_id')
                ->get();
        }

        $visit_per_day = new \stdClass();
        $visit_per_day->title = "Visit Per Day";
        $visit_per_day->text = "Average # of visits made by a sales man in a day";
        $visit_per_day->percentage = $percentage;
        $visit_per_day->trends = $trends_data;
        $visit_per_day->comparison = $comparison;
        $visit_per_day->contribution = $comparison;
        $visit_per_day->details = $customer_details;
        $visit_per_day->listing = $listing;
        return $visit_per_day;
    }

    private function visitFrequency($request, $start_date, $end_date)
    {
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            foreach ($request->channel_ids as $channel) {
                $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')
                    ->where('channel_id', $channel)
                    ->get();

                $customer_ids = array();
                if (count($customer_infos) > 0) {
                    $customer_ids = $customer_infos->pluck('user_id')->toArray();
                }

                $customer_str = implode(',', $customer_ids);

                $diff = date_diff(date_create($start_date), date_create($end_date));
                $date_diff = $diff->format("%a");
                $no_of_customers =  count($customer_ids) * $date_diff;
                if (count($customer_ids)) {
                    $customer_visit = DB::select(
                        "SELECT COUNT(*) as visit_count FROM (SELECT DISTINCT date, customer_id FROM `customer_visits` WHERE `shop_status` = 'OPEN' AND `reason` IS NULL AND `added_on` BETWEEN '$start_date' AND '$end_date' AND `customer_id` in ($customer_str)) as x"
                    );
                    $visit_execution = ROUND(($customer_visit[0]->visit_count / $no_of_customers) * 100, 2);
                    $visit_executions[] = $visit_execution;

                    $customer_visits[] = $customer_visit;

                    $channel_usr = Channel::find($channel);
                    $comparison[] = array(
                        'name' => $channel_usr->name,
                        'steps' => $customer_visit[0]->visit_count
                    );

                    $customer_details[] = array(
                        'RES' => $channel_usr->name,
                        'TOTAL_OUTLETS' => count($customer_ids),
                        'VISITS' => $customer_visit[0]->visit_count,
                        'EXECUTION' => $visit_execution
                    );
                }
            }

            $no_of_visits = array_sum($visit_executions);
            $no_of_customers = count($request->channel_ids);

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = ROUND($no_of_visits / $no_of_customers, 2);
            } else {
                $percentage = "0";
            }

            $all_customer_ids = array();
            $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')
                ->whereIn('channel_id', $request->channel_ids)
                ->get();

            if (count($customer_infos)) {
                $all_customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $trends_data = DB::table('channels')->select('customer_visits.added_on as date', DB::raw('ROUND(COUNT(DISTINCT customer_visits.customer_id)) as value'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_infos', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $all_customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                // ->groupBy('customer_visits.salesman_id')
                ->get();

            $listing = DB::table('channels')->select(
                DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    COUNT(DISTINCT customer_visits.id) as countOfVisit,
                    customer_visits.latitude AS latitude')
            )
                // ->join('regions', 'regions.id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('users as salesman', 'salesman.id', '=', 'merchandisers.merchandizer_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'salesman.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->where('customer_visits.shop_status', 'open')
                // ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $all_customer_ids)
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('channels.id')
                ->groupBy('customer_visits.customer_id')
                ->groupBy('customer_visits.date')
                ->get();
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {

            foreach ($request->region_ids as $region) {
                $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                    ->where('region_id', $region)
                    ->get();

                $customer_ids = array();
                if (count($customer_infos) > 0) {
                    $customer_ids = $customer_infos->pluck('user_id')->toArray();
                }

                $customer_str = implode(',', $customer_ids);

                $diff = date_diff(date_create($start_date), date_create($end_date));
                $date_diff = $diff->format("%a");
                $no_of_customers =  count($customer_ids) * $date_diff;

                $customer_visit = DB::select(
                    "SELECT COUNT(*) as visit_count FROM (SELECT DISTINCT date, customer_id FROM `customer_visits` WHERE `shop_status` = 'OPEN' AND `reason` IS NULL AND `added_on` BETWEEN '$start_date' AND '$end_date' AND `customer_id` in ($customer_str)) as x"
                );

                $customer_visits[] = $customer_visit;

                $visit_execution = ROUND(($customer_visit[0]->visit_count / $no_of_customers) * 100, 2);
                $visit_executions[] = $visit_execution;

                $region_usr = Region::find($region);

                $comparison[] = array(
                    'name' => $region_usr->region_name,
                    'steps' => $customer_visit[0]->visit_count
                );

                $customer_details[] = array(
                    'RES' => $region_usr->region_name,
                    'TOTAL_OUTLETS' => count($customer_ids),
                    'VISITS' => $customer_visit[0]->visit_count,
                    'EXECUTION' => $visit_execution
                );
            }

            // $customer_ids = array();
            // if (count($customer_infos)) {
            //     $customer_ids = $customer_infos->pluck('user_id')->toArray();
            // }
            //
            // $diff = date_diff(date_create($start_date), date_create($end_date));
            // $date_diff = $diff->format("%a");
            // $total_customers = $customer_ids * $date_diff;
            //
            // $customer_visit = DB::table('customer_visits')->select('id','customer_id', DB::raw('ROUND(COUNT(customer_visits.id) /'.$total_customers.') as visit'), DB::raw('COUNT(customer_visits.id) as no_visit'))
            // ->where('shop_status', 'open')
            // ->whereNull('reason')
            // ->whereBetween('added_on', [$start_date, $end_date])
            // ->whereIn('customer_id', $customer_ids)
            // ->where('customer_visits.organisation_id', $this->organisation_id)
            // // ->groupBy('customer_visits.')
            // ->get();

            // $visit_avg = array();
            // if (count($customer_visit)) {
            //     $visit_avg = $customer_visit->pluck('visit')->toArray();
            // }

            $no_of_visits = array_sum($visit_executions);
            $no_of_customers = count($request->region_ids);

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = ROUND($no_of_visits / $no_of_customers, 2);
            } else {
                $percentage = "0";
            }

            $all_customer_ids = array();
            $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                ->whereIn('region_id', $request->region_ids)
                ->get();

            if (count($customer_infos)) {
                $all_customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $trends_data = DB::table('regions')->select('customer_visits.added_on as date', DB::raw('ROUND(COUNT(DISTINCT customer_visits.customer_id)) as value'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_infos', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $all_customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                // ->groupBy('customer_visits.salesman_id')
                ->get();

            $listing = DB::table('regions')->select(
                DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    COUNT(DISTINCT customer_visits.id) as countOfVisit,
                    customer_visits.latitude AS latitude')
            )
                // ->join('regions', 'regions.id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('users as salesman', 'salesman.id', '=', 'merchandisers.merchandizer_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'salesman.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->where('customer_visits.shop_status', 'open')
                // ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $all_customer_ids)
                ->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('regions.id')
                ->groupBy('customer_visits.customer_id')
                ->groupBy('customer_visits.date')
                ->get();
        } else {
            if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->whereIn('user_id', $request->salesman_ids)
                    ->get();
            } else if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                $supervisor = $request->supervisor;
                $salesman_infos = SalesmanInfo::select('id', 'user_id', 'salesman_supervisor')
                    ->where(function ($query) use ($supervisor) {
                        if (!empty($supervisor)) {
                            foreach ($supervisor as $key => $filter_val) {
                                if ($key == 0) {
                                    $query->where('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                } else {
                                    $query->orWhere('salesman_supervisor', 'like', '%' . $filter_val . '%');
                                }
                            }
                        }
                    })->get();;
            } else {
                $salesman_infos = SalesmanInfo::select('id', 'user_id')
                    ->get();
            }

            $salesman_ids = array();
            $visit_avg = array();
            if (count($salesman_infos)) {
                $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
            }

            foreach ($salesman_ids as $salesman_id) {

                $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                    ->where('merchandizer_id', $salesman_id)
                    ->get();

                $customer_infos = array();
                if (count($customer_merchandizers)) {
                    $user_ids = $customer_merchandizers->pluck('user_id')->toArray();
                    $customer_infos = CustomerInfo::select('id', 'user_id')
                        ->whereIn('user_id', $user_ids)
                        ->get();
                }

                if (count($customer_infos)) {
                    $customer_id[] = $customer_infos->pluck('user_id')->toArray();
                } else {
                    $customer_id = array();
                }

                $diff = date_diff(date_create($start_date), date_create($end_date));
                $date_diff = $diff->format("%a");
                $no_of_customers =  count($customer_infos) * $date_diff;

                $customer_visit = DB::select(
                    "SELECT COUNT(*) as visit_count FROM (SELECT DISTINCT date, customer_id FROM `customer_visits` WHERE `shop_status` = 'OPEN' AND `reason` IS NULL AND `added_on` BETWEEN '$start_date' AND '$end_date' AND `salesman_id` = '$salesman_id') as x"
                );

                $visit_execution = 0;
                if ($customer_visit[0]->visit_count != 0 && $no_of_customers != 0) {
                    $visit_execution = ROUND(($customer_visit[0]->visit_count / $no_of_customers) * 100, 2);
                }
                $visit_executions[] = $visit_execution;

                $salesman_user = User::find($salesman_id);

                if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                    $comparison[] = array(
                        'name' => $salesman_user->salesmanInfo->salesman_supervisor,
                        'steps' => $customer_visit[0]->visit_count
                    );
                } else {
                    $comparison[] = array(
                        'name' => $salesman_user->firstname,
                        'steps' => $customer_visit[0]->visit_count
                    );
                }

                if (is_array($request->supervisor) && sizeof($request->supervisor) >= 1) {
                    $customer_details[] = array(
                        'RES' => $salesman_user->salesmanInfo->salesman_supervisor,
                        'TOTAL_OUTLETS' => count($customer_id),
                        'VISITS' => $customer_visit[0]->visit_count,
                        'EXECUTION' => $visit_execution
                    );
                } else {
                    $customer_details[] = array(
                        'RES' => $salesman_user->firstname,
                        'TOTAL_OUTLETS' => count($customer_id),
                        'VISITS' => $customer_visit[0]->visit_count,
                        'EXECUTION' => $visit_execution
                    );
                }
            }

            $customer_ids = array();

            $customer_merchandizers = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->where('merchandizer_id', $salesman_ids)
                ->get();

            $customer_infos = array();
            if (count($customer_merchandizers)) {
                $user_ids = $customer_merchandizers->pluck('user_id')->toArray();
                $customer_infos = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $user_ids)
                    ->get();
            }

            if (count($customer_infos)) {
                $customer_ids = $customer_infos->pluck('user_id')->toArray();
            }

            $no_of_visits = array_sum($visit_executions);
            $no_of_customers = count($salesman_ids);

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = ROUND($no_of_visits / $no_of_customers, 2);
            } else {
                $percentage = "0";
            }

            $trends_data = DB::table('salesman_infos')->select('customer_visits.added_on as date', DB::raw('ROUND(COUNT(DISTINCT customer_visits.id)) as value'))
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                // ->groupBy('customer_visits.salesman_id')
                ->get();

            $listing = DB::table('salesman_infos')
                ->select(
                    DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                customerInfo.firstname AS customer,
                customer_infos.customer_code AS customerCode,
                customer_categories.customer_category_name AS category,
                salesman.firstname AS merchandiser,
                salesman_infos.salesman_supervisor AS supervisor,
                channels.name AS channel,
                regions.region_name AS region,
                customer_visits.is_sequnece AS sequence,
                COUNT(DISTINCT customer_visits.id) as visit,
                COUNT(DISTINCT customer_visits.id) as unplanned,
                customer_visits.latitude AS latitude,
                customer_visits.longitude AS longitude')
                )
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'merchandisers.user_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_visits', 'customer_visits.customer_id', '=', 'customer_infos.user_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $customer_ids)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('customer_visits.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.customer_id')
                ->groupBy('customer_visits.date')
                // ->groupBy('customer_visits.salesman_id')
                ->get();
        }

        $visit_per_day = new \stdClass();
        $visit_per_day->title = "Visit Per Day";
        $visit_per_day->text = "Average # of visits made by a sales man in a day";
        $visit_per_day->percentage = $percentage;
        $visit_per_day->trends = $trends_data;
        $visit_per_day->comparison = $comparison;
        $visit_per_day->contribution = $comparison;
        $visit_per_day->details = $customer_details;
        $visit_per_day->listing = $listing;
        return $visit_per_day;
    }

    private function coverage($request, $start_date, $end_date)
    {
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
            $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')->whereIn('channel_id', $request->channel_ids)
                ->get();

            $all_customers = array();
            if (count($customer_infos)) {
                $all_customers = $customer_infos->pluck('user_id')
                    ->toArray();
            }
            $customer_visits = CustomerVisit::select('id', 'route_id', 'trip_id', 'customer_id', 'salesman_id', 'journey_plan_id', 'latitude', 'longitude', 'shop_status', 'start_time', 'end_time', 'is_sequnece', 'date', 'reason', 'added_on')->with('customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
                ->where('shop_status', 'open')
                ->whereNull('reason')
                ->whereBetween('added_on', [$start_date, $end_date])->whereIn('customer_id', $all_customers)
                // ->groupBy('trip_id')
                ->get();

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_visits = count($customer_visits);
            $no_of_customers = count($all_customers) * $date_diff;

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = round($no_of_visits / $no_of_customers * 100, 2) . '%';
            } else {
                $percentage = "0%";
            }

            $trends_data = DB::table('channels')->select('customer_visits.added_on as date', DB::raw('count(customer_visits.id) as value'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')

                ->join('customer_infos', 'channels.id', '=', 'customer_infos.channel_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('channels.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.salesman_id')
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $comparison = DB::table('channels')->select('channels.name as name', DB::raw('count(customer_visits.id) as steps'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')

                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('channels.id')
                ->get();

            $customer_details = DB::table('channels')->select(
                DB::raw('DISTINCT channels.name as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . $no_of_customers . ' * 100, 2) AS EXECUTION')
            )
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                // ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('channels.id')
                ->get();

            $listing = DB::table('channels')->select(DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    customer_visits.total_task AS total_tasks_planned,
                    SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                    COUNT(DISTINCT customer_visits.id) as total_visits'))
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                // ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('users as salesman', 'salesman.id', '=', 'merchandisers.merchandizer_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'salesman.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $all_customers)->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('channel')
                ->get();
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
            $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')->whereIn('region_id', $request->region_ids)
                ->get();

            $all_customers = array();
            if (count($customer_infos)) {
                $all_customers = $customer_infos->pluck('user_id')
                    ->toArray();
            }

            $customer_visits = CustomerVisit::select('id', 'route_id', 'trip_id', 'customer_id', 'salesman_id', 'journey_plan_id', 'latitude', 'longitude', 'shop_status', 'start_time', 'end_time', 'is_sequnece', 'date', 'reason', 'added_on')->with('customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
                ->where('shop_status', 'open')
                ->whereNull('reason')
                ->whereBetween('date', [$start_date, $end_date])
                ->whereIn('customer_id', $all_customers)
                // ->groupBy('trip_id')
                ->get();

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_visits = count($customer_visits);
            $no_of_customers = count($all_customers) * $date_diff;

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = round($no_of_visits / $no_of_customers * 100, 2) . '%';
            } else {
                $percentage = "0%";
            }

            $trends_data = DB::table('regions')->select('customer_visits.added_on as date', DB::raw('count(customer_visits.id) as value'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')

                ->join('customer_infos', 'regions.id', '=', 'customer_infos.region_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_infos.merchandiser_id')

                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('regions.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.salesman_id')

                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $comparison = DB::table('regions')->select('regions.region_name as name', DB::raw('count(customer_visits.id) as steps'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')

                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('regions.id')
                ->get();

            $customer_details = DB::table('regions')->select(
                DB::raw('DISTINCT regions.region_name as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . $no_of_customers . ' * 100, 2) AS EXECUTION')
            )->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                // ->join('customer_infos', 'customer_infos.merchandiser_id', '=', 'salesman_infos.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('regions.id')
                ->get();

            $listing = DB::table('regions')->select(DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    customer_visits.total_task AS total_tasks_planned,
                    SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                    COUNT(DISTINCT customer_visits.id) as total_visits'))
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_merchandizers as merchandisers', 'merchandisers.user_id', '=', 'customer_infos.user_id')
                ->join('users as salesman', 'salesman.id', '=', 'merchandisers.merchandizer_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'salesman.id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->where('customer_visits.shop_status', 'open')
                ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_visits.customer_id', $all_customers)->where('regions.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.added_on')
                ->groupBy('region')
                ->get();
        } else {
            $salesman_ids = array();
            if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
                $salesman_ids = $request->salesman_ids;
            }

            $salesman_info_query = SalesmanInfo::select('id', 'user_id');
            if (count($salesman_ids)) {
                $salesman_info_query->whereIn('user_id', $request->salesman_ids);
            }
            $salesman_info = $salesman_info_query->get();

            $salesman_user_ids = array();
            if (count($salesman_info)) {
                $salesman_user_ids = $salesman_info->pluck('user_id')
                    ->toArray();
            }

            $customer_merchandiser = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->where('merchandizer_id', $salesman_user_ids)
                ->get();

            $all_customers = array();
            $customer_infos = array();
            if (count($customer_merchandiser)) {
                $all_customers = $customer_merchandiser->pluck('user_id')->toArray();
                $customer_infos = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $all_customers)
                    ->get();
            }

            // $customer_infos = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            //     ->whereIn('merchandiser_id', $salesman_user_ids)
            //     ->get();

            $all_customers = array();
            if (count($customer_infos)) {
                $all_customers = $customer_infos->pluck('user_id')
                    ->toArray();
            }

            $customer_visits = CustomerVisit::select('id', 'route_id', 'trip_id', 'customer_id', 'salesman_id', 'journey_plan_id', 'latitude', 'longitude', 'shop_status', 'start_time', 'end_time', 'is_sequnece', 'date', 'reason', 'added_on')->with('customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
                ->where('shop_status', 'open')
                ->whereNull('reason')
                ->whereBetween('date', [$start_date, $end_date])
                ->whereIn('customer_id', $all_customers)
                // ->groupBy('date')
                ->get();

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_visits = count($customer_visits);
            $no_of_customers = count($all_customers) * $date_diff;
            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = round($no_of_visits / $no_of_customers * 100, 2) . '%';
            } else {
                $percentage = "0%";
            }

            $trends_data = DB::table('salesman_infos')->select('customer_visits.added_on as date', DB::raw('count(customer_visits.id) as value'))
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)->where('salesman_infos.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.salesman_id')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->groupBy('customer_visits.date')
                ->orderBy('customer_visits.added_on')
                ->get();

            $comparison = DB::table('salesman_infos')->select('users.firstname as name', DB::raw('count(customer_visits.id) as steps'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereIn('customer_infos.user_id', $all_customers)
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('users.id')
                // ->groupBy('customer_infos.merchandiser_id')
                // ->groupBy('customer_visits.date')
                // ->groupBy('customer_merchandizers.merchandizer_id')

                ->get();

            $customer_details = DB::table('salesman_infos')->select(
                DB::raw('DISTINCT users.firstname as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT customer_visits.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT customer_visits.id) / ' . $no_of_customers . ' * 100, 2) AS EXECUTION')
            )
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->where('customer_visits.shop_status', 'open')
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.date', [$start_date, $end_date])
                ->whereIn('customer_infos.user_id', $all_customers)
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                // ->groupBy('customer_visits.customer_id')
                // ->groupBy('customer_visits.date')
                ->groupBy('salesman_infos.user_id')
                // ->groupBy('customer_visits.trip_id')
                ->get();

            $listing = DB::table('salesman_infos')->select(DB::raw('DISTINCT DATE(customer_visits.added_on) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    customer_visits.total_task AS total_tasks_planned,
                    SUM(customer_visits.completed_task) AS no_of_tasks_completed,
                    COUNT(DISTINCT customer_visits.id) as total_visits'))
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('customer_visits', 'customer_infos.user_id', '=', 'customer_visits.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->where('customer_visits.shop_status', 'open')
                // ->where('customer_visits.completed_task', '!=', 0)
                ->whereNull('customer_visits.reason')
                ->whereBetween('customer_visits.added_on', [$start_date, $end_date])
                ->whereIn('customer_visits.customer_id', $all_customers)
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('customer_visits.customer_id')
                ->groupBy('customer_visits.date')
                ->groupBy('merchandiser')
                ->get();
        }

        $coverage = new \stdClass();
        $coverage->title = "Coverage";
        $coverage->text = "Outlets Visited atleast once this month vs all outlet in the market";
        $coverage->percentage = $percentage;
        $coverage->trends = $trends_data;
        $coverage->comparison = $comparison;
        $coverage->contribution = $comparison;
        $coverage->details = $customer_details;
        $coverage->listing = $listing;

        return $coverage;
    }

    private function activeOutlets($request, $start_date, $end_date)
    {
        if (is_array($request->channel_id) && sizeof($request->channel_id) >= 1) {
            $customer_infos = CustomerInfo::select('id', 'user_id', 'channel_id')->whereIn('channel_id', $request->channel_ids)
                ->get();

            $all_customers = array();
            if (count($customer_infos)) {
                $all_customers = $customer_infos->pluck('user_id')->toArray();
            }

            $orders = Order::select('id', 'customer_id', 'depot_id', 'order_type_id', 'salesman_id', 'order_number', 'order_date', 'due_date', 'created_at')
                ->with('customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->whereIn('customer_id', $all_customers)
                ->get();

            $orders_customers = array();
            if (count($orders)) {
                $orders_customers = $orders->pluck('customer_id');
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_visits = count($orders_customers);
            $no_of_customers = count($all_customers) * $date_diff;

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = round($no_of_visits / $no_of_customers * 100, 2) . '%';
            } else {
                $percentage = "0%";
            }

            $trends_data = DB::table('channels')->select(
                'orders.created_at as date',
                DB::raw('count(orders.id) as value')
            )
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('orders', 'orders.customer_id', '=', 'customer_infos.user_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('channels.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->orderBy('orders.created_at')
                ->get();

            $comparison = DB::table('channels')->select(
                'channels.name as name',
                DB::raw('count(orders.id) as steps')
            )
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')

                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('channels.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->groupBy('users.id')
                ->get();

            $customer_details = DB::table('channels')->select(
                DB::raw('DISTINCT channels.name as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT orders.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT orders.id) / ' . $no_of_customers . ' * 100, 2) AS EXECUTION')
            )
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->whereIn('customer_infos.user_id', $orders_customers)
                ->where('users.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->groupBy('channels.id')
                ->get();

            $listing = DB::table('channels')->select(
                DB::raw('DISTINCT DATE(orders.created_at) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    orders.order_number as orderNo,
                    orders.grand_total as orderValue')
            )
                ->join('customer_infos', 'customer_infos.channel_id', '=', 'channels.id')
                ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                // ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->where('channels.organisation_id', $this->organisation_id)
                ->groupBy('orders.created_at')
                ->groupBy('channel')
                ->get();
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
            $customer_infos = CustomerInfo::select('id', 'user_id', 'region_id')
                ->whereIn('region_id', $request->region_ids)
                ->get();

            $all_customers = array();
            if (count($customer_infos)) {
                $all_customers = $customer_infos->pluck('user_id')->toArray();
            }

            $orders = Order::select('id', 'customer_id', 'depot_id', 'order_type_id', 'salesman_id', 'order_number', 'order_date', 'due_date', 'created_at')
                ->with('customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->whereIn('customer_id', $all_customers)
                ->get();

            $orders_customers = array();
            if (count($orders)) {
                $orders_customers = $orders->pluck('customer_id');
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_visits = count($orders_customers);
            $no_of_customers = count($all_customers) * $date_diff;

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = round($no_of_visits / $no_of_customers * 100, 2) . '%';
            } else {
                $percentage = "0%";
            }

            $trends_data = DB::table('regions')->select(
                'orders.created_at as date',
                DB::raw('count(orders.id) as value')
            )
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'regions.id', '=', 'customer_infos.region_id')
                ->join('orders', 'orders.customer_id', '=', 'customer_infos.user_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->where('regions.organisation_id', $this->organisation_id)
                ->orderBy('orders.created_at')
                ->get();

            $comparison = DB::table('regions')->select('regions.region_name as name', DB::raw('count(orders.id) as steps'))
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('regions.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->groupBy('regions.id')
                ->get();

            $customer_details = DB::table('regions')->select(
                DB::raw('DISTINCT regions.region_name as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT orders.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT orders.id) / ' . $no_of_customers . ' * 100, 2) AS EXECUTION')
            )
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('regions.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->groupBy('regions.id')
                ->get();

            $listing = DB::table('regions')->select(
                DB::raw('DISTINCT DATE(orders.created_at) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    orders.order_number as orderNo,
                    orders.grand_total as orderValue')
            )
                ->join('customer_infos', 'customer_infos.region_id', '=', 'regions.id')
                ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                // ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('orders.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->groupBy('orders.created_at')
                ->groupBy('region')
                ->get();
        } else {
            $salesman_ids = array();
            if (is_array($request->salesman_ids) && sizeof($request->salesman_ids) >= 1) {
                $salesman_ids = $request->salesman_ids;
            }

            $salesman_info_query = SalesmanInfo::select('id', 'user_id');
            if (count($salesman_ids)) {
                $salesman_info_query->whereIn('user_id', $request->salesman_ids);
            }
            $salesman_info = $salesman_info_query->get();

            $salesman_user_ids = array();
            if (count($salesman_info)) {
                $salesman_user_ids = $salesman_info->pluck('user_id')
                    ->toArray();
            }

            $customer_merchandiser = CustomerMerchandizer::select('id', 'user_id', 'merchandizer_id')
                ->where('merchandizer_id', $salesman_user_ids)
                ->get();

            $all_customers = array();
            $customer_infos = array();
            if (count($customer_merchandiser)) {
                $all_customers = $customer_merchandiser->pluck('user_id')->toArray();
                $customer_infos = CustomerInfo::select('id', 'user_id')
                    ->whereIn('user_id', $all_customers)
                    ->get();
            }

            // $customer_infos = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            //     ->whereIn('merchandiser_id', $salesman_user_ids)
            //     ->get();

            $all_customers = array();
            if (count($customer_infos)) {
                $all_customers = $customer_infos->pluck('user_id')
                    ->toArray();
            }

            $orders = Order::select('id', 'customer_id', 'depot_id', 'order_type_id', 'salesman_id', 'order_number', 'order_date', 'due_date', 'created_at')
                ->with('customer:id,firstname,lastname', 'salesman:id,firstname,lastname')
                ->whereBetween('created_at', [$start_date, $end_date])
                ->whereIn('customer_id', $all_customers)
                ->get();

            $orders_customers = array();
            if (count($orders)) {
                $orders_customers = $orders->pluck('customer_id');
            }

            $diff = date_diff(date_create($start_date), date_create($end_date));
            $date_diff = $diff->format("%a");
            $no_of_visits = count($orders_customers);
            $no_of_customers = count($all_customers) * $date_diff;

            if ($no_of_visits != 0 && $no_of_customers != 0) {
                $percentage = round($no_of_visits / $no_of_customers * 100, 2) . '%';
            } else {
                $percentage = "0%";
            }

            $trends_data = DB::table('salesman_infos')->select('orders.created_at as date', DB::raw('count(orders.id) as value'))
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')
                // ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('orders', 'orders.customer_id', '=', 'customer_infos.user_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->orderBy('orders.created_at')
                ->get();

            $comparison = DB::table('salesman_infos')->select(
                'users.firstname as name',
                DB::raw('count(orders.id) as steps')
            )
                // ->join('customer_merchandizers', 'customer_merchandizers.user_id', '=', 'customer_infos.user_id')
                // ->join('salesman_infos', 'salesman_infos.user_id', '=', 'customer_merchandizers.merchandizer_id')

                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->groupBy('salesman_infos.id')
                // ->groupBy('customer_infos.merchandiser_id')
                // ->groupBy('customer_visits.date')
                // ->groupBy('customer_merchandizers.merchandizer_id')

                ->get();

            $customer_details = DB::table('salesman_infos')->select(
                DB::raw('DISTINCT users.firstname as RES'),
                DB::raw('COUNT(DISTINCT customer_infos.id) as TOTAL_OUTLETS'),
                DB::raw('COUNT(DISTINCT orders.id) AS VISITS'),
                DB::raw('ROUND(COUNT(DISTINCT orders.id) / ' . $no_of_customers . ' * 100, 2) AS EXECUTION')
            )
                // ->join('users', 'salesman_infos.user_id', '=', 'users.id')
                // ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id', 'left')
                ->join('users', 'users.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                // ->where('customer_visits.shop_status', 'open')
                // ->whereNull('customer_visits.reason')
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->whereIn('customer_infos.user_id', $orders_customers)
                ->where('users.organisation_id', $this->organisation_id)
                ->groupBy('users.id')
                ->get();

            $listing = DB::table('salesman_infos')->select(DB::raw('DISTINCT DATE(orders.created_at) as date,
                    customerInfo.firstname AS customer,
                    customer_infos.customer_code AS customerCode,
                    customer_categories.customer_category_name AS category,
                    salesman.firstname AS merchandiser,
                    salesman_infos.salesman_supervisor AS supervisor,
                    channels.name AS channel,
                    regions.region_name AS region,
                    orders.order_number as orderNo,
                    orders.grand_total as orderValue'))
                ->join('users as salesman', 'salesman.id', '=', 'salesman_infos.user_id')
                ->join('customer_merchandizers', 'customer_merchandizers.merchandizer_id', '=', 'salesman_infos.user_id')
                ->join('customer_infos', 'customer_infos.user_id', '=', 'customer_merchandizers.user_id')
                ->join('orders', 'customer_infos.user_id', '=', 'orders.customer_id')
                ->join('users as customerInfo', 'customerInfo.id', '=', 'customer_infos.user_id')
                ->join('customer_categories', 'customer_categories.id', '=', 'customer_infos.customer_category_id')
                ->join('channels', 'channels.id', '=', 'customer_infos.channel_id')
                ->join('regions', 'regions.id', '=', 'customer_infos.region_id')
                // ->where('customer_visits.shop_status', 'open')
                // ->where('customer_visits.completed_task', '!=', 0)
                // ->whereNull('customer_visits.reason')
                ->whereBetween('orders.created_at', [$start_date, $end_date])
                ->whereIn('orders.customer_id', $orders_customers)
                ->where('salesman_infos.organisation_id', $this->organisation_id)
                ->groupBy('orders.created_at')
                ->groupBy('merchandiser')
                ->get();
        }

        $activeOutlets = new \stdClass();
        $activeOutlets->title = "Active Outlets";
        $activeOutlets->text = "Where atleast one order was made from a visit this month";
        $activeOutlets->percentage = $percentage;
        $activeOutlets->trends = $trends_data;
        $activeOutlets->comparison = $comparison;
        $activeOutlets->contribution = $comparison;
        $activeOutlets->details = $customer_details;
        $activeOutlets->listing = $listing;

        return $activeOutlets;
    }
}
