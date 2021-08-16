<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CustomerVisit;
use App\Model\CustomerActivity;
use App\Model\User;

class CustomerActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($visit_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $CustomerActivity = CustomerActivity::with(array('customerInfo.user'=>function($query){
                    $query->select('id','firstname','lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
                }))
                ->with(
                    'customerInfo:id,user_id',
					'CustomerVisit'
                )
			->where('customer_visit_id',$visit_id)
            ->orderBy('id', 'desc')
            ->get();
		$CustomerActivity_array = array();
        if (is_object($CustomerActivity)) {
            foreach ($CustomerActivity as $key => $CustomerActivity1) {
                $CustomerActivity_array[] = $CustomerActivity[$key];
            }
        }

		$data_array = array();
		$page = (isset($_REQUEST['page']))?$_REQUEST['page']:'';
		$limit = (isset($_REQUEST['page_size']))?$_REQUEST['page_size']:'';
		$pagination = array();
		if($page != '' && $limit != ''){
			$offset = ($page-1)*$limit;
			for($i=0;$i<$limit;$i++){
				if(isset($CustomerActivity_array[$offset])){
					$data_array[] = $CustomerActivity_array[$offset];
				}
				$offset++;
			}

			$pagination['total_pages'] = ceil(count($CustomerActivity_array)/$limit);
			$pagination['current_page'] = (int)$page;
			$pagination['total_records'] = count($CustomerActivity_array);
		}else{
			$data_array = $CustomerActivity_array;
		}
        return prepareResult(true, $data_array, [], "Customer Activity listing", $this->success,$pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer Activity", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            if (sizeof($request->activity) >= 1 && is_array($request->activity)) {
                foreach ($request->activity as $activity) {
                    $CustomerActivity = new CustomerActivity;
                    if ($activity['end_time']) {
                        $CustomerActivity->customer_visit_id         = $request->customer_visit_id;
                        $CustomerActivity->customer_id         = $request->customer_id;
                        $CustomerActivity->activity_name            = $activity['activity_name'];
                        $CustomerActivity->activity_action            = $activity['activity_name'];
                        $CustomerActivity->start_time            = $activity['start_time'];
                        $CustomerActivity->end_time            = $activity['end_time'];
                        $CustomerActivity->total_time            = timeCalculate($activity['start_time'], $activity['end_time']);

                        $CustomerActivity->save();
                    }
                }
            }

            \DB::commit();

            $CustomerActivity->getSaveData();

            return prepareResult(true, $CustomerActivity, [], "Customer Activity added successfully", $this->created);
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

        $CustomerActivity = CustomerActivity::with(array('customerInfo.user'=>function($query){
                    $query->select('id','firstname','lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
                }))
                ->with(
                    'customerInfo:id,user_id',
					'CustomerVisit'
                )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($CustomerActivity)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $CustomerActivity, [], "Customer Activity Edit", $this->success);
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
        $validate = $this->validations($input, "update");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer Activity.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
			$CustomerActivity = CustomerActivity::where('uuid', $uuid)->first();
            $CustomerActivity->customer_visit_id         = (!empty($request->customer_visit_id)) ? $request->customer_visit_id : null;
			$CustomerActivity->customer_id         = (!empty($request->customer_id)) ? $request->customer_id : null;
            $CustomerActivity->activity_name            = (!empty($request->activity_name)) ? $request->activity_name : null;
			$CustomerActivity->activity_action            = (!empty($request->activity_action)) ? $request->activity_action : null;
            $CustomerActivity->save();

            \DB::commit();

            $CustomerActivity->getSaveData();

            return prepareResult(true, $CustomerActivity, [], "Customer activity updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating customer activity.", $this->unauthorized);
        }

        $CustomerActivity = CustomerActivity::where('uuid', $uuid)
            ->first();

        if (is_object($CustomerActivity)) {
            $CustomerActivity->delete();
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
        $uuids = $request->customeractivity_ids;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

		if ($action == 'delete') {
            foreach ($uuids as $uuid) {
                $CustomerActivity = CustomerActivity::where('uuid', $uuid)
                ->first();
				if(is_object($CustomerActivity)){
					$CustomerActivity->delete();
				}
            }

            return prepareResult(true, $CustomerActivity, [], "Customer Activity deleted success", $this->success);
        }
    }
	private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'customer_visit_id' => 'required|integer|exists:customer_visits,id',
                'customer_id' => 'required',
            ]);
        }

        if ($type == "update") {
            $validator = \Validator::make($input, [
                'customer_visit_id' => 'required|integer|exists:customer_visits,id',
                'customer_id' => 'required',
                'activity_name' => 'required',
                'activity_action' => 'required'
            ]);
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action'        => 'required',
                'customeractivity_ids'     => 'required'
            ]);
        }

        if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
