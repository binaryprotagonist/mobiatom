<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomFieldValueSave;
use Illuminate\Http\Request;
use App\Model\Lob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Model\CustomerLobLimits;
 
class LobController extends Controller
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

        $user =  Auth::user(); 

        $lob = Lob::select('id', 'uuid', 'organisation_id', 'user_id', 'name')
                            ->where('organisation_id', $user->organisation_id)                          
                            ->orderBy('id', 'desc')
                            ->get();
        
        return prepareResult(true, $lob, [], "Lob listing", $this->success);
    }

    /**
     * Store a LOB name.
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating lob", $this->unprocessableEntity);
        }

        $user =  Auth::user(); 

        $lob = new Lob;  
        $lob->user_id   = $user->id;
        $lob->name = $request->name; 
        $lob->save();

        if ($lob) {
            return prepareResult(true, $lob, [], "Lob added successfully", $this->success);
        } else {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
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
            return prepareResult(false, [], [], "Error while validating lob code", $this->unauthorized);
        } 

            $lob = Lob::where('uuid', $uuid)->first();
           
            //all record deleted in  Customer LobL imits matches in lob_id
            $CustomerLobLimits = CustomerLobLimits::where('lob_id', $lob->id)->delete();
         

        if (is_object($lob)) {
            $lob->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }
        else{
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
     
    }
  
    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [                
                'name'     => 'required' 
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }
 
}
