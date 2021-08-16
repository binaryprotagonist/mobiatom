<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Template;
use App\Model\AssignTemplate;
use App\User;
use auth;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($module)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$module) {
            return prepareResult(false, [], [], "Error while validating template.", $this->unprocessableEntity);
        }

        $Template = Template::with('assginTempalate')->where('module', $module)->orderBy('id', 'desc')->get();
        if (!is_object($Template)) {
            return prepareResult(false, [], [], "Error while validating template.", $this->unprocessableEntity);
        }

        foreach ($Template as $key => $temp) {
            if($temp->assginTempalate) {
                $Template[$key]->is_assign = 1;
            } else {
                if (count($Template) < 2) {
                    $Template[$key]->is_assign = 1;
                }
            }
        }

        $Template_array = array();
        if (is_object($Template)) {
            foreach ($Template as $key => $Template1) {
                $Template_array[] = $Template[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($Template_array[$offset])) {
                    $data_array[] = $Template_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($Template_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($Template_array);
        } else {
            $data_array = $Template_array;
        }

        return prepareResult(true, $data_array, [], "Template listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating template", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $destinationPath = 'uploads/templates/';
            $image_name = Str::slug($request->template_name) . '-' . strtotime(date('Y-m-d'));
            $image = $request->template_image;
            $getBaseType = explode(',', $image);
            $getExt = explode(';', $image);
            $image = str_replace($getBaseType[0] . ',', '', $image);
            $image = str_replace(' ', '+', $image);
            $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
            \File::put($destinationPath . $fileName, base64_decode($image));

            $template_image = URL('/') . '/' . $destinationPath . $fileName;

            $Template = new Template;
            $Template->template_name = $request->template_name;
            $Template->module = $request->module;
            $Template->template_image = $template_image;
            $Template->is_default = $request->is_default;
            $Template->save();

            \DB::commit();
            return prepareResult(true, $Template, [], "Template added successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    // function usertemplate()
    // {
    //     $orgranationids = Auth::user()->organisation_id;
    //     //AssignTemplate
    //     //$assigntemplate = AssignTemplate::where('uuid',$uuid)->first();
    //     $assigntemplate = AssignTemplate::with('template')->get();
    //     if (count($assigntemplate) < 1) {
    //         $template = Template::where('is_default', '1')->get();
    //         $assigntemplate = (object)array();
    //         $assigntemplate->id = null;
    //         $assigntemplate->uuid = null;
    //         $assigntemplate->organisation_id = null;
    //         $assigntemplate->template_id = null;
    //         $assigntemplate->created_at = null;
    //         $assigntemplate->updated_at = null;
    //         $assigntemplate->deleted_at = null;
    //         $assigntemplate->template = $template;

    //         return prepareResult(true, $assigntemplate, [], "User Template Details.", $this->success);
    //     }
    //     return prepareResult(true, $assigntemplate, [], "User Template Details.", $this->success);
    // }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function updatetemplate(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "template");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating template", $this->unprocessableEntity);
        }
        // \DB::beginTransaction();
        // try {



        //     return prepareResult(true, $template, [], "Template updated successfully", $this->success);
        // } catch (\Exception $exception) {
        //     \DB::rollback();
        //     return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        // } catch (\Throwable $exception) {
        //     \DB::rollback();
        //     return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        // }

        $template = Template::find($request->template_id);
        if (is_object($template)) {

            // delete record and create new
            AssignTemplate::where('module', $template->module)
                ->where('organisation_id', $request->user()->organisation_id)
                ->forceDelete();

            $assign_template = new AssignTemplate;
            $assign_template->template_id = $request->template_id;
            $assign_template->module = $template->module;
            $assign_template->save();
        }

        return prepareResult(true, $template, [], "Template updated successfully", $this->success);
    }

    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $Template = Template::where('uuid', $uuid)
            ->first();


        if (!is_object($Template)) {
            return prepareResult(false, [], [], "Record not found", $this->unprocessableEntity);
        }

        return prepareResult(true, $Template, [], "Template Edit", $this->success);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Template", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {
            $Template = Template::where('uuid', $uuid)->first();
            $Template->template_name = $request->template_name;
            $Template->module = $request->module;
            $Template->template_image = $template_image;
            $Template->is_default = $request->is_default;
            $Template->save();

            \DB::commit();
            return prepareResult(true, $Template, [], "Template updated successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating Template", $this->unauthorized);
        }

        $Template = Template::where('uuid', $uuid)
            ->first();

        if (is_object($Template)) {
            $Template->delete();

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
                'template_name' => 'required',
                'template_image' => 'required',
                'is_default' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }
        if ($type == "template") {
            $validator = \Validator::make($input, [
                'template_id' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }
        return ["error" => $error, "errors" => $errors];
    }
}
