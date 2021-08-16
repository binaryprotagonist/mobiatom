<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\PlanFeature;
use Illuminate\Http\Request;

class PlanFeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  int  $id 
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {

        $plan = PlanFeature::where('plan_id', $id)
        ->with('plan:id,name')
            ->get();

        $plan_array = array();
        if (is_object($plan)) {
            foreach ($plan as $key => $plan1) {
                $plan_array[] = $plan[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($plan_array[$offset])) {
                    $data_array[] = $plan_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($plan_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($plan_array);
        } else {
            $data_array = $plan_array;
        }

        return prepareResult(true, $data_array, [], "Plan feature listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating plan", $this->unprocessableEntity);
        }

        // Create region object
        $plan_feature = new PlanFeature;
        $plan_feature->plan_id = $request->plan_id;
        $plan_feature->feature_name =  $request->feature_name;
        $plan_feature->save();

        $plan_feature->plan;

        return prepareResult(true, $plan_feature, [], "Plan feature added successfully", $this->success);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(true, [], [], "User not authenticate", $this->unprocessableEntity);
        }

        $plan = PlanFeature::find($id);

        if (!is_object($plan)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $plan, [], "Plan feature Edit", $this->success);
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
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating plan", $this->unprocessableEntity);
        }

        // Create region object
        $plan_feature = PlanFeature::find($id);
        $plan_feature->plan_id = $request->plan_id;
        $plan_feature->feature_name =  $request->feature_name;
        $plan_feature->save();

        $plan_feature->plan;

        return prepareResult(true, $plan_feature, [], "Plan feature update successfully", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Error while validating regions", $this->unprocessableEntity);
        }

        $plan = PlanFeature::where('id', $id)
            ->first();

        if (is_object($plan)) {
            $plan->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access.", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'plan_id'     => 'required|integer|exists:plans,id',
                'feature_name'     => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
