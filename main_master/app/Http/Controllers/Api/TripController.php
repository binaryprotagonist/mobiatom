<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Trip;
use App\Model\SalesmanTripInfos;

class TripController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function beginday(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating trip", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $start_trip = explode(" ", $request->trip_start);
            $end_trip = explode(" ", $request->trip_end);

            $trip = new Trip;
            $trip->route_id         = (!empty($request->route_id)) ? $request->route_id : null;
            $trip->salesman_id            = (!empty($request->salesman_id)) ? $request->salesman_id : null;
            $trip->trip_start            = date('Y-m-d H:i:s', strtotime($request->trip_start));

            $trip->trip_start_date            = date('Y-m-d', strtotime(current($start_trip)));
            $trip->trip_start_time            = date('H:i:s', strtotime(end($start_trip)));

            // $trip->trip_end            = date('Y-m-d H:i:s', strtotime($request->trip_end));
            // $trip->trip_end_date            = date('Y-m-d', strtotime(current($end_trip)));
            // $trip->trip_end_time            = date('H:i:s', strtotime(end($end_trip)));

            $trip->trip_status       = (!empty($request->trip_status)) ? $request->trip_status : null;
            $trip->trip_from        = (!empty($request->trip_from)) ? $request->trip_from : null;
            $trip->save();

            $salesman_info = new SalesmanTripInfos;
            $salesman_info->trips_id       = $trip->id;
            $salesman_info->salesman_id    = $trip->salesman_id;
            $salesman_info->status         = 1;
            $salesman_info->save();


            \DB::commit();
            return prepareResult(true, $trip, [], "Trip added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function endday(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "end");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating trip", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $trip = Trip::where('salesman_id', $request->salesman_id)->where('trip_status', 1)->orderBy('id', 'desc')->first();

            $start_trip = explode(" ", $request->trip_start);
            $end_trip = explode(" ", $request->trip_end);

            // $trip = new Trip;
            $trip->route_id         = (!empty($request->route_id)) ? $request->route_id : null;
            $trip->salesman_id            = (!empty($request->salesman_id)) ? $request->salesman_id : null;

            // $trip->trip_start            = date('Y-m-d H:i:s', strtotime($request->trip_start));
            // $trip->trip_start_date            = date('Y-m-d', strtotime(current($start_trip)));
            // $trip->trip_start_time            = date('H:i:s', strtotime(end($start_trip)));

            $trip->trip_end            = date('Y-m-d H:i:s', strtotime($request->trip_end));
            $trip->trip_end_date            = date('Y-m-d', strtotime(current($end_trip)));
            $trip->trip_end_time            = date('H:i:s', strtotime(end($end_trip)));

            $trip->trip_status       = (!empty($request->trip_status)) ? $request->trip_status : null;
            $trip->trip_from        = (!empty($request->trip_from)) ? $request->trip_from : null;
            $trip->save();

            $salesman_info = new SalesmanTripInfos;
            $salesman_info->trips_id       = $trip->id;
            $salesman_info->salesman_id    = $trip->salesman_id;
            $salesman_info->status         = 5;
            $salesman_info->save();


            \DB::commit();
            return prepareResult(true, $trip, [], "Trip added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                // 'route_id' => 'required|integer|exists:routes,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'trip_start' => 'required',
                // 'trip_end' => 'required',
                'trip_status' => 'required',
                'trip_from' => 'required',
            ]);
        }

        if ($type == "end") {
            $validator = \Validator::make($input, [
                // 'trip_id' => 'required|integer|exists:trips,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'trip_end' => 'required',
                'trip_status' => 'required',
                'trip_from' => 'required',
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }


        return ["error" => $error, "errors" => $errors];
    }
}
