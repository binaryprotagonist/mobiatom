<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DeviceDetail;
use App\Model\Notifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
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

        $notificaiton = request()->user()->notifications()->orderBy('id', 'asc')->get();

        $notificaiton_array = array();
        if (is_object($notificaiton)) {
            foreach ($notificaiton as $key => $notificaiton1) {
                $notificaiton_array[] = $notificaiton[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($notificaiton_array[$offset])) {
                    $data_array[] = $notificaiton_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($notificaiton_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($notificaiton_array);
            $pagination['status_count'] = $this->getActiveStatusCount()[1];
            $pagination['unread_count'] = $this->getunReadCount()[1];
        } else {
            $data_array = $notificaiton_array;
        }

        // return prepareResult(true, $notificaiton, [], "Notificaiton listing", $this->success);
        return prepareResult(true, $data_array, [], "Notificaiton listing", $this->success, $pagination);
    }

    private function getActiveStatusCount()
    {
        $status_count = request()->user()->notifications()->where('status', 1)->get()->count();
        return array('status_count', $status_count);
    }

    private function getunReadCount()
    {
        $read_count = request()->user()->notifications()->where('is_read', 1)->get()->count();
        return array('read_count', $read_count);
    }

    public function statusChange()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $user_id = request()->user()->id;

        $noti = Notifications::where('user_id', $user_id)
            ->where('status', 1)
            ->first();

        if (is_object($noti)) {
            $noti->status = 0;
            $noti->save();
        }

        $data = collect($this->getActiveStatusCount(), $this->getunReadCount());

        return prepareResult(true, $data, [], "Notificaiton status view", $this->success);
    }

    public function notificationRead($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $user_id = request()->user()->id;

        $noti = Notifications::where('id', $id)
            ->where('user_id', $user_id)
            ->where('is_read', 1)
            ->first();

        if (is_object($noti)) {
            $noti->is_read = 0;
            $noti->save();
        }

        $data = collect($this->getActiveStatusCount(), $this->getunReadCount());

        return prepareResult(true, $data, [], "Notificaiton read", $this->success);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $dataNofi = array(
            'message' => "Your Load Request ERO12345 is approved by user name.",
            'title' => "Load Request",
            'noti_type' => "load_request",
            "uuid" => "41211aa0-86f8-11eb-b7ce-1987983e9125"
        );

        $device_detail = DeviceDetail::where('user_id', 814)->get();
        foreach ($device_detail as $key => $detail) {
        }
        $t = $detail->device_token;

        // $test = send_notification_FCM("dp4Hys8RR9mYNeSVKwEDr4:APA91bHRJhY4FgCerkyYS8LhJGtlfDONifsRZnGaA9IMmZxrZf_bt9tJyC1Vi5PLH_RCYRqJyVjOyqJ5jl3fV9Pbgtrk7M3QEP4g6EaeQM_rdan0P_VNGo4IF1-z9EVNeFZWIscQhbA5", "Load Request approved by Mustufa mahmud", "Load Request Approved, Load Number is LOAD12345", 19);

        $test = sendNotificationAndroid($dataNofi, "dp4Hys8RR9mYNeSVKwEDr4:APA91bHRJhY4FgCerkyYS8LhJGtlfDONifsRZnGaA9IMmZxrZf_bt9tJyC1Vi5PLH_RCYRqJyVjOyqJ5jl3fV9Pbgtrk7M3QEP4g6EaeQM_rdan0P_VNGo4IF1-z9EVNeFZWIscQhbA5");
        // $test = sendNotification();
        // pre($test);

        $d = array(
            814,
            null,
            'Load Request',
            "Load Request Approved, Load Number is LOAD12345",
            1
        );
        saveNotificaiton($d);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}
