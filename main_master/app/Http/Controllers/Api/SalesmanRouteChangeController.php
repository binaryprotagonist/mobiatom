<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DeviceDetail;
use App\Model\SalesmanRouteChangeApproval;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesmanRouteChangeController extends Controller
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

        $route = SalesmanRouteChangeApproval::with(
            'salesman:id,firstname,lastname',
            'customer:id,firstname,lastname',
            'supervisor:id,firstname,lastname',
            'journeyPlan:id,name'
        )
            ->orderBy('id', 'desc')
            ->get();

        $route_array = array();
        if (is_object($route)) {
            foreach ($route as $key => $route1) {
                $route_array[] = $route[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($route_array[$offset])) {
                    $data_array[] = $route_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($route_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($route_array);
        } else {
            $data_array = $route_array;
        }

        return prepareResult(true, $data_array, [], "Route listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Salesman route change approval", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $srca = new SalesmanRouteChangeApproval;
            $srca->route_id = $request->route_id;
            $srca->salesman_id = $request->salesman_id;
            $srca->customer_id = $request->customer_id;
            $srca->journey_plan_id = $request->journey_plan_id;
            $srca->supervisor_id = $request->supervisor_id;
            $srca->requested_date = date('Y-m-d');
            $srca->route_approval = "Pending";
            $srca->reason = $request->reason;
            $srca->save();

            $salesman = User::find($request->salesman_id);
            $customer = User::find($request->customer_id);

            $dataNofi = array(
                'message' =>  $salesman->getName() . " Requested for route diversion",
                'title' => "Route Diversion",
                'noti_type' => "route_diversion",
                "uuid" => $srca->uuid
            );

            $salesmanInfo = $salesman->salesmanInfo;

            $supervisor = $salesmanInfo->salesman_supervisor;

            $device_detail = DeviceDetail::where('user_id', $supervisor)->get();
            $device_detail->each(function ($token, $key) use ($dataNofi) {
                $t = $token->device_token;
                sendNotificationAndroid($dataNofi, $t);
            });

            $d = array(
                'uuid' => $srca->uuid,
                'user_id' => $supervisor,
                'type' => 'Route Diversion',
                'message' => $salesman->getName() . " is Requested for route diversion.",
                'status' => 1
            );
            saveNotificaiton($d);

            \DB::commit();
            return prepareResult(true, $srca, [], "Salesman route approval added successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function approval($uuid, Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "approval");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Salesman route change approval", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $srca = SalesmanRouteChangeApproval::where('uuid', $uuid)->first();
            $srca->route_approval = $request->route_approval;
            $srca->approval_date = date('Y-m-d');
            $srca->save();

            $salesman = User::find($srca->salesman_id);
            $supervisor = User::find($srca->supervisor_id);

            // Send Notification
            $dataNofi = array(
                'message' =>  $supervisor->getName() . " is " . $request->route_approval . " your request for route diversion",
                'title' => "Route Diversion",
                'noti_type' => "route_diversion",
                "uuid" => $srca->uuid
            );

            $device_detail = DeviceDetail::where('user_id', $salesman->id)->get();
            $device_detail->each(function ($token, $key) use ($dataNofi) {
                $t = $token->device_token;
                sendNotificationAndroid($dataNofi, $t);
            });

            $d = array(
                'uuid' => $srca->uuid,
                'user_id' => $salesman->id,
                'type' => 'Route Diversion',
                'message' => $supervisor->getName() . " is " . $request->route_approval . " your request for route diversion",
                'status' => 1
            );
            saveNotificaiton($d);

            \DB::commit();
            return prepareResult(true, $srca, [], "Salesman route approval added successfully", $this->success);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = Validator::make($input, [
                'route_id' => 'required|integer|exists:routes,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'customer_id' => 'required|integer|exists:users,id',
                'journey_plan_id' => 'required|integer|exists:journey_plans,id',
                'supervisor_id' => 'required|integer|exists:users,id',
                'reason' => 'required'
            ]);
        }

        if ($type == "approval") {
            $validator = Validator::make($input, [
                'route_approval' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
