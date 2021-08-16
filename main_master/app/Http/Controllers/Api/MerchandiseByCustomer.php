<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CustomerInfo;
use App\Model\CustomerVisit;
use App\Model\PricingCheck;
use App\Model\PricingCheckDetail;
use App\Model\PricingCheckDetailPrice;
use Illuminate\Http\Request;
use Carbon\CarbonPeriod;

class MerchandiseByCustomer extends Controller
{
    public function assetMerchandiserbyCustomer($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating planogram", $this->unauthorized);
        }

        $customer_info = CustomerInfo::where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname,email',
                'merchandiser:id,firstname,lastname,email',
                'assetTracking'
            )
            ->whereHas('assetTracking', function ($q) {
                $q->where('start_date', '<=', date('Y-m-d'));
                $q->where('end_date', '>=', date('Y-m-d'));
            })
            ->get();

        $merge_all_data = array();
        if (is_object($customer_info)) {
            foreach ($customer_info as $key => $customer_info1) {
                $merge_all_data[] = $customer_info[$key];
            }
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
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($merge_all_data);
        } else {
            $data_array = $merge_all_data;
        }

        return prepareResult(true, $data_array, [], "Asset tracking customer listing", $this->success, $pagination);
    }

    /**
     * customer of the specified route.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function merchandiserbyCustomer(Request $request, $merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        // $input = $request->json()->all();
        // $validate = $this->validations($input, "allCustomer");
        // if ($validate["error"]) {
        //     return prepareResult(false, [], $validate['errors']->first(), "Error while validating route customer", $this->unprocessableEntity);
        // }

        $customers = CustomerInfo::with(
            'user:id,uuid,firstname,lastname',
            'merchandiser',
            'paymentTerm:id,name,number_of_days',
            'region:id,region_name',
            'route:id,route_name,area_id,depot_id',
            'route.depot:id,depot_name',
            'route.areas:id,area_name',
            'salesOrganisation:id,parent_id,name,node_level',
            'channel',
            'customerGroup',
            'customerCategory',
            'shipToParty',
            'soldToParty',
            'payer',
            'billToPayer'
        )
            ->where('merchandiser_id', $merchandiser_id)
            ->get();

        $customers_array = array();
        if (is_object($customers)) {
            foreach ($customers as $key => $customer) {

                $customer_visit = CustomerVisit::where('customer_id', $customer->user_id)
                    ->where('salesman_id', $merchandiser_id)
                    ->orderBy('added_on', 'DESC')
                    ->first();
                if (is_object($customer_visit)) {
                    $customers[$key]->last_visit = $customer_visit->added_on;
                } else {
                    $customers[$key]->last_visit = 'N/A';
                }
                $customers_array[] = $customers[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($customers_array[$offset])) {
                    $data_array[] = $customers_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($customers_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($customers_array);
        } else {
            $data_array = $customers_array;
        }

        return prepareResult(true, $data_array, [], "Merchandiser customer listed successfully", $this->success, $pagination);
    }

    public function merchandiserbyPortfolio(Request $request, $merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating planogram", $this->unauthorized);
        }

        $customer_info = CustomerInfo::where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                'portfolioManagementCustomer:id,portfolio_management_id,user_id',
                'portfolioManagementCustomer.priceCheck:id,salesman_id,customer_id,brand_id,date,added_on',
                'portfolioManagementCustomer.portfolioManagement:id,organisation_id,code,name,status,start_date,end_date',
                'portfolioManagementCustomer.portfolioManagement.portfolioManagementItem:id,portfolio_management_id,item_id,listing_fees,store_price',
                'portfolioManagementCustomer.portfolioManagement.portfolioManagementItem.item:id,item_name,item_major_category_id,brand_id',
                'portfolioManagementCustomer.portfolioManagement.portfolioManagementItem.item.brand:id,brand_name',
                'portfolioManagementCustomer.portfolioManagement.portfolioManagementItem.item.itemMajorCategory:id,name'
                // 'portfolioManagementCustomer.portfolioManagement.portfolioManagementItem.item.pricingCheckDetail:id,pricing_check_id,item_id,item_major_category_id,date,srp,price'
            )
            ->whereHas('portfolioManagementCustomer.portfolioManagement', function ($q) {
                $q->whereDate('start_date', '<=', date('Y-m-d'));
                $q->whereDate('end_date', '>=', date('Y-m-d'));
            })
            ->get();

        // return prepareResult(true, $customer_info, [], "Portfolio managment customer listing", $this->success);

        // $pricing_checks = array();
        // if (count($customer_info)) {
        //     foreach ($customer_info as $cKey => $customer) {
        //         if (count($customer->portfolioManagementCustomer)) {
        //             foreach ($customer->portfolioManagementCustomer as $pcKey => $portfolioCustomer) {
        //                 if (is_object($portfolioCustomer->portfolioManagement)) {
        //                     foreach ($portfolioCustomer->portfolioManagement->portfolioManagementItem as $ptKey => $portfolioItem) {
        //                         $customer_id = $customer->user_id;
        //                         $item_id = $portfolioItem->item_id;
        //
        //                         // $pricing_check = PricingCheck::select('id', 'salesman_id', 'customer_id', 'brand_id', 'date', 'added_on')
        //                         //     ->with(
        //                         //         'pricingDetails:id,pricing_check_id,item_id,item_major_category_id',
        //                         //         'pricingDetails.pricingCheckDetailPrice:id,pricing_check_id,pricing_check_detail_id,price,srp'
        //                         //     )
        //                         //     ->where('customer_id', $customer_id)
        //                         //     ->where('salesman_id', $merchandiser_id)
        //                         //     ->whereHas('pricingDetails', function ($q) use ($item_id) {
        //                         //         $q->where('item_id', $item_id);
        //                         //     })
        //                         //     ->orderBy('added_on', 'desc')
        //                         //     ->first();
        //                         //
        //                         // if (is_object($pricing_check)) {
        //                         //     $pricing_check_detail = PricingCheckDetail::where('pricing_check_id', $pricing_check->id)
        //                         //     ->first();
        //                         //     if (is_object($pricing_check_detail)) {
        //                         //         $pricing_check_detail_price = PricingCheckDetailPrice::where('pricing_check_id', $pricing_check->id)
        //                         //         ->where('pricing_check_detail_id', $pricing_check_detail->id)
        //                         //         ->orderBy('created_at', 'desc')
        //                         //         ->first();
        //                         //
        //                         //         if (is_object($pricing_check_detail_price)) {
        //                         //             $pricing_checks[] = $pricing_check_detail_price;
        //                         //         }
        //                         //     }
        //                         // }
        //                         $customer_info[$cKey]->portfolioManagementCustomer[$pcKey]->portfolioManagement->portfolioManagementItem[$ptKey]->item->pricingCheck = $pricing_checks;
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }

        $merge_all_data = array();
        if (is_object($customer_info)) {
            foreach ($customer_info as $key => $customer_info1) {
                $merge_all_data[] = $customer_info[$key];
            }
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
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($merge_all_data);
        } else {
            $data_array = $merge_all_data;
        }

        return prepareResult(true, $data_array, [], "Portfolio managment customer listing", $this->success, $pagination);
    }

    public function merchandiserbyActivity($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating planogram", $this->unauthorized);
        }

        $customer_info = CustomerInfo::where('merchandiser_id', $merchandiser_id)
            ->with(
                'user:id,firstname,lastname',
                'merchandiser:id,firstname,lastname',
                'salesmanActivityProfiles',
                'salesmanActivityProfiles.salesmanActivityProfileDetail'
            )
            ->whereHas('salesmanActivityProfiles', function ($q) {
                $q->where('valid_from', '<=', date('Y-m-d'));
                $q->where('valid_to', '>=', date('Y-m-d'));
            })
            ->get();

        $merge_all_data = array();
        if (is_object($customer_info)) {
            foreach ($customer_info as $key => $customer_info1) {
                $merge_all_data[] = $customer_info[$key];
            }
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
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($merge_all_data);
        } else {
            $data_array = $merge_all_data;
        }

        return prepareResult(true, $data_array, [], "Portfolio managment customer listing", $this->success, $pagination);
    }
}
