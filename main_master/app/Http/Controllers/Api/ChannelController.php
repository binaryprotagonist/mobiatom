<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Channel;

class ChannelController extends Controller
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

        $channel = Channel::select('id', 'uuid', 'organisation_id', 'name', 'node_level', 'status')
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->get();

        $channel_array = array();
        if (is_object($channel)) {
            foreach ($channel as $key => $channel1) {
                $channel_array[] = $channel[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($channel_array[$offset])) {
                    $data_array[] = $channel_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($channel_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($channel_array);
        } else {
            $data_array = $channel_array;
        }

        return prepareResult(true, $data_array, [], "Channel listing", $this->success, $pagination);
    }

    /**
     * Edit the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $channel = Channel::where('uuid', $uuid)
            ->select('id', 'uuid', 'name', 'node_level', 'status')
            ->with('children')
            ->first();

        if (is_object($channel)) {
            return prepareResult(true, $channel, [], "Channel edit successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
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
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating channel", $this->unprocessableEntity);
        }

        $channel = new Channel;
        $channel->parent_id = $request->parent_id;
        $channel->name = $request->name;
        $channel->node_level = $request->node_level;
        $channel->status = $request->status;
        $channel->save();

        if (is_object($channel)) {
            $channel->children;
            return prepareResult(true, $channel, [], "Channel added successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
    }

    /**
     * Update a created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating channel", $this->unprocessableEntity);
        }

        $channel = Channel::where('uuid', $uuid)
            ->first();

        if (!is_object($channel)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }
        $channel->parent_id = $request->parent_id;
        $channel->name = $request->name;
        $channel->node_level = $request->node_level;
        $channel->status = $request->status;
        $channel->save();

        $channel->children;

        return prepareResult(true, $channel, [], "Channel updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating channel", $this->unauthorized);
        }

        $channel = Channel::where('uuid', $uuid)
            ->first();

        if (is_object($channel)) {
            $channel->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'parent_id'     => 'nullable|integer|exists:channels,id',
                'name'          => 'required',
                'status'        => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'channel_ids'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $action
     * @param  string  $status
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // if (!checkPermission('channel-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating channel.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->channel_ids;

            foreach ($uuids as $uuid) {
                Channel::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            $channel = $this->index();
            return prepareResult(true, $channel, [], "Channel status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->channel_ids;
            foreach ($uuids as $uuid) {
                Channel::where('uuid', $uuid)->delete();
            }

            $channel = $this->index();
            return prepareResult(true, $channel, [], "Channel deleted success", $this->success);
        }
    }
}
