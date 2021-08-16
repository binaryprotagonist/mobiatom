<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\OrganisationPurchasePlan;
use App\Model\Plan;
use App\Model\PlanFeature;
use App\Model\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
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

        $plan = Plan::where('is_active', 1)
            ->with(
                'planFeature:id,feature_name,plan_id',
                'software'
            )
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
            $pagination['monthly_page'] = (int)$page;
            $pagination['total_records'] = count($plan_array);
        } else {
            $data_array = $plan_array;
        }

        return prepareResult(true, $data_array, [], "Plan listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexByPass()
    {
        $plan = Plan::where('is_active', 1)
            ->with('planFeature:id,feature_name,plan_id')
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
            $pagination['monthly_page'] = (int)$page;
            $pagination['total_records'] = count($plan_array);
        } else {
            $data_array = $plan_array;
        }

        return prepareResult(true, $data_array, [], "Plan listing", $this->success, $pagination);
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
        $plan = new Plan;
        $plan->software_id = $request->software_id;
        $plan->name =  $request->name;
        $plan->monthly_price = $request->monthly_price;
        $plan->maximum_user = $request->maximum_user;
        $plan->yearly_price = $request->yearly_price;
        $plan->is_active = $request->is_active;
        $plan->save();

        $plan->getSaveData();

        return prepareResult(true, $plan, [], "Plan added successfully", $this->success);
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

        $plan = Plan::where('id', $id)
            ->first();

        if (!is_object($plan)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $plan, [], "Plan Edit", $this->success);
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
        $plan = Plan::where('id', $id)->first();
        $plan->software_id = $request->software_id;
        $plan->name =  $request->name;
        $plan->monthly_price = $request->monthly_price;
        $plan->maximum_user = $request->maximum_user;
        $plan->yearly_price = $request->yearly_price;
        $plan->is_active = $request->is_active;
        $plan->save();

        $plan->getSaveData();

        return prepareResult(true, $plan, [], "Plan updated successfully", $this->success);
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

        $plan = Plan::where('id', $id)
            ->first();

        if (is_object($plan)) {
            $plan->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access.", $this->unauthorized);
    }

    public function choosePlan(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "org-plan-add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating plan", $this->unprocessableEntity);
        }

        $organisationPurchasePlan = new OrganisationPurchasePlan;
        $organisationPurchasePlan->software_id = $request->software_id;
        $organisationPurchasePlan->plan_id = $request->plan_id;
        $organisationPurchasePlan->registed_user = 1;
        $organisationPurchasePlan->save();

        return prepareResult(true, $organisationPurchasePlan, [], "Organisation Plan added successfully", $this->created);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexOrgPlan()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $plan = OrganisationPurchasePlan::get();

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
            $pagination['monthly_page'] = (int)$page;
            $pagination['total_records'] = count($plan_array);
        } else {
            $data_array = $plan_array;
        }

        return prepareResult(true, $data_array, [], "Organisation Plan listing", $this->success, $pagination);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'software_id'     => 'required|integer|exists:softwares,id',
                'name'     => 'required',
                'monthly_price'     => 'required',
                'yearly_price'     => 'required',
                'maximum_user'     => 'required'
            ]);
        }

        if ($type == "org-plan-add") {
            $validator = \Validator::make($input, [
                'software_id'     => 'required|integer|exists:softwares,id',
                'plan_id'     => 'required|integer|exists:plans,id'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }

    public function softwareByPlan(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->software_id) {
            return prepareResult(false, [], [], "Error while validating regions", $this->unprocessableEntity);
        }

        $software = Software::with('plan', 'plan.planFeature')
            ->where('slug', '=', $request->software_id)
            ->first();

        return prepareResult(true, $software, [], "Plan listing", $this->success);
    }
}
