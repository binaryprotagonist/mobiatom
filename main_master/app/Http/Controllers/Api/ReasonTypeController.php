<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\ReasonType;
use Illuminate\Http\Request;

class ReasonTypeController extends Controller
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

        $reason = ReasonType::select('id', 'uuid', 'organisation_id', 'name', 'type', 'status')
            ->orderBy('id', 'desc')
            ->get();
        $reason_array = array();
        if (is_object($reason)) {
            foreach ($reason as $key => $reason1) {
                $reason_array[] = $reason[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($reason_array[$offset])) {
                    $data_array[] = $reason_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($reason_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($reason_array);
        } else {
            $data_array = $reason_array;
        }

        return prepareResult(true, $data_array, [], "Reason listing", $this->success, $pagination);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating reason", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {
            $reason = new ReasonType;
            $reason->name = $request->name;
            $reason->type = $request->type;
            $reason->status = $request->status;
            $reason->save();

            \DB::commit();
            return prepareResult(true, $reason, [], "Reason added successfully", $this->created);
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
     * @param  \App\Model\Reason  $reason
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating order.", $this->unauthorized);
        }

        $reason = ReasonType::select('id', 'uuid', 'organisation_id', 'name', 'type', 'status')
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($reason)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $reason, [], "Region Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Reason  $reason
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating reason", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {
            $reason = ReasonType::where('uuid', $uuid)->first();
            $reason->name = $request->name;
            $reason->type = $request->type;
            $reason->status = $request->status;
            $reason->save();

            \DB::commit();
            return prepareResult(true, $reason, [], "Reason updated successfully", $this->success);
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
     * @param  \App\Model\Reason  $reason
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating reason", $this->unauthorized);
        }

        $reason = ReasonType::where('uuid', $uuid)
            ->first();

        if (is_object($reason)) {
            $reason->delete();
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
                'name'     => 'required',
                'type'     => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
}
