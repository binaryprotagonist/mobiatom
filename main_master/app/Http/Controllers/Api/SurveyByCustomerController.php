<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Survey;
use Illuminate\Http\Request;

class SurveyByCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function consumerSurveyByCustomer($customer_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $consumer_survey = Survey::with(
            'surveyCustomer',
            'surveyCustomer.customer:id,firstname,lastname',
            'surveyCustomer.customer.customerInfo:id,user_id,customer_code',
            'surveyQuestion')
        ->where('survey_type_id', 2)
        ->whereDate('start_date', '<=', date('Y-m-d'))
        ->whereDate('end_date', '>=', date('Y-m-d'))
        ->whereHas('surveyCustomer', function ($query) use ($customer_id) {
            $query->where('customer_id', $customer_id);
        })
        ->orderBy('id', 'desc')
        ->get();

        return prepareResult(true, $consumer_survey, [], "Consumer Survey by customer listing", $this->success);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function assetTrackingSurveyByCustomer($customer_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $asset_survey = Survey::with(
            'surveyCustomer',
            'surveyCustomer.customer:id,firstname,lastname',
            'surveyQuestion'
        )
        ->where('survey_type_id', 4)
        ->whereDate('start_date', '<=', date('Y-m-d'))
        ->whereDate('end_date', '>=', date('Y-m-d'))
        ->whereHas('surveyCustomer', function ($query) use ($customer_id) {
            $query->where('customer_id', $customer_id);
        })
        ->orderBy('id', 'desc')
        ->get();

        return prepareResult(true, $asset_survey, [], "asset tracking Survey by customer listing", $this->success);
    }
}
