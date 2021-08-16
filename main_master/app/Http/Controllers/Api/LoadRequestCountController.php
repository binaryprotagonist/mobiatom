<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\LoadRequestCount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoadRequestCountController extends Controller
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

        $load_request_count = LoadRequestCount::select('id', 'uuid', 'organisation_id', 'request_per_day')                           
                                                ->orderBy('id', 'desc')
                                                ->first();
        
        return prepareResult(true, $load_request_count, [], "load request count listing", $this->success);
    }

    /**
     * Store Load request count per-day.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @return [json] van object
     */
    public function store(Request $request)
    { 
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }       

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating load request count", $this->unprocessableEntity);
        }
 
       \DB::beginTransaction();
       try {
            $load_request_count = new LoadRequestCount;        
            $load_request_count->request_per_day = (!empty($request->request_per_day)) ? $request->request_per_day : 0; 
            $load_request_count->save();

            \DB::commit();
            if ($load_request_count) {
                return prepareResult(true, $load_request_count, [], "load request count added successfully", $this->success);
            } else {
                \DB::rollback();
                return prepareResult(false, [], 'load request count not add', "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
            } 
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    } 
    
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [                 
                'request_per_day'     => 'required|integer'               
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    } 
}
