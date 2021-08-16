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
use App\Model\Planogram;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Dashboard2Controller extends Controller
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
        $planogram = $this->planogram($request, $start_date, $end_date);
        // $activeOutlets = $this->activeOutlets($request, $start_date, $end_date);
        // $visitPerDay = $this->visitPerDay($request, $start_date, $end_date);
        // $visitFrequency = $this->visitFrequency($request, $start_date, $end_date);
        // $timeSpent = $this->timeSpent($request, $start_date, $end_date);
        // $routeCompliance = $this->routeCompliance($request, $start_date, $end_date);
        // $strikeRate = $this->strikeRate($request, $start_date, $end_date);

        $data = array(
            'coverage' => $coverage,
            'execution' => $execution,
            'planogram' => $planogram,
            // 'activeOutlets' => $activeOutlets,
            // 'visitPerDay' => $visitPerDay,
            // 'visitFrequency' => $visitFrequency,
            // 'timeSpent' => $timeSpent,
            // 'strikeRate' => $strikeRate,
            // 'routeCompliance' => $routeCompliance
        );

        return prepareResult(true, $data, [], "dashboard listing", $this->success);
    }

    private function planogram($request, $start_date, $end_date)
    {
        if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
        } else if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
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

            $salesman_ids = array();
            if (count($salesman_infos)) {
                $salesman_ids = $salesman_infos->pluck('user_id')->toArray();
            }

            if (count($salesman_ids)) {
                foreach ($salesman_ids as $salesman_id) {

                    $customerInfos = CustomerInfo::select('id', 'merchandiser_id', 'user_id')
                        ->where('merchandiser_id', $salesman_id)
                        ->get();

                    $customer_ids = array();
                    if (count($customerInfos)) {
                        $customer_ids = $customerInfos->pluck('user_id');
                    }

                    $planogram = Planogram::select('id', 'name', 'start_date', 'end_date', 'status')
                        ->with('planogramCustomer')
                        ->where('status', 1)
                        ->where('start_date', '<=', $request->start_date)
                        ->where('end_date', '>=', $request->end_date)
                        ->whereHas('planogramCustomer', function ($q) use ($customer_ids) {
                            $q->whereIn('customer_id', $customer_ids);
                        })
                        ->get();


                    // $planogram = DB::table('planograms')
                    //     ->select(
                    //         DB::raw('COUNT(planograms.id) as totalTask'),
                    //         DB::raw('COUNT(planogram_posts.customer_id) as completedTask')
                    //     )
                    //     ->join('planogram_customers', 'planogram_customers.planogram_id', '=', 'planograms.id')
                    //     ->join('planogram_posts', 'planogram_posts.customer_id', '=', 'planogram_customers.customer_id')
                    //     ->where('planograms.status', 1)
                    //     ->where('planograms.start_date', '<=', $request->start_date)
                    //     ->where('planograms.end_date', '>=', $request->end_date)
                    //     ->whereIn('planogram_customers.customer_id', $customer_ids)
                    //     ->where('planograms.organisation_id', $this->organisation_id)
                    //     ->groupBy('planogram_posts.customer_id')
                    //     ->get();

                    pre($planogram, false);


                    // $planogram = DB::table('planograms')
                    //     ->select(
                    //         DB::raw('COUNT(planograms.id) as totalTask'),
                    //         DB::raw('COUNT(planogram_posts.customer_id) as completedTask')
                    //     )
                    //     ->join('planogram_customers', 'planogram_customers.id', '=', 'planograms.id')
                    //     ->join('planogram_posts', 'planogram_posts.planogram_id', '=', 'planograms.id')
                    //     ->where('planogram_customers.customer_id', $customer_id)
                    //     ->where('planograms.start_date', '<=', $start_date)
                    //     ->where('planograms.end_date', '>=', $end_date)
                    //     ->where('planograms.organisation_id', $this->organisation_id)
                    //     ->get();


                    // pre($planogram, false);



                    // $planogram = Planogram::select('id', 'name', 'start_date', 'end_date', 'status')
                    //     ->with('planogramCustomer')
                    //     ->where('status', 1)
                    //     ->where('start_date', '<=', $request->start_date)
                    //     ->where('end_date', '>=', $request->end_date)
                    //     ->whereHas('planogramCustomer', function ($q) use ($customer_id) {
                    //         $q->where('customer_id', $customer_id);
                    //     })
                    //     ->get();
                    // $totalPlanogram = 0;
                    // $pcustomer_ids = array();

                    // if (count($planogram)) {
                    //     $totalPlanogram = count($planogram);
                    //     $pcustomer_ids = $planogram->pluck('planogramCustomer');
                    // }

                    // pre($pcustomer_ids, false);

                    // $planogram_posts = DB::table('planogram_posts')
                    //     ->select(
                    //         DB::raw($totalPlanogram . ' as totalTask'),
                    //         DB::raw('COUNT(planogram_posts.customer_id) as completedTask')
                    //     )
                    //     ->whereIn('customer_id', $pcustomer_ids)
                    //     ->where('organisation_id', $this->organisation_id)
                    //     ->groupBy('planogram_posts.customer_id')
                    //     ->get();

                }
            }
        }
    }

    private function coverage($request, $start_date, $end_date)
    {
    }

    private function execution($request, $start_date, $end_date)
    {
    }
}
