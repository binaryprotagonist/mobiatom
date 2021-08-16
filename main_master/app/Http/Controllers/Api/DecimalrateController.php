<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Decimalrate;
use App\User;

class DecimalrateController extends Controller
{
    public function getdecimalrate()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $Decimalrate = Decimalrate::first();
		
        return prepareResult(true, $Decimalrate, [], "Decimal rate", $this->success,[]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function savedecimalrate(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating decimal rate", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
			$Decimalrate = Decimalrate::first();
			if(!is_object($Decimalrate)){
				$Decimalrate = new Decimalrate;
			}
            $Decimalrate->decimal_rate = $request->decimal_rate;
            $Decimalrate->save();

            \DB::commit();
            return prepareResult(true, $Decimalrate, [], "Decimal rate saved successfully", $this->created);
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
                'decimal_rate'  => 'required'
            ]);
        }
		
        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}