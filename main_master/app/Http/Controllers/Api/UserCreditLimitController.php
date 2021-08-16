<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\UserCreditLimit;
use Illuminate\Support\Facades\Auth;
// use App\Imports\VanImport;
use Illuminate\Support\Facades\Validator;
// use Maatwebsite\Excel\Facades\Excel;

class UserCreditLimitController extends Controller
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

        if (!checkPermission('route-list')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }

        if (!$this->user->can('route-list') && $this->user->role_id != '1') {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        }


        $user =  Auth::user(); 
 
        $UserCreditLimit = UserCreditLimit::select('id', 'uuid', 'organisation_id', 'user_id', 'credit_limit_type')
                            ->where('organisation_id', $user->organisation_id)
                            ->orderBy('id', 'desc')
                            ->first();
        
        return prepareResult(true, $UserCreditLimit, [], "credit limit type listing", $this->success);
    }

    /**
     * Store User Credit Limit option.
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

       /*  if (!checkPermission('region-save')) {
            return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        } */

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating credit limit type", $this->unprocessableEntity);
        }

        $user =  Auth::user(); 

        $UserCreditLimit = new UserCreditLimit;  
        $UserCreditLimit->user_id           = $user->id;
        $UserCreditLimit->credit_limit_type = $request->credit_limit_type; 
        $UserCreditLimit->save();

        if ($UserCreditLimit) {
            return prepareResult(true, $UserCreditLimit, [], "Credit limit type added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    } 
    
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [                 
                'credit_limit_type'     => 'required|integer'               
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    } 
}
