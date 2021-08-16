<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SurveyType;
use Illuminate\Http\Request;

class SurveyTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $survey_type = SurveyType::orderBy('id', 'desc')->get();
        return prepareResult(true, $survey_type, [], "Survey type listing", $this->success);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey type", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $survey_type = new SurveyType;
            $survey_type->survey_name = $request->survey_name;
            $survey_type->status = $request->status;
            $survey_type->save();

            \DB::commit();
            return prepareResult(true, $survey_type, [], "Survey type added successfully", $this->created);
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
     * @param  \App\Model\SurveyType  $surveyType
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating survey.", $this->unauthorized);
        }

        $survey_type = SurveyType::where('uuid', $uuid)
            ->first();

        if (!is_object($survey_type)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $survey_type, [], "Survey type Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\SurveyType  $surveyType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating survey type", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $survey_type = SurveyType::where('uuid', $uuid)->first();
            $survey_type->survey_name = $request->survey_name;
            $survey_type->status = $request->status;
            $survey_type->save();

            \DB::commit();
            return prepareResult(true, $survey_type, [], "Survey type updated successfully", $this->created);
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
     * @param  \App\Model\SurveyType  $surveyType
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $survey_type = SurveyType::where('uuid', $uuid)
            ->first();

        if (is_object($survey_type)) {
            $survey_type->delete();
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
                'survey_name' => 'required|string',
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
