<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomerInfo;
use App\Model\Distribution;
use App\Model\DistributionModelStock;
use App\Model\Survey;
use Illuminate\Http\Request;
use stdClass;

class SurveyMerchandiserByCustomer extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $merchandiser_id
     * @param  int  $distribution_id
     * @return \Illuminate\Http\Response
     */
    public function distributionCustomers($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_info = CustomerInfo::select('id', 'user_id')
            ->with(
                'user:id,firstname,lastname',
                'customerMerchandiser',
                'customerMerchandiser.salesman:id,firstname,lastname',
                'distributionCustomer:id,customer_id,distribution_id'
            )
            ->whereHas('customerMerchandiser', function ($query) use ($merchandiser_id) {
                $query->where('merchandiser_id', $merchandiser_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        $merge_all_data = array();
        foreach ($customer_info as $custKey => $customer) {
            $merge_data = new stdClass;
            $merge_data->cusotmer_id = $customer->user_id;
            $merge_data->user = $customer->user;
            $merge_data->merchandiser = $customer->merchandiser;
            foreach ($customer->distributionCustomer as $aicKey => $distribution_customer) {

                $distributions = Distribution::select('id', 'name', 'start_date', 'end_date')
                    ->where('start_date', '<=', date('Y-m-d'))
                    ->where('end_date', '>=', date('Y-m-d'))
                    ->where('id', $distribution_customer->distribution_id)
                    ->first();

                $distributionModelStock = DistributionModelStock::select('id', 'customer_id', 'distribution_id')
                    ->with(
                        'distributionModelStockDetails:id,distribution_model_stock_id,distribution_id,item_id,item_uom_id,capacity',
                        'distributionModelStockDetails.item:id,item_name,item_major_category_id,brand_id',
                        'distributionModelStockDetails.item.itemMajorCategory:id,name',
                        'distributionModelStockDetails.item.brand:id,brand_name',
                        'distributionModelStockDetails.itemUom:id,name'
                    )
                    ->where('customer_id', $distribution_customer->customer_id)
                    ->where('distribution_id', $distributions->id)
                    ->get();

                $distributions->distributionModelStocks = $distributionModelStock;

                $merge_data->distribution[] = $distributions;
            }
            $merge_all_data[] = $merge_data;
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($merge_all_data[$offset])) {
                    $data_array[] = $merge_all_data[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($merge_all_data) / $limit);
            $pagination['total_records'] = count($merge_all_data);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $merge_all_data;
        }

        return prepareResult(true, $data_array, [], "Distribution customer listing", $this->success, $pagination);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $route_id
     * @param  int  $distribution_id
     * @return \Illuminate\Http\Response
     */
    public function distributionCustomersbyRoute($route_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$route_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_info = CustomerInfo::select('id', 'user_id')
            ->with(
                'user:id,firstname,lastname',
                'distributionCustomer:id,customer_id,distribution_id'
            )
            ->where('route_id', $route_id)
            ->orderBy('id', 'desc')
            ->get();

        $merge_all_data = array();
        foreach ($customer_info as $custKey => $customer) {
            $merge_data = new stdClass;
            $merge_data->cusotmer_id = $customer->user_id;
            $merge_data->user = $customer->user;
            $merge_data->merchandiser = $customer->merchandiser;
            foreach ($customer->distributionCustomer as $aicKey => $distribution_customer) {

                $distributions = Distribution::select('id', 'name', 'start_date', 'end_date')
                    ->where('start_date', '<=', date('Y-m-d'))
                    ->where('end_date', '>=', date('Y-m-d'))
                    ->where('id', $distribution_customer->distribution_id)
                    ->first();

                $distributionModelStock = DistributionModelStock::select('id', 'customer_id', 'distribution_id')
                    ->with(
                        'distributionModelStockDetails:id,distribution_model_stock_id,distribution_id,item_id,item_uom_id,capacity',
                        'distributionModelStockDetails.item:id,item_name,item_major_category_id,brand_id',
                        'distributionModelStockDetails.item.itemMajorCategory:id,name',
                        'distributionModelStockDetails.item.brand:id,brand_name',
                        'distributionModelStockDetails.itemUom:id,name'
                    )
                    ->where('customer_id', $distribution_customer->customer_id)
                    ->where('distribution_id', $distributions->id)
                    ->get();

                $distributions->distributionModelStocks = $distributionModelStock;

                $merge_data->distribution[] = $distributions;
            }
            $merge_all_data[] = $merge_data;
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($merge_all_data[$offset])) {
                    $data_array[] = $merge_all_data[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($merge_all_data) / $limit);
            $pagination['total_records'] = count($merge_all_data);
            $pagination['current_page'] = (int)$page;
        } else {
            $data_array = $merge_all_data;
        }

        return prepareResult(true, $data_array, [], "Distribution customer listing", $this->success, $pagination);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $merchandiser_id
     * @param  int  $distribution_id
     * @return \Illuminate\Http\Response
     */
    public function assetTrackingCustomers($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_info = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            ->where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                'assetTracking'
            )
            ->whereHas('assetTracking', function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $customer_info, [], "Asset tracking by merchandise listing", $this->success);
    }

    public function distributionSurveyCustomers($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_info = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            ->where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                'distributionCustomer:id,customer_id,distribution_id',
                'distributionCustomer.distribution:id,name,start_date,end_date',
                'distributionCustomer.distribution.distributionSurvey:id,survey_type_id,distribution_id,name,start_date,end_date',
                'distributionCustomer.distribution.distributionSurvey.surveyQuestion',
                'distributionCustomer.distribution.distributionSurvey.surveyQuestion.surveyQuestionValue'
            )
            ->whereHas('distributionCustomer.distribution.distributionSurvey', function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $customer_info, [], "distribution by merchandise listing", $this->success);
    }

    public function assetTrackingSurveyCustomers($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_info = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            ->where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                // 'surveyCustomer'
                'assetTracking:id,customer_id,organisation_id,title,start_date,end_date',
                'assetTracking.surveyCustomer:id,survey_id,customer_id',
                'assetTracking.surveyCustomer.survey:id,survey_type_id,distribution_id,name,start_date,end_date',
                'assetTracking.surveyCustomer.survey.surveyQuestion',
                'assetTracking.surveyCustomer.survey.surveyQuestion.surveyQuestionValue'
            )
            ->whereHas('assetTracking.surveyCustomer.survey', function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $customer_info, [], "Asset tracking by merchandise listing", $this->success);
    }

    public function consumerSurveyCustomers($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_info = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
            ->where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                'surveyCustomer:id,survey_id,survey_type_id,customer_id',
                'surveyCustomer.survey',
                'surveyCustomer.survey.surveyQuestion',
                'surveyCustomer.survey.surveyQuestion.surveyQuestionValue'
            )
            ->whereHas('surveyCustomer.survey', function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $customer_info, [], "Consumer survey by merchandise listing", $this->success);
    }

    public function sensorySurveyCustomers()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $survey = Survey::with(
            'surveyQuestion',
            'surveyQuestion.surveyQuestionValue'
        )
            ->where('survey_type_id', 3)
            ->where(function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $survey, [], "Sensory survey by merchandise listing", $this->success);
    }
}
