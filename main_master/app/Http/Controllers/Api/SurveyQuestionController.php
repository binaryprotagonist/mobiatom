<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SurveyQuestion;
use App\Model\SurveyQuestionAnswer;
use App\Model\SurveyQuestionAnswerDetail;
use App\Model\SurveyQuestionValue;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($survey_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$survey_id) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $survey_question = SurveyQuestion::with(
            'survey:id,survey_type_id,name,distribution_id',
            'survey.distribution',
            'surveyQuestionValue:id,survey_id,survey_question_id,question_value'
        )
            ->where('survey_id', $survey_id)
            ->orderBy('id', 'desc')
            ->get();

        $survey_question_array = array();
        if (is_object($survey_question)) {
            foreach ($survey_question as $key => $survey_question1) {
                $survey_question_array[] = $survey_question[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_question_array[$offset])) {
                    $data_array[] = $survey_question_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_question_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_question_array);
        } else {
            $data_array = $survey_question_array;
        }

        return prepareResult(true, $data_array, [], "Survey question listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listAll()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $survey_question = SurveyQuestion::with('survey:id,survey_type_id,name,distribution_id', 'surveyQuestionValue:id,survey_id,survey_question_id,question_value')
            ->orderBy('id', 'desc')
            ->get();

        $survey_question_array = array();
        if (is_object($survey_question)) {
            foreach ($survey_question as $key => $survey_question1) {
                $survey_question_array[] = $survey_question[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_question_array[$offset])) {
                    $data_array[] = $survey_question_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_question_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_question_array);
        } else {
            $data_array = $survey_question_array;
        }

        return prepareResult(true, $data_array, [], "Survey question listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey question", $this->unprocessableEntity);
        }

        if (is_array($request->question_value) && sizeof($request->question_value) < 1) {
            return prepareResult(false, [], 'Please add atleast one answer.', "Error while validating survey question", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $survey_question = new SurveyQuestion;
            $survey_question->survey_id = $request->survey_id;
            $survey_question->question = $request->question;
            $survey_question->question_type = $request->question_type;
            $survey_question->save();

            if ($survey_question->question_type != 'text' && $survey_question->question_type != 'textarea') {
                foreach ($request->question_value as $question_value) {
                    $survey_question_value = new SurveyQuestionValue;
                    $survey_question_value->survey_id = $survey_question->survey_id;
                    $survey_question_value->survey_question_id = $survey_question->id;
                    $survey_question_value->question_value = $question_value;
                    $survey_question_value->save();
                }
            }

            \DB::commit();
            return prepareResult(true, $survey_question, [], "Survey question added successfully", $this->created);
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
     * @param  $uuid $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $survey_question = SurveyQuestion::with('survey:id,survey_type_id,name,distribution_id', 'surveyQuestionValue:id,survey_id,survey_question_id,question_value')
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($survey_question)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $survey_question, [], "Survey Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\SurveyQuestion  $surveyQuestion
     * @return \Illuminate\Http\Response
     */
    public function update($uuid, Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey question", $this->unprocessableEntity);
        }

        if (is_array($request->question_value) && sizeof($request->question_value) < 1) {
            return prepareResult(false, [], 'Please add atleast one answer.', "Error while validating survey question", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $survey_question = SurveyQuestion::where('uuid', $uuid)->first();

            SurveyQuestionValue::where('survey_question_id', $survey_question->id)->delete();

            $survey_question->survey_id = $request->survey_id;
            $survey_question->question = $request->question;
            $survey_question->question_type = $request->question_type;
            $survey_question->save();

            if ($survey_question->question_type != 'text' && $survey_question->question_type != 'textarea') {
                foreach ($request->question_value as $question_value) {
                    $survey_question_value = new SurveyQuestionValue;
                    $survey_question_value->survey_id = $survey_question->survey_id;
                    $survey_question_value->survey_question_id = $survey_question->id;
                    $survey_question_value->question_value = $question_value;
                    $survey_question_value->save();
                }
            }

            \DB::commit();
            return prepareResult(true, $survey_question, [], "Survey question update successfully", $this->created);
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
     * @param  $uuid $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating survey question", $this->unauthorized);
        }

        $survey = SurveyQuestion::where('uuid', $uuid)
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
                'survey_id' => 'required|integer|exists:surveys,id',
                'question' => 'required|string',
                'question_type' => 'required'
            ]);
        }

        if ($type == "addAnswer") {
            $validator = \Validator::make($input, [
                'survey_id' => 'required|integer|exists:surveys,id'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }

    public function storeQuestionAnswer(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }
        $input = $request->json()->all();

        $validate = $this->validations($input, "addAnswer");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey question", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $survey_question_answer = new SurveyQuestionAnswer;
            $survey_question_answer->survey_id = $request->survey_id;
            $survey_question_answer->survey_type_id = $request->survey_type_id;
            $survey_question_answer->salesman_id = $request->salesman_id;
            $survey_question_answer->customer_id = $request->customer_id;
            if ($request->survey_type_id == 3) {
                $survey_question_answer->customer_name = $request->customer_name;
                $survey_question_answer->email = $request->email;
                $survey_question_answer->phone = $request->phone;
            }
            $survey_question_answer->save();

            foreach ($request->questions as $question) {
                $sqad = new SurveyQuestionAnswerDetail;
                $sqad->survey_id = $request->survey_id;
                $sqad->survey_question_answer_id = $survey_question_answer->id;
                $sqad->survey_question_id = $question['survey_question_id'];
                $sqad->answer = $question['answer'];
                $sqad->save();
            }

            \DB::commit();
            return prepareResult(true, $survey_question_answer, [], "Survey question answer added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexQuestion(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $survey_question_query = SurveyQuestionAnswer::with(
            'survey',
            'surveyQuestion',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code'
        );
        if ($request->survey_id) {
            $survey_question_query->where('survey_id', $request->survey_id);
        }
        if ($request->survey_question_id) {
            $survey_question_query->where('survey_question_id', $request->survey_question_id);
        }
        if ($request->salesman_id) {
            $survey_question_query->where('salesman_id', $request->salesman_id);
        }
        if ($request->customer_id) {
            $survey_question_query->where('customer_id', $request->customer_id);
        }
        $survey_question = $survey_question_query->orderBy('id', 'desc')->get();

        $survey_question_array = array();
        if (is_object($survey_question)) {
            foreach ($survey_question as $key => $survey_question1) {
                $survey_question_array[] = $survey_question[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_question_array[$offset])) {
                    $data_array[] = $survey_question_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_question_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_question_array);
        } else {
            $data_array = $survey_question_array;
        }

        return prepareResult(true, $data_array, [], "Survey question answer listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @param $survey_id
     * @return \Illuminate\Http\Response
     */
    public function surveyQuestionAnswer($survey_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        // survey thorgh question and ans joi
        $survey_question_ans = SurveyQuestionAnswer::with(
            'survey',
            'customer:id,firstname,lastname',
            'customer.customerinfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmaninfo:id,user_id,salesman_code'
        )
            ->where('survey_id', $survey_id)
            ->orderBy('id', 'desc')
            ->get();


        $survey_question_ans_array = array();
        if (is_object($survey_question_ans)) {
            foreach ($survey_question_ans as $key => $survey_question_ans1) {
                $survey_question_ans_array[] = $survey_question_ans[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_question_ans_array[$offset])) {
                    $data_array[] = $survey_question_ans_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_question_ans_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_question_ans_array);
        } else {
            $data_array = $survey_question_ans_array;
        }

        return prepareResult(true, $data_array, [], "Survey question answer listing", $this->success, $pagination);
    }

    public function surveyQuestionAnswerDetails($id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$id) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $sqad = SurveyQuestionAnswerDetail::with(
            'survey',
            'surveyQuestion',
            'surveyQuestion.surveyQuestionValue'
            // 'surveyQuestionValue'
        )
            ->where('survey_question_answer_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        $sqad_array = array();
        if (is_object($sqad)) {
            foreach ($sqad as $key => $sqad1) {
                $sqad_array[] = $sqad[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($sqad_array[$offset])) {
                    $data_array[] = $sqad_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($sqad_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($sqad_array);
        } else {
            $data_array = $sqad_array;
        }

        return prepareResult(true, $data_array, [], "Survey question answer details listing", $this->success, $pagination);
    }
}
