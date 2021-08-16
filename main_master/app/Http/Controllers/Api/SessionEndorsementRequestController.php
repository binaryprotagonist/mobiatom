<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DeviceDetail;
use App\Model\SessionEndorsement;
use App\Model\SessionEndorsementRequest;
use Illuminate\Http\Request;

class SessionEndorsementRequestController extends Controller
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

        $supervisor_id = $request->user()->id;

        $session_endorsement_request = SessionEndorsementRequest::select('id', 'uuid', 'salesman_id', 'supervisor_id', 'route_id', 'trip_id', 'status', 'created_at')
            ->with(
                'salesman:id,firstname,lastname',
                'supervisor:id,firstname,lastname',
                'route:id,route_name',
                'trip',
            )
            ->where('supervisor_id', $supervisor_id)
            ->where('status', '!=', "Approved")
            ->get();

        $session_endorsement_request_array = array();
        if (is_object($session_endorsement_request)) {
            foreach ($session_endorsement_request as $key => $session_endorsement_request1) {
                $session_endorsement_request_array[] = $session_endorsement_request[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($session_endorsement_request_array[$offset])) {
                    $data_array[] = $session_endorsement_request_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($session_endorsement_request_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($session_endorsement_request_array);
        } else {
            $data_array = $session_endorsement_request_array;
        }

        return prepareResult(true, $data_array, [], "Session Endorsement Request listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function salesmanIndex(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $salesman_id = $request->user()->id;

        $session_endorsement_request = SessionEndorsementRequest::select('id', 'uuid', 'salesman_id', 'supervisor_id', 'route_id', 'trip_id', 'status')
            ->with(
                'salesman:id,firstname,lastname',
                'supervisor:id,firstname,lastname',
                'route:id,route_name',
                'trip'
            )
            ->where('salesman_id', $salesman_id)
            ->get();

        $session_endorsement_request_array = array();
        if (is_object($session_endorsement_request)) {
            foreach ($session_endorsement_request as $key => $session_endorsement_request1) {
                $session_endorsement_request_array[] = $session_endorsement_request[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($session_endorsement_request_array[$offset])) {
                    $data_array[] = $session_endorsement_request_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($session_endorsement_request_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($session_endorsement_request_array);
        } else {
            $data_array = $session_endorsement_request_array;
        }

        return prepareResult(true, $data_array, [], "Session Endorsement Request listing", $this->success, $pagination);
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

        \DB::beginTransaction();
        try {

            $check = SessionEndorsementRequest::where('trip_id', $request->trip_id)
                ->where('salesman_id', $request->salesman_id)
                ->whereDate('created_at', date('Y-m-d'))
                ->first();

            if (!is_object($check)) {
                $session_endorsement_request = new SessionEndorsementRequest;
                $session_endorsement_request->route_id = $request->route_id;
                $session_endorsement_request->salesman_id = $request->salesman_id;
                $session_endorsement_request->supervisor_id = $request->supervisor_id;
                $session_endorsement_request->trip_id = $request->trip_id;
                $session_endorsement_request->status = "Pending";
                $session_endorsement_request->save();

                $dataNofi = array(
                    'message' => "Salesman " . getUserName($request->salesman_id) . " request for session endrosement",
                    'title' => "Session Endorsement",
                    'noti_type' => "session_endorsement",
                    "uuid" => $session_endorsement_request->uuid
                );

                $device_detail = DeviceDetail::where('user_id', $session_endorsement_request->supervisor_id)
                    ->orderBy('id', 'desc')
                    ->first();

                if (is_object($device_detail)) {
                    $t = $device_detail->device_token;
                    sendNotificationAndroid($dataNofi, $t);
                }
                $d = array(
                    'uuid' => $session_endorsement_request->uuid,
                    'user_id' => $session_endorsement_request->supervisor_id,
                    'type' => 'Session Endorsement',
                    'message' => "Salesman " . getUserName($request->salesman_id) . " request for session endrosement",
                    'status' => 1
                );

                saveNotificaiton($d);

                \DB::commit();
                return prepareResult(true, $session_endorsement_request, [], "Session Endorsement Request successfully", $this->success);
            } else {
                return prepareResult(true, [], [], "You have already request for session endorsement", $this->success);
            }
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Session Endorsement Approval the specified session endorsement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\SessionEndorsement  $sessionEndorsement
     * @return \Illuminate\Http\Response
     */
    public function sessionEndorsementApproval(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $uuid = $request->uuid;
        $status = $request->status;

        $ser = SessionEndorsementRequest::where('uuid', $uuid)->first();
        if (!is_object($ser)) {
            return prepareResult(false, [], [], "Session Endorsement not found.", $this->unauthorized);
        }

        $ser->status = $status;
        $ser->save();

        $se = new SessionEndorsement;
        $se->salesman_id = $ser->salesman_id;
        $se->supervisor_id = $ser->supervisor_id;
        $se->route_id = $ser->route_id;
        $se->trip_id = $ser->trip_id;
        $se->date = date('Y-m-d');
        $se->status = $ser->status;
        $se->save();

        $dataNofi = array(
            'message' => "Supervisor " . getUserName($ser->supervisor_id) . " is " . $ser->status . " your request for session endrosement",
            'title' => "Session Endorsement",
            'noti_type' => "session_endorsement",
            "uuid" => $ser->uuid
        );

        $device_detail = DeviceDetail::where('user_id', $ser->salesman_id)->orderBy('id', 'desc')->first();
        if (is_object($device_detail)) {
            $t = $device_detail->device_token;
            sendNotificationAndroid($dataNofi, $t);
        }

        $d = array(
            'uuid' => $uuid,
            'user_id' => $ser->salesman_id,
            'type' => 'Session Endorsement',
            'message' => "Supervisor " . getUserName($ser->salesman_id) . " is " . $ser->status . " your request for session endrosement",
            'status' => 1
        );

        saveNotificaiton($d);

        return prepareResult(true, $ser, [], "Supervisor Request Approval successfully", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\SessionEndorsementRequest  $sessionEndorsementRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\SessionEndorsementRequest  $sessionEndorsementRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        //
    }
}
