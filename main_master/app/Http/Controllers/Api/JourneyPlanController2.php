<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\JourneyPlanImport;
use App\Model\CustomerInfo;
use App\Model\ImportTempFile;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanCustomer;
use App\Model\JourneyPlanDay;
use App\Model\JourneyPlanWeek;
use App\Model\SalesmanInfo;
use Illuminate\Http\Request;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

use File;
use phpDocumentor\Reflection\Types\Null_;
use URL;


class JourneyPlanController2 extends Controller
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

        $journey_plans = JourneyPlan::with(
            'route:id,organisation_id,uuid,area_id,route_code,route_name,status'
            // ,
            // 'journeyPlanWeeks:id,journey_plan_id,uuid,week_number',
            // 'journeyPlanWeeks.journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
            // 'journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time'
        )
            ->orderBy('id', 'desc')
            ->get();

        $results = GetWorkFlowRuleObject('Journey Plan');
        $approve_need_journey_plans = array();
        $approve_need_journey_plans_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_journey_plans[] = $raw['object']->raw_id;
                $approve_need_journey_plans_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $journey_plans_array = array();
        if (is_object($journey_plans)) {
            foreach ($journey_plans as $key => $journey_plans1) {
                if (in_array($journey_plans[$key]->id, $approve_need_journey_plans)) {
                    $journey_plans[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_journey_plans_detail_object_id[$journey_plans[$key]->id])) {
                        $journey_plans[$key]->objectid = $approve_need_journey_plans_detail_object_id[$journey_plans[$key]->id];
                    } else {
                        $journey_plans[$key]->objectid = '';
                    }
                } else {
                    $journey_plans[$key]->need_to_approve = 'no';
                    $journey_plans[$key]->objectid = '';
                }

                if ($journey_plans[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($journey_plans[$key]->id, $approve_need_journey_plans)) {
                    $journey_plans_array[] = $journey_plans[$key];
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
                if (isset($journey_plans_array[$offset])) {
                    $data_array[] = $journey_plans_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($journey_plans_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($journey_plans_array);
        } else {
            $data_array = $journey_plans_array;
        }
        return prepareResult(true, $data_array, [], "Journey plan listing", $this->success, $pagination);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Journey Plan", $this->unprocessableEntity);
        }

        if ($request->plan_type == 2) {
            if (is_array($request->weeks) && sizeof($request->weeks) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one week.", $this->unprocessableEntity);
            }
        }

        if (is_array($request->days) && sizeof($request->days) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one day.", $this->unprocessableEntity);
        }

        if (is_array($request->customers) && sizeof($request->customers) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Journey Plan', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Journey Plan',$request);
            }

            $journey_plans = new JourneyPlan;
            $journey_plans->route_id = $request->route_id;
            $journey_plans->is_merchandiser = $request->is_merchandiser;
            $journey_plans->merchandiser_id = $request->merchandiser_id;
            $journey_plans->name = $request->name;
            $journey_plans->description = $request->description;
            $journey_plans->start_date = $request->start_date;
            $journey_plans->no_end_date = $request->no_end_date;

            if ($request->no_end_date == 0) {
                $journey_plans->end_date = $request->end_date;
            }

            $journey_plans->start_time = $request->start_time;
            $journey_plans->end_time = $request->end_time;
            $journey_plans->start_day_of_the_week = $request->start_day_of_the_week;
            $journey_plans->plan_type = $request->plan_type;
            $journey_plans->is_enforce = $request->is_enforce;
            $journey_plans->current_stage = $current_stage;

            if ($request->plan_type == 2) {
                $journey_plans->week_1 = $request->weeks['week_1'];
                $journey_plans->week_2 = $request->weeks['week_2'];
                $journey_plans->week_3 = $request->weeks['week_3'];
                $journey_plans->week_4 = $request->weeks['week_4'];
                $journey_plans->week_5 = $request->weeks['week_5'];
            }

            $journey_plans->save();

            if ($isActivate = checkWorkFlowRule('Journey Plan', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Journey Plan', $request, $journey_plans->id);
            }

            if ($journey_plans->plan_type == 2) {
                foreach ($request->customers as $key => $days) {
                    $journey_plans_weeks = new JourneyPlanWeek;
                    $journey_plans_weeks->journey_plan_id = $journey_plans->id;
                    $journey_plans_weeks->week_number = $key;
                    $journey_plans_weeks->save();
                    foreach ($days as $dkey => $day) {
                        $journey_plans_days = new JourneyPlanDay;
                        $journey_plans_days->journey_plan_id = $journey_plans->id;
                        $journey_plans_days->journey_plan_week_id = $journey_plans_weeks->id;
                        $journey_plans_days->day_name = $day['day_name'];
                        $journey_plans_days->day_number = $day['day_number'];
                        $journey_plans_days->save();
                        foreach ($day['customers'] as $ckey => $customer) {
                            $journey_plans_customers = new JourneyPlanCustomer;
                            $journey_plans_customers->journey_plan_id = $journey_plans->id;
                            $journey_plans_customers->journey_plan_day_id = $journey_plans_days->id;
                            $journey_plans_customers->customer_id = $customer['customer_id'];
                            $journey_plans_customers->day_customer_sequence = $customer['day_customer_sequence'];
                            $journey_plans_customers->day_start_time = $customer['day_start_time'];
                            $journey_plans_customers->day_end_time = $customer['day_end_time'];
                            $journey_plans_customers->save();
                        }
                    }
                }
            } else {
                foreach ($request->customers as $key => $day) {
                    $journey_plans_days = new JourneyPlanDay;
                    $journey_plans_days->journey_plan_id = $journey_plans->id;
                    $journey_plans_days->day_name = $day['day_name'];
                    $journey_plans_days->day_number = $day['day_number'];
                    $journey_plans_days->save();
                    foreach ($day['customers'] as $ckey => $customer) {
                        $journey_plans_customers = new JourneyPlanCustomer;
                        $journey_plans_customers->journey_plan_day_id = $journey_plans_days->id;
                        $journey_plans_customers->journey_plan_id = $journey_plans->id;
                        $journey_plans_customers->customer_id = $customer['customer_id'];
                        $journey_plans_customers->day_customer_sequence = $customer['day_customer_sequence'];
                        $journey_plans_customers->day_start_time = $customer['day_start_time'];
                        $journey_plans_customers->day_end_time = $customer['day_end_time'];
                        $journey_plans_customers->save();
                    }
                }
            }

            \DB::commit();

            $journey_plans->getSaveData();

            return prepareResult(true, $journey_plans, [], "Journey Plans added successfully", $this->success);
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
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */


    public function show($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Journey plan", $this->unauthorized);
        }

        $checkJPPlanType = JourneyPlan::select('plan_type')->where('uuid', $uuid)->first();

        if (is_object($checkJPPlanType)) {
            if ($checkJPPlanType->plan_type == 1) {
                $journey_plan = JourneyPlan::where('uuid', $uuid)->with(
                    'route:id,organisation_id,uuid,area_id,route_code,route_name,status',
                    'journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
                    'journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
                    'journeyPlanDays.journeyPlanCustomers.customerInfo:id,user_id,customer_code',
                    'journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
                )->first();
            } else {
                $journey_plan = JourneyPlan::where('uuid', $uuid)->with(
                    'route:id,organisation_id,uuid,area_id,route_code,route_name,status',
                    'journeyPlanWeeks:id,journey_plan_id,uuid,week_number',
                    'journeyPlanWeeks.journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo:id,user_id,customer_code',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
                )
                    ->first();
            }
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $journey_plan, [], "Journey Plan show", $this->success);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Journey plan", $this->unauthorized);
        }

        $checkJPPlanType = JourneyPlan::select('plan_type')->where('uuid', $uuid)->first();

        if ($checkJPPlanType->plan_type == 1) {
            $journey_plan = JourneyPlan::where('uuid', $uuid)->with(
                'route:id,organisation_id,uuid,area_id,route_code,route_name,status',
                'journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
                'journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
                'journeyPlanDays.journeyPlanCustomers.customerInfo:id,user_id,customer_code',
                'journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
            )->first();
        } else {
            $journey_plan = JourneyPlan::where('uuid', $uuid)->with(
                'route:id,organisation_id,uuid,area_id,route_code,route_name,status',
                'journeyPlanWeeks:id,journey_plan_id,uuid,week_number',
                'journeyPlanWeeks.journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
                'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
                'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo:id,user_id,customer_code',
                'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
            )
                ->first();
        }

        if (!is_object($journey_plan)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $journey_plan, [], "Journey Plan Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Journey Plan", $this->unprocessableEntity);
        }

        if ($request->plan_type == 2) {
            if (is_array($request->weeks) && sizeof($request->weeks) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one week.", $this->unprocessableEntity);
            }
        }

        if (is_array($request->days) && sizeof($request->days) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one day.", $this->unprocessableEntity);
        }

        if (is_array($request->customers) && sizeof($request->customers) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Journey Plan', 'edit', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Journey Plan',$request);
            }

            $journey_plans = JourneyPlan::where('uuid', $uuid)->first();

            $journey_plans->route_id = $request->route_id;
            $journey_plans->is_merchandiser = $request->is_merchandiser;
            $journey_plans->merchandiser_id = $request->merchandiser_id;
            $journey_plans->name = $request->name;
            $journey_plans->description = $request->description;
            $journey_plans->start_date = $request->start_date;
            $journey_plans->no_end_date = $request->no_end_date;
            if (!$request->no_end_date) {
                $journey_plans->end_date = $request->end_date;
            }
            $journey_plans->start_time = $request->start_time;
            $journey_plans->end_time = $request->end_time;
            $journey_plans->start_day_of_the_week = $request->start_day_of_the_week;
            $journey_plans->plan_type = $request->plan_type;
            $journey_plans->is_enforce = $request->is_enforce;
            $journey_plans->current_stage = $current_stage;
            if ($request->plan_type == 2) {
                $journey_plans->week_1 = $request->weeks['week_1'];
                $journey_plans->week_2 = $request->weeks['week_2'];
                $journey_plans->week_3 = $request->weeks['week_3'];
                $journey_plans->week_4 = $request->weeks['week_4'];
                $journey_plans->week_5 = $request->weeks['week_5'];
            }
            $journey_plans->save();

            if ($isActivate = checkWorkFlowRule('Journey Plan', 'edit', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Journey Plan', $request, $journey_plans->id);
            }

            if ($journey_plans->plan_type == 2) {
                JourneyPlanWeek::where('journey_plan_id', $journey_plans->id)->delete();
                JourneyPlanDay::where('journey_plan_id', $journey_plans->id)->delete();
                JourneyPlanCustomer::where('journey_plan_id', $journey_plans->id)->delete();

                foreach ($request->customers as $key => $days) {
                    $journey_plans_weeks = new JourneyPlanWeek;
                    $journey_plans_weeks->journey_plan_id = $journey_plans->id;
                    $journey_plans_weeks->week_number = $key;
                    $journey_plans_weeks->save();
                    foreach ($days as $dkey => $day) {
                        $journey_plans_days = new JourneyPlanDay;
                        $journey_plans_days->journey_plan_id = $journey_plans->id;
                        $journey_plans_days->journey_plan_week_id = $journey_plans_weeks->id;
                        $journey_plans_days->day_name = $day['day_name'];
                        $journey_plans_days->day_number = $day['day_number'];
                        $journey_plans_days->save();
                        foreach ($day['customers'] as $ckey => $customer) {
                            $journey_plans_customers = new JourneyPlanCustomer;
                            $journey_plans_customers->journey_plan_id = $journey_plans->id;
                            $journey_plans_customers->journey_plan_day_id = $journey_plans_days->id;
                            $journey_plans_customers->customer_id = $customer['customer_id'];
                            $journey_plans_customers->day_customer_sequence = $customer['day_customer_sequence'];
                            $journey_plans_customers->day_start_time = $customer['day_start_time'];
                            $journey_plans_customers->day_end_time = $customer['day_end_time'];
                            $journey_plans_customers->save();
                        }
                    }
                }
            } else {
                JourneyPlanDay::where('journey_plan_id', $journey_plans->id)->delete();
                JourneyPlanCustomer::where('journey_plan_id', $journey_plans->id)->delete();

                foreach ($request->customers as $key => $day) {
                    $journey_plans_days = new JourneyPlanDay;
                    $journey_plans_days->journey_plan_id = $journey_plans->id;
                    $journey_plans_days->day_name = $day['day_name'];
                    $journey_plans_days->day_number = $day['day_number'];
                    $journey_plans_days->save();
                    foreach ($day['customers'] as $ckey => $customer) {
                        $journey_plans_customers = new JourneyPlanCustomer;
                        $journey_plans_customers->journey_plan_day_id = $journey_plans_days->id;
                        $journey_plans_customers->journey_plan_id = $journey_plans->id;
                        $journey_plans_customers->customer_id = $customer['customer_id'];
                        $journey_plans_customers->day_customer_sequence = $customer['day_customer_sequence'];
                        $journey_plans_customers->day_start_time = $customer['day_start_time'];
                        $journey_plans_customers->day_end_time = $customer['day_end_time'];
                        $journey_plans_customers->save();
                    }
                }
            }

            \DB::commit();
            $journey_plans->getSaveData();
            return prepareResult(true, $journey_plans, [], "Journey Plans added successfully", $this->success);
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
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Journey Plan", $this->unauthorized);
        }

        $journey_plan = JourneyPlan::where('uuid', $uuid)
            ->first();

        if (is_object($journey_plan)) {
            $journey_plan->delete();

            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                // 'route_id' => 'required|integer|exists:routes,id',
                'name' => 'required',
                'start_date' => 'required',
                'no_end_date' => 'required',
                // 'start_time' => 'required',
                // 'end_time' => 'required',
                'start_day_of_the_week' => 'required|integer',
                'plan_type' => 'required|integer',
            ]);
        }
        if ($type == "routePlan") {
            $validator = \Validator::make($input, [
                'route_id' => 'required|integer|exists:routes,id'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function showRoute($id, Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating Journey plan", $this->unauthorized);
        }

        $checkJPPlanType = JourneyPlan::select('plan_type')
            ->where('route_id', $id)
            ->first();

        if ($checkJPPlanType) {
            if ($checkJPPlanType->plan_type == 1) {
                $journey_plan = JourneyPlan::where('route_id', $id)->with(
                    'route:id,organisation_id,uuid,area_id,route_code,route_name,status',
                    'journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
                    'journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
                )->first();
            } else {
                $journey_plan = JourneyPlan::where('route_id', $id)->with(
                    'route:id,organisation_id,uuid,area_id,route_code,route_name,status',
                    'journeyPlanWeeks:id,journey_plan_id,uuid,week_number',
                    'journeyPlanWeeks.journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo',
                    'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
                )
                    ->first();
            }

            if (!is_object($journey_plan)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            return prepareResult(true, $journey_plan, [], "Journey Plan show", $this->success);
        } else {

            return prepareResult(true, [], [], "Journey Plan show", $this->success);
        }
    }

    public function journeyPlanByMerchandise($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating Journey plan", $this->unauthorized);
        }

        $journey_plan = JourneyPlan::with(
            'merchandiser:id,firstname,lastname',
            'journeyPlanWeeks:id,journey_plan_id,uuid,week_number',
            'journeyPlanWeeks.journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
            'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
            'journeyPlanDays:id,journey_plan_id,uuid,journey_plan_week_id,day_number,day_name',
            'journeyPlanDays.journeyPlanCustomers:id,journey_plan_day_id,customer_id,day_customer_sequence,day_start_time,day_end_time',
            'journeyPlanDays.journeyPlanCustomers.customerInfo',
            'journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname',
            'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo',
            'journeyPlanWeeks.journeyPlanDays.journeyPlanCustomers.customerInfo.user:id,firstname,lastname'
        )
            ->where('merchandiser_id', $merchandiser_id)
            ->where('start_date', '<=', date('Y-m-d'))
            // ->where('current_stage', 'Approved')
            ->where(function ($q) {
                $q->whereDate('end_date', '>=', date('Y-m-d'))
                    ->orWhereNull('end_date');
            })
            ->orderBy('id', 'desc')
            ->first();

        return prepareResult(true, $journey_plan, [], "Journey Plan listing", $this->success);
    }

    public function imports(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'journeyplan_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate Journey plan import", $this->unauthorized);
        }

        Excel::import(new JourneyPlanImport, request()->file('journeyplan_file'));
        return prepareResult(true, [], [], "Journey plan successfully imported", $this->success);
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array(
            "Journey Name", "Desc", "Start Date", "End Date", "Start Time", "End Time", "Day Wise", "Week Wise", "First Day Of Week", "Enforce Flag", "Merchandiser", "Customer", "Week1 Sunday", "Week1 Sunday Start Time", "Week1 Sunday End Time", "Week1 Monday", "Week1 Monday Start Time", "Week1 Monday End Time", "Week1 Tuesday", "Week1 Tuesday Start Time", "Week1 Tuesday End Time", "Week1 Wednesday", "Week1 Wednesday Start Time", "Week1 Wednesday End Time", "Week1 Thrusday",
            "Week1 Thrusday Start Time", "Week1 Thrusday End Time", "Week1 Friday", "Week1 Friday Start Time", "Week1 Friday End Time", "Week1 Saturday", "Week1 Saturday Start Time", "Week1 Saturday End Time", "Week2 Sunday", "Week2 Sunday Start Time", "Week2 Sunday End Time", "Week2 Monday", "Week2 Monday Start Time", "Week2 Monday End Time", "Week2 Tuesday", "Week2 Tuesday Start Time", "Week2 Tuesday End Time", "Week2 Wednesday", "Week2 Wednesday Start Time", "Week2 Wednesday End Time", "Week2 Thrusday", "Week2 Thrusday Start Time", "Week2 Thrusday End Time",
            "Week2 Friday", "Week2 Friday Start Time", "Week2 Friday End Time", "Week2 Saturday", "Week2 Saturday Start Time", "Week2 Saturday End Time", "Week3 Sunday", "Week3 Sunday Start Time", "Week3 Sunday End Time", "Week3 Monday", "Week3 Monday Start Time", "Week3 Monday End Time", "Week3 Tuesday", "Week3 Tuesday Start Time", "Week3 Tuesday End Time", "Week3 Wednesday", "Week3 Wednesday Start Time", "Week3 Wednesday End Time", "Week3 Thrusday", "Week3 Thrusday Start Time", "Week3 Thrusday End Time", "Week3 Friday", "Week3 Friday Start Time",
            "Week3 Friday End Time", "Week3 Saturday", "Week3 Saturday Start Time", "Week3 Saturday End Time", "Week4 Sunday", "Week4 Sunday Start Time", "Week4 Sunday End Time", "Week4 Monday", "Week4 Monday Start Time", "Week4 Monday End Time", "Week4 Tuesday", "Week4 Tuesday Start Time", "Week4 Tuesday End Time", "Week4 Wednesday", "Week4 Wednesday Start Time", "Week4 Wednesday End Time", "Week4 Thrusday", "Week4 Thrusday Start Time", "Week4 Thrusday End Time", "Week4 Friday", "Week4 Friday Start Time", "Week4 Friday End Time", "Week4 Saturday",
            "Week4 Saturday Start Time", "Week4 Saturday End Time", "Week5 Sunday", "Week5 Sunday Start Time", "Week5 Sunday End Time", "Week5 Monday", "Week5 Monday Start Time", "Week5 Monday End Time", "Week5 Tuesday", "Week5 Tuesday Start Time", "Week5 Tuesday End Time", "Week5 Wednesday", "Week5 Wednesday Start Time", "Week5 Wednesday End Time", "Week5 Thrusday", "Week5 Thrusday Start Time", "Week5 Thrusday End Time", "Week5 Friday", "Week5 Friday Start Time", "Week5 Friday End Time", "Week5 Saturday", "Week5 Saturday Start Time", "Week5 Saturday End Time"
        );

        return prepareResult(true, $mappingarray, [], "journey plan Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'journeyplan_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate region import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('journeyplan_file')->store('import');
            $filename = storage_path("app/" . $file);
            $fp = fopen($filename, "r");
            $content = fread($fp, filesize($filename));
            $lines = explode("\n", $content);
            $heading_array_line = isset($lines[1]) ? $lines[1] : '';
            $heading_array = explode(",", trim($heading_array_line));
            fclose($fp);

            // echo "<pre>";
            // print_r($lines);
            // print_r($heading_array);
            // print_r($map_key_value_array);
            // exit;
            if (!$heading_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            if (!$map_key_value_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }


            $import = new JourneyPlanImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

            $succussrecords = 0;
            $successfileids = 0;
            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());
                $data = json_encode($import->successAllRecords());
                $fileName = time() . '_datafile.txt';
                File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();
                $successfileids = $importtempfiles->id;
            }
            $errorrecords = 0;
            $errror_array = array();
            if ($import->failures()) {

                foreach ($import->failures() as $failure_key => $failure) {
                    //echo $failure_key.'--------'.$failure->row().'||';
                    //print_r($failure);
                    if ($failure->row() != 1) {
                        $failure->row(); // row that went wrong
                        $failure->attribute(); // either heading key (if using heading row concern) or column index
                        $failure->errors(); // Actual error messages from Laravel validator
                        $failure->values(); // The values of the row that has failed.
                        //print_r($failure->errors());

                        $error_msg = isset($failure->errors()[0]) ? $failure->errors()[0] : '';
                        if ($error_msg != "") {
                            //$errror_array['errormessage'][] = array("There was an error on row ".$failure->row().". ".$error_msg);
                            //$errror_array['errorresult'][] = $failure->values();
                            $error_result = array();
                            $error_row_loop = 0;
                            foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
                                $error_result[$map_key_value_array_value] = isset($failure->values()[$error_row_loop]) ? $failure->values()[$error_row_loop] : '';
                                $error_row_loop++;
                            }
                            $errror_array[] = array(
                                'errormessage' => "There was an error on row " . $failure->row() . ". " . $error_msg,
                                'errorresult' => $error_result, //$failure->values(),
                                //'attribute' => $failure->attribute(),//$failure->values(),
                                //'error_result' => $error_result,
                                //'map_key_value_array' => $map_key_value_array,
                            );
                        }
                    }
                }
                $errorrecords = count($errror_array);
            }

            $errors = $errror_array;
            $result['successrecordscount'] = $succussrecords;
            $result['errorrcount'] = $errorrecords;
            $result['successfileids'] = $successfileids;
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                if ($failure->row() != 1) {
                    info($failure->row());
                    info($failure->attribute());
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                    $errors[] = $failure->errors();
                }
            }

            return prepareResult(true, [], $errors, "Failed to validate bank import", $this->success);
        }
        return prepareResult(true, $result, $errors, "Journey Plan successfully imported", $this->success);
    }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        $skipduplicate = $request->skipduplicate;

        //$skipduplicate = 1 means skip the data
        //$skipduplicate = 0 means overwrite the data

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);
            $current_organisation_id = request()->user()->organisation_id;
            if ($finaldata) :
                foreach ($finaldata as $row) :
                    if ($skipduplicate == 1) {

                        $customer = CustomerInfo::where('customer_code', $row[11])->first();
                        $merchandiser = SalesmanInfo::where('salesman_code', $row[10])->first();
                        $journeyPlan = JourneyPlan::where('merchandiser_id', $merchandiser->user_id)
                            ->whereDate('created_at', date('Y-m-d'))
                            ->first();

                        if (!is_object($journeyPlan)) {
                            $journeyPlan = new JourneyPlan;
                        }
                        \DB::beginTransaction();
                        try {
                            if (!is_object($merchandiser) or !is_object($customer)) {
                                if (!is_object($merchandiser)) {
                                    return prepareResult(false, [], [], "merchandiser not exists", $this->unauthorized);
                                }
                                if (!is_object($customer)) {
                                    return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                                }
                            }

                            $save = true;
                            if (isset($journeyPlan->id) && $journeyPlan->id) {
                                $save = false;
                            }
                            // $journeyPlan = new JourneyPlan;
                            $journeyPlan->organisation_id = $current_organisation_id;
                            $journeyPlan->name = $row[0];
                            $journeyPlan->description = $row[1];
                            $journeyPlan->start_date = date('Y-m-d', strtotime($row[2]));

                            if (isset($row[3]) and $row[3] != "") {
                                $journeyPlan->end_date = date('Y-m-d', strtotime($row[3]));
                                $journeyPlan->no_end_date = 0;
                            } else {
                                $journeyPlan->no_end_date = 1;
                            }

                            if ($row[9] == 'No') {
                                $journeyPlan->is_enforce = 0;
                            } else {
                                $journeyPlan->is_enforce = 1;
                            }

                            if (is_object($merchandiser)) {
                                $journeyPlan->is_merchandiser = 1;
                                $journeyPlan->merchandiser_id = (is_object($merchandiser)) ? $merchandiser->user_id : 0;
                            } else {
                                $journeyPlan->merchandiser_id = Null;
                                $journeyPlan->is_merchandiser = 0;
                            }
                            if ($row[6] == "Yes") {
                                $planType = 1;
                                $dayNumber = 0;
                                if ($row[8] == "Monday") {
                                    $dayNumber = 1;
                                } else if ($row[8] == "Tuesday") {
                                    $dayNumber = 2;
                                } else if ($row[8] == "Wednesday") {
                                    $dayNumber = 3;
                                } else if ($row[8] == "Thursday") {
                                    $dayNumber = 4;
                                } else if ($row[8] == "Friday") {
                                    $dayNumber = 5;
                                } else if ($row[8] == "Saturday") {
                                    $dayNumber = 6;
                                } else if ($row[8] == "Sunday") {
                                    $dayNumber = 7;
                                }
                                $journeyPlan->start_day_of_the_week = $dayNumber;
                            } else if ($row[7] == "Yes") {
                                $planType = 2;
                            }
                            $journeyPlan->plan_type = $planType;
                            $weekArray = [];
                            $monthArray = [];
                            $count = 0;
                            if ($planType == 2) {
                                if (isset($row[12]) and $row[12] != "") {
                                    $journeyPlan->week_1 = 1;
                                    $weekArray[$count]['week'] = "week1";
                                    $weekArray[$count]['column'] = 12;
                                    $count++;
                                }
                                if (isset($row[33]) and $row[33] != "") {
                                    $journeyPlan->week_2 = 1;
                                    $weekArray[$count]['week'] = "week2";
                                    $weekArray[$count]['column'] = 33;
                                    $count++;
                                }
                                if (isset($row[54]) and $row[54] != "") {
                                    $journeyPlan->week_3 = 1;
                                    $weekArray[$count]['week'] = "week3";
                                    $weekArray[$count]['column'] = 54;
                                    $count++;
                                }
                                if (isset($row[75]) and $row[75] != "") {
                                    $journeyPlan->week_4 = 1;
                                    $weekArray[$count]['week'] = "week4";
                                    $weekArray[$count]['column'] = 75;
                                    $count++;
                                }
                                if (isset($row[96]) and $row[96] != "") {
                                    $journeyPlan->week_5 = 1;
                                    $weekArray[$count]['week'] = "week5";
                                    $weekArray[$count]['column'] = 96;
                                }
                            }
                            if ($planType == 1) {
                                if (
                                    (isset($row[12]) and $row[12] != "") ||
                                    (isset($row[33]) and $row[33] != "") ||
                                    (isset($row[54]) and $row[54] != "") ||
                                    (isset($row[75]) and $row[75] != "") ||
                                    (isset($row[96]) and $row[96] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 1;
                                    $monthArray[$count]['day_name'] = "Monday";
                                    if (isset($row[12]) and $row[12] != "") {
                                        $monthArray[$count]['column'] = 12;
                                    } else if ((isset($row[33]) and $row[33] != "")) {
                                        $monthArray[$count]['column'] = 33;
                                    } else if ((isset($row[54]) and $row[54] != "")) {
                                        $monthArray[$count]['column'] = 54;
                                    } else if ((isset($row[75]) and $row[75] != "")) {
                                        $monthArray[$count]['column'] = 75;
                                    } else if ((isset($row[96]) and $row[96] != "")) {
                                        $monthArray[$count]['column'] = 96;
                                    }
                                    $count++;
                                }
                                if (
                                    (isset($row[15]) and $row[15] != "") ||
                                    (isset($row[36]) and $row[36] != "") ||
                                    (isset($row[57]) and $row[57] != "") ||
                                    (isset($row[78]) and $row[78] != "") ||
                                    (isset($row[99]) and $row[99] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 2;
                                    $monthArray[$count]['day_name'] = "Tuesday";
                                    if (isset($row[15]) and $row[15] != "") {
                                        $monthArray[$count]['column'] = 15;
                                    } else if ((isset($row[36]) and $row[36] != "")) {
                                        $monthArray[$count]['column'] = 36;
                                    } else if ((isset($row[57]) and $row[57] != "")) {
                                        $monthArray[$count]['column'] = 57;
                                    } else if ((isset($row[78]) and $row[78] != "")) {
                                        $monthArray[$count]['column'] = 78;
                                    } else if ((isset($row[99]) and $row[99] != "")) {
                                        $monthArray[$count]['column'] = 99;
                                    }
                                    $count++;
                                }
                                if (
                                    (isset($row[18]) and $row[18] != "") ||
                                    (isset($row[39]) and $row[39] != "") ||
                                    (isset($row[60]) and $row[60] != "") ||
                                    (isset($row[81]) and $row[81] != "") ||
                                    (isset($row[102]) and $row[102] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 3;
                                    $monthArray[$count]['day_name'] = "Wednesday";
                                    if (isset($row[18]) and $row[18] != "") {
                                        $monthArray[$count]['column'] = 18;
                                    } else if ((isset($row[39]) and $row[39] != "")) {
                                        $monthArray[$count]['column'] = 39;
                                    } else if ((isset($row[60]) and $row[60] != "")) {
                                        $monthArray[$count]['column'] = 60;
                                    } else if ((isset($row[81]) and $row[81] != "")) {
                                        $monthArray[$count]['column'] = 81;
                                    } else if ((isset($row[102]) and $row[102] != "")) {
                                        $monthArray[$count]['column'] = 102;
                                    }
                                    $count++;
                                }
                                if (
                                    (isset($row[21]) and $row[21] != "") ||
                                    (isset($row[42]) and $row[42] != "") ||
                                    (isset($row[63]) and $row[63] != "") ||
                                    (isset($row[84]) and $row[84] != "") ||
                                    (isset($row[105]) and $row[105] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 4;
                                    $monthArray[$count]['day_name'] = "Thursday";
                                    if (isset($row[21]) and $row[21] != "") {
                                        $monthArray[$count]['column'] = 21;
                                    } else if ((isset($row[42]) and $row[42] != "")) {
                                        $monthArray[$count]['column'] = 42;
                                    } else if ((isset($row[63]) and $row[63] != "")) {
                                        $monthArray[$count]['column'] = 63;
                                    } else if ((isset($row[84]) and $row[84] != "")) {
                                        $monthArray[$count]['column'] = 84;
                                    } else if ((isset($row[105]) and $row[152] != "")) {
                                        $monthArray[$count]['column'] = 105;
                                    }
                                    $count++;
                                }
                                if (
                                    (isset($row[24]) and $row[24] != "") ||
                                    (isset($row[45]) and $row[45] != "") ||
                                    (isset($row[65]) and $row[65] != "") ||
                                    (isset($row[87]) and $row[87] != "") ||
                                    (isset($row[108]) and $row[108] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 5;
                                    $monthArray[$count]['day_name'] = "Friday";
                                    if (isset($row[24]) and $row[24] != "") {
                                        $monthArray[$count]['column'] = 24;
                                    } else if ((isset($row[45]) and $row[45] != "")) {
                                        $monthArray[$count]['column'] = 45;
                                    } else if ((isset($row[65]) and $row[65] != "")) {
                                        $monthArray[$count]['column'] = 65;
                                    } else if ((isset($row[87]) and $row[87] != "")) {
                                        $monthArray[$count]['column'] = 87;
                                    } else if ((isset($row[108]) and $row[1082] != "")) {
                                        $monthArray[$count]['column'] = 108;
                                    }
                                    $count++;
                                }
                                if (
                                    (isset($row[27]) and $row[27] != "") ||
                                    (isset($row[48]) and $row[48] != "") ||
                                    (isset($row[68]) and $row[68] != "") ||
                                    (isset($row[91]) and $row[91] != "") ||
                                    (isset($row[111]) and $row[111] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 6;
                                    $monthArray[$count]['day_name'] = "Saturday";
                                    if (isset($row[27]) and $row[27] != "") {
                                        $monthArray[$count]['column'] = 27;
                                    } else if ((isset($row[48]) and $row[48] != "")) {
                                        $monthArray[$count]['column'] = 48;
                                    } else if ((isset($row[68]) and $row[68] != "")) {
                                        $monthArray[$count]['column'] = 68;
                                    } else if ((isset($row[91]) and $row[91] != "")) {
                                        $monthArray[$count]['column'] = 91;
                                    } else if ((isset($row[111]) and $row[11182] != "")) {
                                        $monthArray[$count]['column'] = 111;
                                    }
                                    $count++;
                                }
                                if (
                                    (isset($row[30]) and $row[30] != "") ||
                                    (isset($row[51]) and $row[51] != "") ||
                                    (isset($row[71]) and $row[71] != "") ||
                                    (isset($row[94]) and $row[94] != "") ||
                                    (isset($row[114]) and $row[114] != "")
                                ) {
                                    $monthArray[$count]['day_number'] = 7;
                                    $monthArray[$count]['day_name'] = "Sunday";
                                    if (isset($row[30]) and $row[30] != "") {
                                        $monthArray[$count]['column'] = 30;
                                    } else if ((isset($row[51]) and $row[51] != "")) {
                                        $monthArray[$count]['column'] = 51;
                                    } else if ((isset($row[71]) and $row[71] != "")) {
                                        $monthArray[$count]['column'] = 71;
                                    } else if ((isset($row[94]) and $row[94] != "")) {
                                        $monthArray[$count]['column'] = 94;
                                    } else if ((isset($row[114]) and $row[114] != "")) {
                                        $monthArray[$count]['column'] = 114;
                                    }
                                    $count++;
                                }
                            }
                            if ($save == true) {
                                $journeyPlan->save();
                            }

                            $journey_plan_months_ids = [];
                            foreach ($monthArray as $key => $monthData) {

                                $journey_plan_days = new JourneyPlanDay;
                                $journey_plan_days->journey_plan_id = $journeyPlan->id;
                                $journey_plan_days->journey_plan_week_id = NULL;
                                $journey_plan_days->day_number = $monthData['day_number'];
                                $journey_plan_days->day_name = $monthData['day_name'];
                                $journey_plan_days->save();

                                $start_time = "10:00";
                                $end_time = "06:00";

                                if ($row[$monthData['column']] == "Yes") {
                                    $start_time = $row[$monthData['column'] + 1];
                                    $end_time = $row[$monthData['column'] + 2];
                                }

                                $journey_plan_customer = $this->savePlanCustomer(
                                    $journeyPlan->id,
                                    $journey_plan_days->id,
                                    (is_object($customer)) ? $customer->id : 0,
                                    $key + 1,
                                    $start_time,
                                    $end_time
                                );
                            }

                            $journey_plan_weeks_ids = [];
                            foreach ($weekArray as $key => $weekData) {
                                $journey_plan_weeks = new JourneyPlanWeek;
                                $journey_plan_weeks->journey_plan_id = $journeyPlan->id;
                                $journey_plan_weeks->week_number = $weekData['week'];
                                $journey_plan_weeks->save();

                                $journey_plan_weeks_ids[$key]['journey_id'] = $journeyPlan->id;
                                $journey_plan_weeks_ids[$key]['week_id'] = $journey_plan_weeks->id;
                                $journey_plan_weeks_ids[$key]['column'] = $weekData['column'];
                            }

                            foreach ($journey_plan_weeks_ids as $key => $journeyPlanWeek) {
                                $startColumn = $journeyPlanWeek['column'];
                                if ($row[$startColumn] == "Yes") {

                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 1, "Sunday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 1, $row[$startColumn + 1], $row[$startColumn + 2]);

                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 1;
                                    // $journey_plan_days->day_name = "Sunday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 1;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 1];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 2];
                                    // $journey_plan_customer->save();
                                }
                                if ($row[$startColumn + 3] == "Yes") {
                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 2, "Monday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 2, $row[$startColumn + 4], $row[$startColumn + 5]);

                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 2;
                                    // $journey_plan_days->day_name = "Monday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 2;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 4];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 5];
                                    // $journey_plan_customer->save();
                                }
                                if ($row[$startColumn + 6] == "Yes") {
                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 3;
                                    // $journey_plan_days->day_name = "Tuesday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 3;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 7];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 8];
                                    // $journey_plan_customer->save();

                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 3, "Tuesday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 3, $row[$startColumn + 7], $row[$startColumn + 8]);
                                }
                                if ($row[$startColumn + 9] == "Yes") {

                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 4, "Wednesday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 4, $row[$startColumn + 10], $row[$startColumn + 11]);

                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 4;
                                    // $journey_plan_days->day_name = "Wednesday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 4;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 10];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 11];
                                    // $journey_plan_customer->save();
                                }
                                if ($row[$startColumn + 12] == "Yes") {

                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 5, "Thursday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 5, $row[$startColumn + 13], $row[$startColumn + 14]);

                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 5;
                                    // $journey_plan_days->day_name = "Thursday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 5;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 13];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 14];
                                    // $journey_plan_customer->save();
                                }
                                if ($row[$startColumn + 15] == "Yes") {
                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 6, "Friday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 6, $row[$startColumn + 16], $row[$startColumn + 17]);

                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 6;
                                    // $journey_plan_days->day_name = "Friday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 6;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 16];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 17];
                                    // $journey_plan_customer->save();
                                }
                                if ($row[$startColumn + 18] == "Yes") {
                                    $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 7, "Saturday");

                                    $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 6, $row[$startColumn + 19], $row[$startColumn + 20]);

                                    // $journey_plan_days = new JourneyPlanDay;
                                    // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                                    // $journey_plan_days->day_number = 7;
                                    // $journey_plan_days->day_name = "Saturday";
                                    // $journey_plan_days->save();

                                    // $journey_plan_customer = new JourneyPlanCustomer;
                                    // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                                    // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                                    // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                                    // $journey_plan_customer->day_customer_sequence = 6;
                                    // $journey_plan_customer->day_start_time = $row[$startColumn + 19];
                                    // $journey_plan_customer->day_end_time = $row[$startColumn + 20];
                                    // $journey_plan_customer->save();
                                }
                            }

                            \DB::commit();
                        } catch (\Exception $exception) {
                            \DB::rollback();
                            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        } catch (\Throwable $exception) {
                            \DB::rollback();
                            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    } else {
                        $customer = CustomerInfo::where('customer_code', $row[11])->first();
                        $merchandiser = SalesmanInfo::where('salesman_code', $row[10])->first();
                        $journeyPlan = JourneyPlan::where('merchandiser_id', $merchandiser->user_id)->first();

                        if (is_object($journeyPlan)) {
                            pre($journeyPlan->name, false);
                            pre('if', false);
                        } else {
                            pre('else', false);
                        }
                    }


                // if (is_object($journeyPlan)) {
                //     \DB::beginTransaction();
                //     try {
                //         if (!is_object($merchandiser) or !is_object($customer)) {
                //             if (!is_object($merchandiser)) {
                //                 return prepareResult(false, [], [], "merchandiser not exists", $this->unauthorized);
                //             }
                //             if (!is_object($customer)) {
                //                 return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                //             }
                //         }
                //         $journeyPlan->name = $row[0];
                //         $journeyPlan->description = $row[1];
                //         $journeyPlan->start_date = Carbon::createFromFormat('d/m/Y', $row[2])->format('Y-m-d');

                //         if (isset($row[3]) and $row[3] != "") {
                //             $journeyPlan->end_date = Carbon::createFromFormat('d/m/Y', $row[3])->format('Y-m-d');
                //             $journeyPlan->no_end_date = 0;
                //         } else {
                //             $journeyPlan->no_end_date = 1;
                //         }
                //         $journeyPlan->is_enforce = $row[9];
                //         $journeyPlan->merchandiser_id = (is_object($merchandiser)) ? $merchandiser->user_id : 0;

                //         if ($row[6] == "Yes") {
                //             $planType = 1;
                //             $dayNumber = 0;
                //             if ($row[8] == "Monday") {
                //                 $dayNumber = 1;
                //             } else if ($row[8] == "Tuesday") {
                //                 $dayNumber = 2;
                //             } else if ($row[8] == "Wednesday") {
                //                 $dayNumber = 3;
                //             } else if ($row[8] == "Thursday") {
                //                 $dayNumber = 4;
                //             } else if ($row[8] == "Friday") {
                //                 $dayNumber = 5;
                //             } else if ($row[8] == "Saturday") {
                //                 $dayNumber = 6;
                //             } else if ($row[8] == "Sunday") {
                //                 $dayNumber = 7;
                //             }
                //             $journeyPlan->start_day_of_the_week = $dayNumber;
                //         } else if ($row[7] == "Yes") {
                //             $planType = 2;
                //         } else {
                //             $planType = 1;
                //         }
                //         $journeyPlan->plan_type = $planType;
                //         $weekArray = [];
                //         $count = 0;
                //         if ($planType == 2) {
                //             if (isset($row[12]) and $row[12] != "") {
                //                 $journeyPlan->week_1 = 1;
                //                 $weekArray[$count]['week'] = "week1";
                //                 $weekArray[$count]['column'] = 12;
                //                 $count++;
                //             }
                //             if (isset($row[33]) and $row[33] != "") {
                //                 $journeyPlan->week_2 = 1;
                //                 $weekArray[$count]['week'] = "week2";
                //                 $weekArray[$count]['column'] = 33;
                //                 $count++;
                //             }
                //             if (isset($row[54]) and $row[54] != "") {
                //                 $journeyPlan->week_3 = 1;
                //                 $weekArray[$count]['week'] = "week3";
                //                 $weekArray[$count]['column'] = 54;
                //                 $count++;
                //             }
                //             if (isset($row[75]) and $row[75] != "") {
                //                 $journeyPlan->week_4 = 1;
                //                 $weekArray[$count]['week'] = "week4";
                //                 $weekArray[$count]['column'] = 75;
                //                 $count++;
                //             }
                //             if (isset($row[96]) and $row[96] != "") {
                //                 $journeyPlan->week_5 = 1;
                //                 $weekArray[$count]['week'] = "week5";
                //                 $weekArray[$count]['column'] = 96;
                //             }
                //         }
                //         $journeyPlan->save();

                //         $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //         $preData->delete();
                //         $journey_plan_weeks_ids = [];
                //         foreach ($weekArray as $key => $weekData) {
                //             $journey_plan_weeks = new JourneyPlanWeek;
                //             $journey_plan_weeks->journey_plan_id = $journeyPlan->id;
                //             $journey_plan_weeks->week_number = $weekData['week'];
                //             $journey_plan_weeks->save();

                //             $journey_plan_weeks_ids[$key]['journey_id'] = $journeyPlan->id;
                //             $journey_plan_weeks_ids[$key]['week_id'] = $journey_plan_weeks->id;
                //             $journey_plan_weeks_ids[$key]['column'] = $weekData['column'];
                //         }

                //         foreach ($journey_plan_weeks_ids as $key => $journeyPlanWeek) {
                //             $startColumn = $journeyPlanWeek['column'];
                //             if ($row[$startColumn] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 1, "Sunday");

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 1, $row[$startColumn + 1], $row[$startColumn + 2]);
                //             }
                //             if ($row[$startColumn + 3] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 2, "Monday");

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 2, $row[$startColumn + 4], $row[$startColumn + 5]);

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 2;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 4];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 5];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 6] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 3, "Tuesday");

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 3;
                //                 // $journey_plan_days->day_name = "Tuesday";
                //                 // $journey_plan_days->save();

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 3, $row[$startColumn + 7], $row[$startColumn + 8]);

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 3;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 7];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 8];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 9] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 4, "Wednesday");

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 4;
                //                 // $journey_plan_days->day_name = "Wednesday";
                //                 // $journey_plan_days->save();

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 4, $row[$startColumn + 10], $row[$startColumn + 11]);

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 4;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 10];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 11];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 12] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 5, "Thursday");

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 5;
                //                 // $journey_plan_days->day_name = "Thursday";
                //                 // $journey_plan_days->save();

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 5, $row[$startColumn + 13], $row[$startColumn + 14]);

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 5;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 13];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 14];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 15] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 6, "Friday");

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 6;
                //                 // $journey_plan_days->day_name = "Friday";
                //                 // $journey_plan_days->save();

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 6, $row[$startColumn + 16], $row[$startColumn + 17]);

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 6;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 16];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 17];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 18] == "Yes") {
                //                 $preData = JourneyPlanWeek::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 7, "Saturday");

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 7;
                //                 // $journey_plan_days->day_name = "Saturday";
                //                 // $journey_plan_days->save();

                //                 $preData = JourneyPlanCustomer::where('journey_plan_id', $journeyPlan->id);
                //                 $preData->delete();

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 6, $row[$startColumn + 19], $row[$startColumn + 20]);

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 6;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 19];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 20];
                //                 // $journey_plan_customer->save();
                //             }
                //         }

                //         \DB::commit();
                //     } catch (\Exception $exception) {
                //         \DB::rollback();
                //         return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                //     } catch (\Throwable $exception) {
                //         \DB::rollback();
                //         return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                //     }
                // } else {
                //     \DB::beginTransaction();
                //     try {
                //         if (!is_object($merchandiser) or !is_object($customer)) {
                //             if (!is_object($merchandiser)) {
                //                 return prepareResult(false, [], [], "merchandiser not exists", $this->unauthorized);
                //             }
                //             if (!is_object($customer)) {
                //                 return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                //             }
                //         }
                //         $journeyPlan = new JourneyPlan;
                //         $journeyPlan->organisation_id = $current_organisation_id;
                //         $journeyPlan->name = $row[0];
                //         $journeyPlan->description = $row[1];
                //         $journeyPlan->start_date = Carbon::createFromFormat('d/m/Y', $row[2])->format('Y-m-d');

                //         if (isset($row[3]) and $row[3] != "") {
                //             $journeyPlan->end_date = Carbon::createFromFormat('d/m/Y', $row[3])->format('Y-m-d');
                //         }
                //         $journeyPlan->is_enforce = $row[9];
                //         $journeyPlan->merchandiser_id = (is_object($merchandiser)) ? $merchandiser->user_id : 0;

                //         if ($row[6] == "Yes") {
                //             $planType = 1;
                //             $dayNumber = 0;
                //             if ($row[8] == "Monday") {
                //                 $dayNumber = 1;
                //             } else if ($row[8] == "Tuesday") {
                //                 $dayNumber = 2;
                //             } else if ($row[8] == "Wednesday") {
                //                 $dayNumber = 3;
                //             } else if ($row[8] == "Thursday") {
                //                 $dayNumber = 4;
                //             } else if ($row[8] == "Friday") {
                //                 $dayNumber = 5;
                //             } else if ($row[8] == "Saturday") {
                //                 $dayNumber = 6;
                //             } else if ($row[8] == "Sunday") {
                //                 $dayNumber = 7;
                //             }
                //             $journeyPlan->start_day_of_the_week = $dayNumber;
                //         } else if ($row[7] == "Yes") {
                //             $planType = 2;
                //         }
                //         $journeyPlan->plan_type = $planType;
                //         $weekArray = [];
                //         $count = 0;
                //         if ($planType == 2) {
                //             if (isset($row[12]) and $row[12] != "") {
                //                 $journeyPlan->week_1 = 1;
                //                 $weekArray[$count]['week'] = "week1";
                //                 $weekArray[$count]['column'] = 12;
                //                 $count++;
                //             }
                //             if (isset($row[33]) and $row[33] != "") {
                //                 $journeyPlan->week_2 = 1;
                //                 $weekArray[$count]['week'] = "week2";
                //                 $weekArray[$count]['column'] = 33;
                //                 $count++;
                //             }
                //             if (isset($row[54]) and $row[54] != "") {
                //                 $journeyPlan->week_3 = 1;
                //                 $weekArray[$count]['week'] = "week3";
                //                 $weekArray[$count]['column'] = 54;
                //                 $count++;
                //             }
                //             if (isset($row[75]) and $row[75] != "") {
                //                 $journeyPlan->week_4 = 1;
                //                 $weekArray[$count]['week'] = "week4";
                //                 $weekArray[$count]['column'] = 75;
                //                 $count++;
                //             }
                //             if (isset($row[96]) and $row[96] != "") {
                //                 $journeyPlan->week_5 = 1;
                //                 $weekArray[$count]['week'] = "week5";
                //                 $weekArray[$count]['column'] = 96;
                //             }
                //         }
                //         $journeyPlan->save();

                //         $journey_plan_weeks_ids = [];

                //         foreach ($weekArray as $key => $weekData) {
                //             $journey_plan_weeks = new JourneyPlanWeek;
                //             $journey_plan_weeks->journey_plan_id = $journeyPlan->id;
                //             $journey_plan_weeks->week_number = $weekData['week'];
                //             $journey_plan_weeks->save();

                //             $journey_plan_weeks_ids[$key]['journey_id'] = $journeyPlan->id;
                //             $journey_plan_weeks_ids[$key]['week_id'] = $journey_plan_weeks->id;
                //             $journey_plan_weeks_ids[$key]['column'] = $weekData['column'];
                //         }

                //         foreach ($journey_plan_weeks_ids as $key => $journeyPlanWeek) {
                //             $startColumn = $journeyPlanWeek['column'];
                //             if ($row[$startColumn] == "Yes") {

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 1, "Sunday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 1, $row[$startColumn + 1], $row[$startColumn + 2]);

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 1;
                //                 // $journey_plan_days->day_name = "Sunday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 1;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 1];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 2];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 3] == "Yes") {
                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 2, "Monday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 2, $row[$startColumn + 4], $row[$startColumn + 5]);

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 2;
                //                 // $journey_plan_days->day_name = "Monday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 2;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 4];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 5];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 6] == "Yes") {
                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 3;
                //                 // $journey_plan_days->day_name = "Tuesday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 3;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 7];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 8];
                //                 // $journey_plan_customer->save();

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 3, "Tuesday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 3, $row[$startColumn + 7], $row[$startColumn + 8]);
                //             }
                //             if ($row[$startColumn + 9] == "Yes") {

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 4, "Wednesday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 4, $row[$startColumn + 10], $row[$startColumn + 11]);

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 4;
                //                 // $journey_plan_days->day_name = "Wednesday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 4;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 10];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 11];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 12] == "Yes") {

                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 5, "Thursday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 5, $row[$startColumn + 13], $row[$startColumn + 14]);

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 5;
                //                 // $journey_plan_days->day_name = "Thursday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 5;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 13];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 14];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 15] == "Yes") {
                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 6, "Friday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 6, $row[$startColumn + 16], $row[$startColumn + 17]);

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 6;
                //                 // $journey_plan_days->day_name = "Friday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 6;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 16];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 17];
                //                 // $journey_plan_customer->save();
                //             }
                //             if ($row[$startColumn + 18] == "Yes") {
                //                 $journey_plan_days = $this->savePlanDay($journeyPlanWeek['journey_id'], $journeyPlanWeek['week_id'], 7, "Saturday");

                //                 $journey_plan_customer = $this->savePlanCustomer($journeyPlanWeek['journey_id'], $journey_plan_days->id, (is_object($customer)) ? $customer->id : 0, 6, $row[$startColumn + 19], $row[$startColumn + 20]);

                //                 // $journey_plan_days = new JourneyPlanDay;
                //                 // $journey_plan_days->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_days->journey_plan_week_id = $journeyPlanWeek['week_id'];
                //                 // $journey_plan_days->day_number = 7;
                //                 // $journey_plan_days->day_name = "Saturday";
                //                 // $journey_plan_days->save();

                //                 // $journey_plan_customer = new JourneyPlanCustomer;
                //                 // $journey_plan_customer->journey_plan_id = $journeyPlanWeek['journey_id'];
                //                 // $journey_plan_customer->journey_plan_day_id = $journey_plan_days->id;
                //                 // $journey_plan_customer->customer_id = (is_object($customer)) ? $customer->id : 0;
                //                 // $journey_plan_customer->day_customer_sequence = 6;
                //                 // $journey_plan_customer->day_start_time = $row[$startColumn + 19];
                //                 // $journey_plan_customer->day_end_time = $row[$startColumn + 20];
                //                 // $journey_plan_customer->save();
                //             }
                //         }

                //         \DB::commit();
                //     } catch (\Exception $exception) {
                //         \DB::rollback();
                //         return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                //     } catch (\Throwable $exception) {
                //         \DB::rollback();
                //         return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                //     }
                // }
                endforeach;
            // unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            // \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "journey plan successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    private function savePlanDay($journey_id, $week_id, $day_number, $day_name)
    {
        $journey_plan_days = new JourneyPlanDay;
        $journey_plan_days->journey_plan_id = $journey_id;
        $journey_plan_days->journey_plan_week_id = $week_id;
        $journey_plan_days->day_number = $day_number;
        $journey_plan_days->day_name = $day_name;
        $journey_plan_days->save();

        return $journey_plan_days;
    }

    private function savePlanCustomer($journey_id, $journey_plan_days_id, $customer_id, $customer_sequence, $start_time, $end_time)
    {
        $journey_plan_customer = new JourneyPlanCustomer;
        $journey_plan_customer->journey_plan_id = $journey_id;
        $journey_plan_customer->journey_plan_day_id = $journey_plan_days_id;
        $journey_plan_customer->customer_id = $customer_id;
        $journey_plan_customer->day_customer_sequence = $customer_sequence;
        $journey_plan_customer->day_start_time = $start_time;
        $journey_plan_customer->day_end_time = $end_time;
        $journey_plan_customer->save();

        return $journey_plan_customer;
    }
}
