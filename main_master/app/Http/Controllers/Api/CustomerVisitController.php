<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CustomerVisit;
use App\Model\User;
use App\Model\SalesmanTripInfos;

class CustomerVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->journey_plan_id) {
            return prepareResult(false, [], [], "Error while validating Customer visit.", $this->unprocessableEntity);
        }

        $CustomerVisit_query = CustomerVisit::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'trip',
                'customer:id,firstname,lastname',
                'customer.customerinfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmaninfo:id,user_id,salesman_code',
                'route:id,route_code,route_name',
                'journeyPlan:id,name'
            )
            ->where('journey_plan_id', $request->journey_plan_id);

        if ($request->start_date && $request->end_date) {
            $CustomerVisit = $CustomerVisit_query->whereBetween('date', [$request->start_date, $request->end_date])->orderBy('id', 'desc')->get();
        } else if ($request->all) {
            $CustomerVisit = $CustomerVisit_query->orderBy('id', 'desc')->get();
        } else {
            $CustomerVisit = $CustomerVisit_query->whereBetween('date', [date('Y-m-d'), date('Y-m-d')])->orderBy('id', 'desc')->get();
        }

        $CustomerVisit_array = array();
        if (is_object($CustomerVisit)) {
            foreach ($CustomerVisit as $key => $CustomerVisit1) {
                $CustomerVisit_array[] = $CustomerVisit[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($CustomerVisit_array[$offset])) {
                    $data_array[] = $CustomerVisit_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($CustomerVisit_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($CustomerVisit_array);
        } else {
            $data_array = $CustomerVisit_array;
        }
        return prepareResult(true, $data_array, [], "Customer Visit listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer Visit", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $customervisit = new CustomerVisit;
            $customervisit->trip_id         = (!empty($request->trip_id)) ? $request->trip_id : null;
            $customervisit->route_id         = (!empty($request->route_id)) ? $request->route_id : null;
            $customervisit->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $customervisit->salesman_id            = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $customervisit->journey_plan_id            = (!empty($request->journey_plan_id)) ? $request->journey_plan_id : null;
            $customervisit->latitude            = (!empty($request->latitude)) ? $request->latitude : null;
            $customervisit->longitude       = (!empty($request->longitude)) ? $request->longitude : null;
            $customervisit->shop_status        = (!empty($request->shop_status)) ? $request->shop_status : null;
            $customervisit->reason        = $request->reason;
            $customervisit->comment        = $request->comment;
            $customervisit->start_time          = (!empty($request->start_time)) ? $request->start_time : null;
            $customervisit->is_sequnece          = $request->is_sequnece;
            $customervisit->end_time          = (!empty($request->end_time)) ? $request->end_time : null;
            $customervisit->total_task          = (!empty($request->total_task)) ? $request->total_task : 0;
            $customervisit->completed_task          = (!empty($request->completed_task)) ? $request->completed_task : 0;
            $customervisit->date       = date('Y-m-d', strtotime($request->date));
            $customervisit->added_on       = date('Y-m-d H:i:s');
            $customervisit->visit_total_time       = timeCalculate($request->start_time, $request->end_time);

            $customervisit->save();

            $salesman_info = new SalesmanTripInfos;
            $salesman_info->trips_id       = $customervisit->trip_id;
            $salesman_info->salesman_id    = $customervisit->salesman_id;
            $salesman_info->status         = 3;
            $salesman_info->save();
            
            \DB::commit();

            $customervisit->getSaveData();

            return prepareResult(true, $customervisit, [], "Customer Visit added successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating Customer visit.", $this->unprocessableEntity);
        }

        $CustomerVisit = CustomerVisit::with(array('customerInfo.user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'trip',
                'customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'route:id,route_code,route_name',
                'journeyPlan:id,name'
            )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($CustomerVisit)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $CustomerVisit, [], "Customer Visit Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer visit.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $customervisit = CustomerVisit::where('uuid', $uuid)->first();
            $customervisit->route_id         = (!empty($request->route_id)) ? $request->route_id : null;
            $customervisit->trip_id         = (!empty($request->trip_id)) ? $request->trip_id : null;
            $customervisit->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $customervisit->salesman_id            = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $customervisit->journey_plan_id            = (!empty($request->journey_plan_id)) ? $request->journey_plan_id : null;
            $customervisit->latitude            = (!empty($request->latitude)) ? $request->latitude : null;
            $customervisit->longitude       = (!empty($request->longitude)) ? $request->longitude : null;
            $customervisit->shop_status        = (!empty($request->shop_status)) ? $request->shop_status : null;
            $customervisit->reason        = $request->reason;
            $customervisit->comment        = $request->comment;
            $customervisit->start_time          = (!empty($request->start_time)) ? $request->start_time : null;
            $customervisit->is_sequnece          = $request->is_sequnece;
            $customervisit->end_time          = (!empty($request->end_time)) ? $request->end_time : null;
            $customervisit->total_task          = (!empty($request->total_task)) ? $request->total_task : 0;
            $customervisit->completed_task          = (!empty($request->completed_task)) ? $request->completed_task : 0;
            $customervisit->date       = date('Y-m-d', strtotime($request->date));
            $customervisit->added_on       = date('Y-m-d H:i:s');
            $customervisit->visit_total_time       = timeCalculate($request->start_time, $request->end_time);
            $customervisit->save();


            \DB::commit();

            $customervisit->getSaveData();

            return prepareResult(true, $customervisit, [], "Customer visit updated successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating customer visit.", $this->unprocessableEntity);
        }

        $CustomerVisit = CustomerVisit::where('uuid', $uuid)
            ->first();

        if (is_object($CustomerVisit)) {
            $CustomerVisit->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating invoice", $this->unprocessableEntity);
        }

        $action = $request->action;
        $uuids = $request->customervisit_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $CustomerVisit = CustomerVisit::where('uuid', $uuid)
                    ->first();
                if (is_object($CustomerVisit)) {
                    $CustomerVisit->delete();
                }
            }
            $CustomerVisit = $this->index();
            return prepareResult(true, $CustomerVisit, [], "Customer Visit deleted success", $this->success);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                // 'route_id' => 'required|integer|exists:routes,id',
                'journey_plan_id' => 'required|integer|exists:journey_plans,id',
                'trip_id' => 'required|integer|exists:trips,id',
                'customer_id' => 'required',
                'salesman_id' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
                'shop_status' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'is_sequnece' => 'required',
                'date' => 'required|date'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'customervisit_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function activityBySalesman(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->salesman_id) {
            return prepareResult(false, [], [], "Error while validating Customer visit.", $this->unprocessableEntity);
        }

        $CustomerVisit_query = CustomerVisit::with(array('customer' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'customerActivity:id,customer_visit_id,customer_id,activity_name,activity_action,start_time,end_time',
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'route:id,route_code,route_name',
                'trip'
            )
            ->where('salesman_id', $request->salesman_id);

        if ($request->start_date && $request->end_date) {
            $CustomerVisit = $CustomerVisit_query->whereBetween('date', [$request->start_date, $request->end_date])->orderBy('id', 'desc')->get();
        } else if ($request->all) {
            $CustomerVisit = $CustomerVisit_query->orderBy('id', 'desc')->get();
        } else if ($request->date) {
            $CustomerVisit = $CustomerVisit_query->whereDate('date', date('Y-m-d', strtotime($request->date)))->orderBy('id', 'desc')->get();
        } else {
            $CustomerVisit = $CustomerVisit_query->whereBetween('date', [date('Y-m-d', strtotime("-7 day")), date('Y-m-d')])->orderBy('id', 'desc')->get();
        }

        $CustomerVisit_array = array();
        if (is_object($CustomerVisit)) {
            foreach ($CustomerVisit as $key => $CustomerVisit1) {
                $CustomerVisit_array[] = $CustomerVisit[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($CustomerVisit_array[$offset])) {
                    $data_array[] = $CustomerVisit_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($CustomerVisit_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($CustomerVisit_array);
        } else {
            $data_array = $CustomerVisit_array;
        }
        return prepareResult(true, $data_array, [], "Customer Visit listing", $this->success, $pagination);
    }
}
