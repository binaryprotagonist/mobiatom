<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\CodeSetting;

class CodeSettingController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        // $validate = $this->validations($input, "add");
        // if ($validate["error"]) {
        //     return prepareResult(false, [], $validate['errors']->first(), "Error while validating channel", $this->unprocessableEntity);
        // }
        $variable = $request->function_for;
        if (CodeSetting::count() > 0) {
            $code_setting = CodeSetting::first();
            if ($code_setting['is_final_update_' . $variable] == 1) {
                return prepareResult(false, [], [], "Already added prefix code and number.", $this->unprocessableEntity);
            }
        } else {
            $code_setting = new CodeSetting;
        }

        $code_setting['is_code_auto_' . $variable]     = $request->is_code_auto;
        $code_setting['prefix_code_' . $variable]      = $request->prefix_code;
        $code_setting['start_code_' . $variable]       = $request->start_code;
        $code_setting['next_coming_number_' . $variable] = $request->prefix_code . $request->start_code;
        $code_setting['is_final_update_' . $variable]  = $request->is_final_update;
        $code_setting->save();

        if (is_object($code_setting)) {
            return prepareResult(true, $code_setting, [], "Code Setting updated successfully", $this->success);
        }

        return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
    }

    public function getNextCommingCode(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();

        $variable = $request->function_for;
        $nextComingNumber['number_is'] = null;
        $nextComingNumber['prefix_is'] = null;
        if (CodeSetting::count() > 0) {
            $code_setting = CodeSetting::first();
            if ($code_setting['is_final_update_' . $variable] == 1) {
                $nextComingNumber['number_is'] = $code_setting['next_coming_number_' . $variable];
                $nextComingNumber['prefix_is'] = $code_setting['prefix_code_' . $variable];
            }
        }
        return prepareResult(true, $nextComingNumber, [], "Next comming number.", $this->success);
    }
}
