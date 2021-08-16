<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Survey;
use App\Model\SurveyCustomer;
use App\Model\SurveyQuestionAnswer;
use Illuminate\Http\Request;

class SurveyController extends Controller
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

        // if (!checkPermission('route-list')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        // if (!$this->user->can('route-list') && $this->user->role_id != '1') {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $survey_query = Survey::with(
            'surveyType:id,survey_name',
            'surveyCustomer:id,survey_id,survey_type_id,customer_id',
            'surveyCustomer.customer:id,firstname,lastname',
            'surveyCustomer.customer.customerInfo:id,user_id,customer_code',
            'distribution'
        );
        // ->where('survey_type_id', $request->survey_type_id)
        
        if ($request->date) {
            $survey_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }
        
        if ($request->start_date) {
            $survey_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $survey_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        if ($request->name) {
            $survey_query->where('name', 'like', '%' . $request->name . '%');
        }
        
        $survey = $survey_query->orderBy('id', 'desc')
            ->get();

        $survey_array = array();
        if (is_object($survey)) {
            foreach ($survey as $key => $survey1) {
                $survey_array[] = $survey[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_array[$offset])) {
                    $data_array[] = $survey_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_array);
        } else {
            $data_array = $survey_array;
        }

        return prepareResult(true, $data_array, [], "Survey listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexByType(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // if (!checkPermission('route-list')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        // if (!$this->user->can('route-list') && $this->user->role_id != '1') {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $survey_query = Survey::with(
            'surveyType:id,survey_name',
            'surveyCustomer:id,survey_id,survey_type_id,customer_id',
            'surveyCustomer.customer:id,firstname,lastname',
            'surveyCustomer.customer.customerInfo:id,user_id,customer_code',
            'distribution'
        )
            ->where('survey_type_id', $request->survey_type_id);
            if ($request->date) {
                $survey_query->where('created_at', date('Y-m-d', strtotime($request->date)));
            }
            
            if ($request->start_date) {
                $survey_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
            }
    
            if ($request->end_date) {
                $survey_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
            }
    
            if ($request->name) {
                $survey_query->where('name', 'like', '%' . $request->name . '%');
            }
            
            $survey = $survey_query->orderBy('id', 'desc')
                ->get();

        $survey_array = array();
        if (is_object($survey)) {
            foreach ($survey as $key => $survey1) {
                $survey_array[] = $survey[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_array[$offset])) {
                    $data_array[] = $survey_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_array);
        } else {
            $data_array = $survey_array;
        }

        return prepareResult(true, $data_array, [], "Survey listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey", $this->unprocessableEntity);
        }

        if ($request->survey_type_id == 1) {
            $validate = $this->validations($input, "type1");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey", $this->unprocessableEntity);
            }
        }

        if ($request->survey_type_id == 2 || $request->survey_type_id == 4) {
            $validate = $this->validations($input, "type24");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $survey = new Survey;
            $survey->survey_type_id = $request->survey_type_id;
            if ($request->survey_type_id == 1) {
                $survey->distribution_id = $request->distribution_id;
            }
            $survey->name = $request->name;
            $survey->start_date = $request->start_date;
            $survey->end_date = $request->end_date;
            $survey->save();

            if ($survey->survey_type_id == 2 || $survey->survey_type_id == 4) {
                if (is_array($request->customer_id) && sizeof($request->customer_id) >= 1) {
                    foreach ($request->customer_id as $id) {
                        $survey_customers = new SurveyCustomer;
                        $survey_customers->survey_type_id = $survey->survey_type_id;
                        $survey_customers->survey_id = $survey->id;
                        $survey_customers->customer_id = $id;
                        $survey_customers->save();
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $survey, [], "Survey added successfully", $this->created);
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
     * @param  \App\Model\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating survey.", $this->unauthorized);
        }

        $survey = Survey::with(
            'surveyType:id,survey_name',
            'surveyCustomer:id,survey_id,survey_type_id,customer_id',
            'surveyCustomer.customer:id,firstname,lastname',
            'surveyCustomer.customer.customerInfo:id,user_id,customer_code',
            'distribution'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($survey)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $survey, [], "Survey Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey", $this->unprocessableEntity);
        }

        if ($request->survey_type_id == 1) {
            $validate = $this->validations($input, "type1");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey", $this->unprocessableEntity);
            }
        }


        if ($request->survey_type_id == 2 || $request->survey_type_id == 4) {
            $validate = $this->validations($input, "type24");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $survey = Survey::where('uuid', $uuid)->first();
            SurveyCustomer::where('survey_id', $survey->id)->delete();

            $survey->survey_type_id = $request->survey_type_id;
            if ($request->survey_type_id == 1) {
                $survey->distribution_id = $request->distribution_id;
            }
            $survey->name = $request->name;
            $survey->start_date = $request->start_date;
            $survey->end_date = $request->end_date;
            $survey->save();

            if ($survey->survey_type_id == 2 || $survey->survey_type_id == 4) {
                if (is_array($request->customer_id) && sizeof($request->customer_id) >= 1) {
                    foreach ($request->customer_id as $id) {
                        $survey_customers = new SurveyCustomer;
                        $survey_customers->survey_type_id = $survey->survey_type_id;
                        $survey_customers->survey_id = $survey->id;
                        $survey_customers->customer_id = $id;
                        $survey_customers->save();
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $survey, [], "Survey updated successfully", $this->created);
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
     * @param  \App\Model\Survey  $survey
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating survey", $this->unauthorized);
        }

        $survey = Survey::where('uuid', $uuid)
            ->first();

        if (is_object($survey)) {
            $survey->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'survey_type_id' => 'required|integer|exists:survey_types,id',
                'name' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date'
            ]);
        }

        if ($type == "type1") {
            $validator = \Validator::make($input, [
                'distribution_id' => 'required|integer|exists:distributions,id'
            ]);
        }

        if ($type == "type24") {
            $validator = \Validator::make($input, [
                'customer_id' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
