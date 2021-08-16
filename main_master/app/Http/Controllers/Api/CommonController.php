<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Collection;
use App\Model\CompetitorInfo;
use App\Model\ComplaintFeedback;
use App\Model\CreditNote;
use App\Model\CustomerInfo;
use App\Model\DebitNote;
use App\Model\Delivery;
use App\Model\Estimation;
use App\Model\Expense;
use App\Model\Invoice;
use App\Model\Order;
use App\Model\PurchaseOrder;
use App\Model\SalesmanInfo;
use App\Model\Vendor;
use App\Model\AssignInventory;
use App\Model\AssignInventoryCustomer;
use App\Model\AssignInventoryDetails;
use App\Model\AssignInventoryPost;
use App\Model\CampaignPicture;
use App\Model\Planogram;
use App\Model\AssetTracking;
use App\Model\Survey;
use App\Model\Promotional;
use App\Model\Distribution;

use App\User;
use App\Model\Item;
use App\Model\JourneyPlan;
use App\Model\PricingCheck;
use App\Model\ShareOfAssortment;
use App\Model\ShareOfDisplay;
use App\Model\SOS;

use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;

class CommonController extends Controller
{

    public function advancedSearch(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }


        $input = $request->json()->all();
        //        $validate = $this->validations($input, "comment");
        //        if ($validate["error"]) {
        //            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer CommentS", $this->unprocessableEntity);
        //        }

        //        if ($request->customer_code) {
        //            $exist = CustomerInfo::where('customer_code', $request->customer_code)->first();
        //            if (is_object($exist)) {
        //                return prepareResult(false, [], 'Customer code is already exist', "Error while validating Customer", $this->unprocessableEntity);
        //            }
        //        }

        $allData = (isset($input['allData'])) ? $input['allData'] : '';
        $page = (isset($input['page'])) ? $input['page'] : '';
        $limit = (isset($input['page_size'])) ? $input['page_size'] : '';
        $pagination = array();

        if ($input['module'] == "pricing-check") {

            $brand_ids = (isset($input['brand']) ? $input['brand'] : '');
            $category_ids = (isset($input['category']) ? $input['category'] : '');
            $customer_ids = (isset($input['customer']) ? $input['customer'] : '');
            $endrange = (isset($input['enddate']) ? $input['enddate'] : '');
            $salesman_ids = (isset($input['merchandiser']) ? $input['merchandiser'] : '');
            $startrange = (isset($input['startdate']) ? $input['startdate'] : '');

            $pricing_check_query = PricingCheck::select('id', 'organisation_id', 'brand_id', 'salesman_id', 'customer_id', 'date')
                ->with(
                    'brand:id,brand_name',
                    'customer:id,firstname,lastname',
                    'customer.customerInfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmanInfo:id,user_id,salesman_code',
                    'pricingDetails',
                    'pricingDetails.item:id,item_name',
                    'pricingDetails.itemMajorCategory:id,name'
                );

            if (is_array($salesman_ids)) {
                $pricing_check_query->whereIn('salesman_id', $salesman_ids);
            }

            if (is_array($customer_ids)) {
                $pricing_check_query->whereIn('customer_id', $customer_ids);
            }

            if (is_array($brand_ids)) {
                $pricing_check_query->whereIn('brand_id', $brand_ids);
            }

            if ($startrange && $endrange) {
                $pricing_check_query->whereBetween('date', [$startrange, $endrange]);
            }

            if ($startrange) {
                $pricing_check_query->whereDate('date', $startrange);
            }

            if ($endrange) {
                $pricing_check_query->whereDate('date', $endrange);
            }

            if ($category_ids) {
                $pricing_check_query->whereHas('pricingDetails', function ($q) use ($category_ids) {
                    $q->whereIn('item_major_category_id', $category_ids);
                });
            }

            $pricing_check = $pricing_check_query->orderBy('id', 'desc')->get();

            $pricing_check_array = array();
            if (is_object($pricing_check)) {
                foreach ($pricing_check as $key => $pricing_check1) {
                    $pricing_check_array[] = $pricing_check[$key];
                }
            }

            $data_array = array();
            $page = (isset($request->page)) ? $request->page : '';
            $limit = (isset($request->page_size)) ? $request->page_size : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($pricing_check_array[$offset])) {
                        $data_array[] = $pricing_check_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($pricing_check_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($pricing_check_array);
            } else {
                $data_array = $pricing_check_array;
            }

            return prepareResult(true, $data_array, [], "Pricing checking listing", $this->success, $pagination);
        }

        if ($input['module'] == "sos") {

            $brand_ids = (isset($input['brand']) ? $input['brand'] : '');
            $category_ids = (isset($input['category']) ? $input['category'] : '');
            $customer_ids = (isset($input['customer']) ? $input['customer'] : '');
            $endrange = (isset($input['enddate']) ? $input['enddate'] : '');
            $salesman_ids = (isset($input['merchandiser']) ? $input['merchandiser'] : '');
            $startrange = (isset($input['startdate']) ? $input['startdate'] : '');

            $sos_query = SOS::select('id', 'salesman_id', 'customer_id', 'date', 'no_of_Shelves', 'block_store')
                ->with(
                    'customer:id,firstname,lastname',
                    'customer.customerInfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmanInfo:id,user_id,salesman_code',
                    'sosOurBrand',
                    'sosOurBrand.brand:id,brand_name',
                    'sosOurBrand.itemMajorCategory:id,name',
                    'sosCompetitor.brand:id,brand'
                );

            if (is_array($salesman_ids)) {
                $sos_query->whereIn('salesman_id', $salesman_ids);
            }

            if (is_array($customer_ids)) {
                $sos_query->whereIn('customer_id', $customer_ids);
            }

            if ($startrange && $endrange) {
                $sos_query->whereBetween('date', [$startrange, $endrange]);
            }

            if ($startrange) {
                $sos_query->whereDate('date', $startrange);
            }

            if ($endrange) {
                $sos_query->whereDate('date', $endrange);
            }

            if ($brand_ids) {
                $sos_query->whereHas('sosOurBrand', function ($q) use ($brand_ids) {
                    $q->whereIn('brand_id', $brand_ids);
                });
            }

            if ($category_ids) {
                $sos_query->whereHas('sosOurBrand', function ($q) use ($category_ids) {
                    $q->whereIn('item_major_category_id', $category_ids);
                });
            }

            $sos = $sos_query->orderBy('id', 'desc')->get();

            $sos_array = array();
            if (is_object($sos)) {
                foreach ($sos as $key => $sos1) {
                    $sos_array[] = $sos[$key];
                }
            }

            $data_array = array();
            $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
            $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($sos_array[$offset])) {
                        $data_array[] = $sos_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($sos_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($sos_array);
            } else {
                $data_array = $sos_array;
            }

            return prepareResult(true, $data_array, [], "Share of shalves listing", $this->success, $pagination);
        }

        if ($input['module'] == "soa") {

            $brand_ids = (isset($input['brand']) ? $input['brand'] : '');
            $customer_ids = (isset($input['customer']) ? $input['customer'] : '');
            $category_ids = (isset($input['category']) ? $input['category'] : '');
            $endrange = (isset($input['enddate']) ? $input['enddate'] : '');
            $salesman_ids = (isset($input['merchandiser']) ? $input['merchandiser'] : '');
            $startrange = (isset($input['startdate']) ? $input['startdate'] : '');

            $share_of_assortment_query = ShareOfAssortment::select('id', 'salesman_id', 'customer_id', 'date', 'no_of_sku')
                ->with(
                    'customer:id,firstname,lastname',
                    'customer.customerInfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmanInfo:id,user_id,salesman_code',
                    'shareOurBrand:id,share_of_assortment_id,brand_id,captured_sku,brand_share,item_major_category_id',
                    'shareOurBrand.brand:id,brand_name',
                    'shareOurBrand.itemMajorCategory:id,name',
                    'shareCompetitor:id,share_of_assortment_id,competitor_info_id,competitor_sku,brand_share',
                    'shareCompetitor.competitorInfo:id,company,item,price,brand,note'
                );

            if (is_array($salesman_ids)) {
                $share_of_assortment_query->whereIn('salesman_id', $salesman_ids);
            }

            if (is_array($customer_ids)) {
                $share_of_assortment_query->whereIn('customer_id', $customer_ids);
            }

            if ($startrange && $endrange) {
                $share_of_assortment_query->whereBetween('date', [$startrange, $endrange]);
            }

            if ($startrange) {
                $share_of_assortment_query->whereDate('date', $startrange);
            }

            if ($endrange) {
                $share_of_assortment_query->whereDate('date', $endrange);
            }

            if ($brand_ids) {
                $share_of_assortment_query->whereHas('shareOurBrand', function ($q) use ($brand_ids) {
                    $q->whereIn('brand_id', $brand_ids);
                });
            }

            if ($category_ids) {
                $share_of_assortment_query->whereHas('shareOurBrand', function ($q) use ($category_ids) {
                    $q->whereIn('item_major_category_id', $category_ids);
                });
            }

            $share_of_assortment = $share_of_assortment_query->orderBy('id', 'desc')->get();

            $share_of_assortment_array = array();
            if (is_object($share_of_assortment)) {
                foreach ($share_of_assortment as $key => $share_of_assortment1) {
                    $share_of_assortment_array[] = $share_of_assortment[$key];
                }
            }

            $data_array = array();
            $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
            $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($share_of_assortment_array[$offset])) {
                        $data_array[] = $share_of_assortment_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($share_of_assortment_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($share_of_assortment_array);
            } else {
                $data_array = $share_of_assortment_array;
            }

            return prepareResult(true, $data_array, [], "Share assortment listing", $this->success, $pagination);
        }

        if ($input['module'] == "journey_plan") {

            $route = (isset($input['route']) ? $input['route'] : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');
            $startrange = (isset($input['startdate']) ? $input['startdate'] : '');
            $endrange = (isset($input['enddate']) ? $input['enddate'] : '');
            $name = (isset($input['name']) ? $input['name'] : '');

            $journey_plan_query = JourneyPlan::select('id', 'route_id', 'name', 'description', 'start_date', 'end_date', 'plan_type', 'current_stage', 'status')
                ->with(
                    'route:id,route_name',
                    'merchandiser:id,firstname,lastname',
                );

            if ($name) {
                $journey_plan_query->where('name', 'like', '%' . $name . '%');
            }

            if ($salesman) {
                $journey_plan_query->where('is_merchandiser', 1);
                $journey_plan_query->where('merchandiser_id', $salesman);
            }

            if ($route) {
                $journey_plan_query->where('route_id', $route);
            }

            if ($startrange && $endrange) {
                $journey_plan_query->whereDate('start_date', '<=', $startrange);
                $journey_plan_query->whereDate('end_date', '>=', $endrange);
                $journey_plan_query->where('no_end_date', 1);
            }

            if ($startrange) {
                $journey_plan_query->whereDate('start_date', '<=', $startrange);
            }

            if ($endrange) {
                $journey_plan_query->whereDate('end_date', '>=', $endrange);
                $journey_plan_query->where('no_end_date', 1);
            }

            $journey_plan = $journey_plan_query->orderBy('id', 'desc')->get();

            $journey_plan_array = array();
            if (is_object($journey_plan)) {
                foreach ($journey_plan as $key => $journey_plan1) {
                    $journey_plan_array[] = $journey_plan[$key];
                }
            }

            $data_array = array();
            $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
            $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($journey_plan_array[$offset])) {
                        $data_array[] = $journey_plan_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($journey_plan_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($journey_plan_array);
            } else {
                $data_array = $journey_plan_array;
            }

            return prepareResult(true, $data_array, [], "Share display listing", $this->success, $pagination);
        }

        if ($input['module'] == "sod") {

            $salesman_ids = (isset($input['merchandiser']) ? $input['merchandiser'] : '');
            $customer_ids = (isset($input['customer']) ? $input['customer'] : '');
            $startrange = (isset($input['startdate']) ? $input['startdate'] : '');
            $endrange = (isset($input['enddate']) ? $input['enddate'] : '');
            $brand_ids = (isset($input['brand']) ? $input['brand'] : '');
            $category_ids = (isset($input['category']) ? $input['category'] : '');
            $customer_code = (isset($input['customer_code']) ? $input['customer_code'] : '');

            $share_of_display_query = ShareOfDisplay::select('id', 'salesman_id', 'customer_id', 'date', 'gandola_store', 'stands_store')
                ->with(
                    'salesman:id,firstname,lastname',
                    'salesman.salesmanInfo:id,user_id,salesman_code',
                    'customer:id,firstname,lastname',
                    'customer.customerInfo:id,user_id,customer_code',
                    'shareOfDisplayOurBrand',
                    'shareOfDisplayOurBrand.brand:id,brand_name',
                    'shareOfDisplayOurBrand.itemMajorCategory:id,name',
                    'shareOfDisplayCompetitor.brand:id,brand'
                );
            if (is_array($salesman_ids)) {
                $share_of_display_query->whereIn('salesman_id', $salesman_ids);
            }

            if (is_array($customer_ids)) {
                $share_of_display_query->whereIn('customer_id', $customer_ids);
            }

            if ($startrange && $endrange) {
                $share_of_display_query->whereBetween('date', [$startrange, $endrange]);
            }

            if ($startrange) {
                $share_of_display_query->whereDate('date', $startrange);
            }

            if ($endrange) {
                $share_of_display_query->whereDate('date', $endrange);
            }

            if ($brand_ids) {
                $share_of_display_query->whereHas('shareOfDisplayOurBrand', function ($q) use ($brand_ids) {
                    $q->whereIn('brand_id', $brand_ids);
                });
            }

            if ($category_ids) {
                $share_of_display_query->whereHas('shareOfDisplayOurBrand', function ($q) use ($category_ids) {
                    $q->whereIn('item_major_category_id', $category_ids);
                });
            }

            if ($customer_code) {
                $share_of_display_query->whereHas('customer.customerInfo', function ($q) use ($customer_code) {
                    $q->whereIn('customer_code', $customer_code);
                });
            }

            $share_of_display = $share_of_display_query->orderBy('id', 'desc')->get();

            $share_of_display_array = array();
            if (is_object($share_of_display)) {
                foreach ($share_of_display as $key => $share_of_display1) {
                    $share_of_display_array[] = $share_of_display[$key];
                }
            }

            $data_array = array();
            $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
            $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($share_of_display_array[$offset])) {
                        $data_array[] = $share_of_display_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($share_of_display_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($share_of_display_array);
            } else {
                $data_array = $share_of_display_array;
            }

            return prepareResult(true, $data_array, [], "Share display listing", $this->success, $pagination);
        }

        if ($input['module'] == "customer") {
            //Missing - status
            $firstname = (isset($input['firstname']) ? $input['firstname'] : '');
            $lastname = (isset($input['lastname']) ? $input['lastname'] : '');
            $customer_code = (isset($input['customer_code']) ? $input['customer_code'] : '');
            $email = (isset($input['email']) ? $input['email'] : '');
            $route = (isset($input['route']) ? $input['route'] : '');
            $region = (isset($input['region']) ? $input['region'] : '');
            $sales_organisation = (isset($input['sales_organisation']) ? $input['sales_organisation'] : '');
            $channel = (isset($input['channel']) ? $input['channel'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');

            $selectData = CustomerInfo::with(
                'user:id,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status,parent_id',
                'route:id,route_code,route_name,status',
                'channel:id,name,status',
                'region:id,region_name,region_status',
                'customerGroup:id,group_code,group_name',
                'salesOrganisation:id,name',
                'shipToParty:id,user_id',
                'shipToParty.user:id,firstname,lastname',
                'soldToParty:id,user_id',
                'soldToParty.user:id,firstname,lastname',
                'payer:id,user_id',
                'payer.user:id,firstname,lastname',
                'billToPayer:id,user_id',
                //'paymentTerm:id,name,number_of_days',
                'billToPayer.user:id,firstname,lastname'
            );
            if (!$allData) {
                if ($route) {
                    $selectData->where('route_id', $route);
                }
                if ($region) {
                    $selectData->where('region_id', $region);
                }
                if ($customer_code) {
                    $selectData->where('customer_code', $customer_code);
                }
                if ($sales_organisation) {
                    $selectData->where('sales_organisation_id', $sales_organisation);
                }
                if ($channel) {
                    $selectData->where('channel_id', $channel);
                }
                if ($status) {
                    $selectData->where('current_stage', 'like', "%{$status}%");
                }
                if ($firstname or $lastname or $email) {
                    $selectData->whereHas('user', function ($query) use ($firstname, $lastname, $email) {
                        if ($firstname) {
                            $query->where('firstname', 'like', "%{$firstname}%");
                        }
                        if ($lastname) {
                            $query->where('lastname', 'like', "%{$lastname}%");
                        }
                        if ($email) {
                            $query->where('email', 'like', "%{$email}%");
                        }
                    });
                }

                $data = $selectData->orderBy('id', 'desc')->get()->toArray();

                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "item") {

            $item_name = (isset($input['item_name']) ? $input['item_name'] : '');
            $price = (isset($input['price']) ? $input['price'] : '');
            $erp_code = (isset($input['erp_code']) ? $input['erp_code'] : '');
            $item_code = (isset($input['item_code']) ? $input['item_code'] : '');
            $category = (isset($input['category']) ? $input['category'] : '');
            $brand = (isset($input['brand_id']) ? $input['brand_id'] : '');
            $item_group = (isset($input['item_group']) ? $input['item_group'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');

            $selectData = Item::with(
                'itemUomLowerUnit:id,name,code',
                'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price,purchase_order_price',
                'ItemMainPrice.itemUom:id,name,code',
                'itemMajorCategory:id,name',
                'itemGroup:id,uuid,name,code,status',
                'brand:id,uuid,brand_name,status'
            );
            if (!$allData) {
                if ($item_name)
                    $selectData->where('item_name', 'like', "%{$item_name}%");
                if ($erp_code)
                    $selectData->where('erp_code', $erp_code);
                if ($item_code)
                    $selectData->where('item_code', $item_code);
                if ($category)
                    $selectData->where('item_major_category_id', $category);
                if ($brand)
                    $selectData->where('brand_id', $brand);
                if ($item_group)
                    $selectData->where('item_group_id', $item_group);
                if ($status)
                    $selectData->where('current_stage', 'like', "%{$status}%");
                if ($price) {
                    $selectData->whereHas('ItemMainPrice', function ($query) use ($price) {
                        $query->where('item_price', 'like', "%{$price}%");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();

                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "saleman") {
            //Missing - salesman type - status
            $salesman_code = (isset($input['salesman_code']) ? $input['salesman_code'] : '');
            $firstname = (isset($input['firstname']) ? $input['firstname'] : '');
            $lastname = (isset($input['lastname']) ? $input['lastname'] : '');
            $role_id = (isset($input['role_id']) ? $input['role_id'] : '');
            $email = (isset($input['email']) ? $input['email'] : '');
            $mobile = (isset($input['mobile']) ? $input['mobile'] : '');
            $route = (isset($input['route']) ? $input['route'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');

            $selectData = SalesmanInfo::with(
                'user:id,uuid,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status',
                'organisation:id,org_name',
                'route:id,route_code,route_name,status',
                'salesmanRole:id,name,code,status',
                'salesmanType:id,name,code,status',
                'salesmanRange'
            );
            if (!$allData) {
                if ($firstname or $lastname or $email or $role_id or $mobile) {
                    $selectData->whereHas('user', function ($query) use ($firstname, $lastname, $email, $role_id, $mobile) {
                        if ($firstname) {
                            $query->where('firstname', 'like', "%{$firstname}%");
                        }
                        if ($lastname) {
                            $query->where('lastname', 'like', "%{$lastname}%");
                        }
                        if ($role_id) {
                            $query->where('role_id', '=', $role_id);
                        }
                        if ($email) {
                            $query->where('email', 'like', "%{$email}%");
                        }
                        if ($mobile) {
                            $query->where('mobile', 'like', "%{$mobile}%");
                        }
                    });
                }
                if ($route) {
                    $selectData->where('route_id', '=', $route);
                }
                if ($salesman_code) {
                    $selectData->where('salesman_code', $salesman_code);
                }
                if ($status) {
                    $selectData->where('current_stage', 'like', "%{$status}%");
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "invoice") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $invoice_no = (isset($input['invoice_no']) ? $input['invoice_no'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');

            //            $invoice = Invoice::select('*')
            //                ->join('users', 'invoices.customer_id','=','users.id');

            $selectData = Invoice::with(
                'user:id,parent_id,firstname,lastname',
                'order',
                'order.orderDetails',
                'invoices',
                'invoices.item:id,item_name',
                'invoices.itemUom:id,name,code',
                'orderType:id,name,description'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('invoice_date', array("$startdate", "$enddate"));
                if ($invoice_no)
                    $selectData->where('invoice_number', 'like', "%{$invoice_no}%");
                if ($status)
                    $selectData->where('current_stage', '=', $status);
                if ($salesman)
                    $selectData->where('salesman_id', '=', $salesman);
                if ($startrange)
                    $selectData->whereBetween('grand_total', array("$startrange", "$endrange"));
                if ($customerName) {
                    $selectData->whereHas('user', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%'");
                    });
                    //                $selectData->where('user', function ($query) use ($customerName) {
                    //                    $query->where("concat(users.firstname, ' ',users.lastname) like '%$customerName%' ");
                    //                });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "delivery") {
            //Missing - saleman
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $delivery_no = (isset($input['delivery_no']) ? $input['delivery_no'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');


            $selectData = Delivery::with(
                'order',
                'orderType:id,name',
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'invoice',
                'paymentTerm:id,name,number_of_days',
                'deliveryDetails',
                'deliveryDetails.item:id,item_name',
                'deliveryDetails.itemUom:id,name'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('delivery_date', array("$startdate", "$enddate"));
                if ($delivery_no)
                    $selectData->where('delivery_number', 'like', "%{$delivery_no}%");
                if ($status)
                    $selectData->where('current_stage', '=', $status);
                if ($salesman)
                    $selectData->where('salesman_id', '=', $salesman);
                if ($startrange)
                    $selectData->whereBetween('grand_total', array("$startrange", "$endrange"));
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "order") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $order_no = (isset($input['order_no']) ? $input['order_no'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');

            $selectData = Order::with(
                'orderType:id,name,description',
                'paymentTerm:id,name,number_of_days',
                'orderDetails',
                'orderDetails.item:id,item_name',
                'orderDetails.itemUom:id,name,code',
                'customer:id,firstname,lastname',
                'depot:id,depot_name'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('order_date', array("$startdate", "$enddate"));
                if ($order_no)
                    $selectData->where('order_number', 'like', "%{$order_no}%");
                if ($status)
                    $selectData->where('current_stage', '=', $status);
                if ($salesman)
                    $selectData->where('salesman_id', '=', $salesman);
                if ($startrange)
                    $selectData->whereBetween('grand_total', array("$startrange", "$endrange"));
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "credit_note") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $credit_note_no = (isset($input['credit_note_no']) ? $input['credit_note_no'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');


            $selectData = CreditNote::with(
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'invoice',
                'creditNoteDetails',
                'creditNoteDetails.item:id,item_name',
                'creditNoteDetails.itemUom:id,name,code'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('credit_note_date', array("$startdate", "$enddate"));
                if ($credit_note_no)
                    $selectData->where('credit_note_number', 'like', "%{$credit_note_no}%");
                if ($status)
                    $selectData->where('current_stage', '=', $status);
                if ($startrange)
                    $selectData->whereBetween('grand_total', array("$startrange", "$endrange"));
                if ($salesman)
                    $selectData->where('salesman_id', '=', $salesman);
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "debit_note") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $debit_note_no = (isset($input['debit_note_no']) ? $input['debit_note_no'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');


            $selectData = DebitNote::with(
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'invoice',
                'debitNoteDetails',
                'debitNoteDetails.item:id,item_name',
                'debitNoteDetails.itemUom:id,name,code'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('debit_note_date', array("$startdate", "$enddate"));
                if ($debit_note_no)
                    $selectData->where('debit_note_number', 'like', "%{$debit_note_no}%");
                if ($status)
                    $selectData->where('current_stage', '=', $status);
                if ($salesman)
                    $selectData->where('salesman_id', '=', $salesman);
                if ($startrange)
                    $selectData->whereBetween('grand_total', array("$startrange", "$endrange"));
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "collection") {

            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $collection_no = (isset($input['collection_no']) ? $input['collection_no'] : '');
            $payment_method = (isset($input['payment_method']) ? $input['payment_method'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');


            $selectData = Collection::with(
                'invoice',
                'customer',
                'salesman',
                'collectiondetails'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('cheque_date', array("$startdate", "$enddate"));
                if ($collection_no)
                    $selectData->where('collection_number', 'like', "%{$collection_no}%");
                if ($payment_method)
                    $selectData->where('payemnt_type', '=', $payment_method);
                if ($status)
                    $selectData->where('current_stage', '=', $status);
                if ($startrange)
                    $selectData->whereBetween('invoice_amount', array("$startrange", "$endrange"));
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "vendor") {
            $firstame = (isset($input['firstname']) ? $input['firstname'] : '');
            $lastname = (isset($input['lastname']) ? $input['lastname'] : '');
            $company_name = (isset($input['company_name']) ? $input['company_name'] : '');
            $mobile = (isset($input['mobile']) ? $input['mobile'] : '');
            $email = (isset($input['email']) ? $input['email'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');

            $selectData = Vendor::with(
                'organisation:id,org_name'
            );

            if (!$allData) {
                if ($firstame)
                    $selectData->where('firstname', 'like', "%{$firstame}%");
                if ($lastname)
                    $selectData->where('lastname', 'like', "%{$lastname}%");
                if ($company_name)
                    $selectData->where('company_name', 'like', "%{$company_name}%");
                if ($mobile)
                    $selectData->where('mobile', 'like', "%{$mobile}%");
                if ($email)
                    $selectData->where('email', 'like', "%{$email}%");
                if ($status)
                    $selectData->where('status', '=', $status);
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "purchase_order") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $expected_delivery_start_date = (isset($input['expected_delivery_start_date']) ? date("Y-m-d", strtotime($input['expected_delivery_start_date'])) : '');
            $expected_delivery_end_date = (isset($input['expected_delivery_end_date']) ? date("Y-m-d", strtotime($input['expected_delivery_end_date'])) : '');
            $purchase_order_no = (isset($input['purchase_order_no']) ? $input['purchase_order_no'] : '');
            $vendor_name = (isset($input['vendor_name']) ? $input['vendor_name'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');


            $selectData = PurchaseOrder::with(
                'vendor:id,firstname,lastname,email,company_name',
                'purchaseorderdetail',
                'purchaseorderdetail.item:id,item_name',
                'purchaseorderdetail.itemUom:id,name,code'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('purchase_order_date', array("$startdate", "$enddate"));
                if ($expected_delivery_start_date)
                    $selectData->whereBetween('expected_delivery_date', array("$expected_delivery_start_date", "$expected_delivery_end_date"));
                if ($purchase_order_no)
                    $selectData->where('purchase_order', 'like', "%{$purchase_order_no}%");
                if ($status)
                    $selectData->where('status', '=', $status);
                if ($startrange)
                    $selectData->whereBetween('gross_total', array("$startrange", "$endrange"));
                if ($vendor_name) {
                    $selectData->whereHas('vendor', function ($query) use ($vendor_name) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$vendor_name%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "expense") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $categoryName = (isset($input['category_name']) ? $input['category_name'] : '');
            $customerName = (isset($input['customer_name']) ? $input['customer_name'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');

            $selectData = Expense::with(
                'customerInfo:id,user_id',
                'customer:id,firstname,lastname',
                'organisation:id,org_name',
                'expensecategory:id,name'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('expense_date', array("$startdate", "$enddate"));
                if ($status)
                    $selectData->where('status', '=', $status);
                if ($startrange)
                    $selectData->whereBetween('amount', array("$startrange", "$endrange"));
                if ($categoryName) {
                    $selectData->whereHas('expensecategory', function ($query) use ($categoryName) {
                        $query->where('name', 'like', "%{$categoryName}%");
                    });
                }
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "estimate") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $estimate_no = (isset($input['estimate_no']) ? $input['estimate_no'] : '');
            $customerName = (isset($input['customer_name']) ? $input['customer_name'] : '');
            $status = (isset($input['status']) ? $input['status'] : '');
            $startrange = (isset($input['startrange']) ? $input['startrange'] : '');
            $endrange = (isset($input['endrange']) ? $input['endrange'] : '');

            $selectData = Estimation::with(
                'customerInfo:id,user_id',
                'customer:id,firstname,lastname',
                'organisation:id,org_name',
                'salesperson:id,name,email',
                'estimationdetail',
                'estimationdetail.item:id,item_name',
                'estimationdetail.itemUom:id,name'
            );

            if (!$allData) {
                if ($startdate)
                    $selectData->whereBetween('estimate_date', array("$startdate", "$enddate"));
                if ($estimate_no)
                    $selectData->where('estimate_code', 'like', "%{$estimate_no}%");
                if ($status)
                    $selectData->where('status', '=', $status);
                if ($startrange)
                    $selectData->whereBetween('total', array("$startrange", "$endrange"));
                if ($customerName) {
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "stock_in_store") {
            //Missing - status
            $activityName = (isset($input['back_store']) ? $input['back_store'] : '');
            $startdate = (isset($input['valid_from']) ? date("Y-m-d", strtotime($input['valid_from'])) : '');
            $enddate = (isset($input['valid_to']) ? date("Y-m-d", strtotime($input['valid_to'])) : '');
            $customer = (isset($input['customer']) ? $input['customer'] : '');
            $customer_code = (isset($input['customer_code']) ? $input['customer_code'] : '');

            $selectData = AssignInventory::select('id', 'uuid', 'organisation_id', 'activity_name', 'valid_from', 'valid_to', 'status')
                ->with(
                    'assignInventoryCustomer',
                    'assignInventoryCustomer.customer:id,firstname,lastname',
                    'assignInventoryCustomer.customer.customerInfo:id,user_id,customer_code',
                    'assignInventoryDetails:id,uuid,assign_inventory_id,item_id,item_uom_id',
                    'assignInventoryDetails.item:id,item_name,item_code',
                    'assignInventoryDetails.itemUom:id,name'
                );
            if (!$allData) {

                if ($startdate) :
                    $selectData->where('valid_from', $startdate);
                endif;
                if ($enddate) :
                    $selectData->where('valid_to', $enddate);
                endif;
                if ($activityName) :
                    $selectData->where('activity_name', 'like', "%{$activityName}%");
                endif;
                if (is_array($customer)) :
                    $selectData->whereHas('assignInventoryCustomer', function ($query) use ($customer) {
                        $query->whereIn('customer_id', $customer);
                    });
                endif;
                if ($customer_code) :
                    $selectData->whereHas('assignInventoryCustomer.customer.customerInfo', function ($query) use ($customer_code) {
                        $query->where('customer_code', $customer_code);
                    });
                endif;

                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "complaint") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');

            $selectData = ComplaintFeedback::with(
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'item:id,item_name,item_code',
                'route:id,route_name',
                'complaintFeedbackImage'
            );

            if (!$allData) {
                if ($startdate) :
                    $selectData->whereBetween('created_at', array("$startdate", "$enddate"));
                endif;
                if ($salesman) :
                    $selectData->where('salesman_id', '=', $salesman);
                endif;
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }
        if ($input['module'] == "competitor") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');

            $selectData = CompetitorInfo::with(
                'competitorInfoImage',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code'
            );

            if (!$allData) {
                if ($startdate) :
                    $selectData->whereBetween('created_at', array("$startdate", "$enddate"));
                endif;
                if ($salesman) :
                    $selectData->where('salesman_id', $salesman);
                endif;
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }
        if ($input['module'] == "campaign") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $salesman = (isset($input['salesman']) ? $input['salesman'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $customer_code = (isset($input['customer_code']) ? $input['customer_code'] : '');

            $selectData = CampaignPicture::with(
                'campaignPictureImage',
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code'
            );

            if (!$allData) {
                if ($startdate) :
                    $selectData->whereBetween('created_at', array("$startdate", "$enddate"));
                endif;
                if ($salesman) :
                    $selectData->where('salesman_id', '=', $salesman);
                endif;
                if ($customerName) :
                    $selectData->whereHas('customer', function ($query) use ($customerName) {
                        $query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%'");
                    });
                endif;
                if ($customer_code) {
                    $selectData->whereHas('customer', function ($query) use ($customer_code) {
                        $query->where('customer_code', $customer_code);
                    });
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }
        if ($input['module'] == "planogram") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $customer = (isset($input['customer']) ? $input['customer'] : '');

            $selectData = Planogram::with(
                'planogramImage',
                'planogramPost',
                'planogramCustomer',
                'planogramCustomer.customer:id,firstname,lastname',
                'planogramCustomer.customer.customerInfo:id,user_id,customer_code',
            );

            if (!$allData) {
                if ($startdate) :
                    $selectData->whereDate('start_date', '<=', date('Y-m-d', strtotime($startdate)));
                endif;
                if ($enddate) :
                    $selectData->whereDate('end_date', '>=', date('Y-m-d', strtotime($enddate)));
                endif;
                if ($customer) :
                    $selectData->whereHas('planogramCustomer', function ($q) use ($customer) {
                        $q->whereIn('customer_id', $customer);
                    });
                endif;
                if ($customer_code) :
                    $selectData->whereHas('planogramCustomer.customer.customerInfo', function ($q) use ($customer_code) {
                        $q->where('customer_code', $customer_code);
                    });
                endif;

                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "asset-tracking") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $customer = (isset($input['customer']) ? $input['customer'] : '');
            $assettrackingname = (isset($input['assettrackingname']) ? $input['assettrackingname'] : '');

            $selectData = AssetTracking::with('customer:id,firstname,lastname');

            if (!$allData) {
                if ($startdate) {
                    $selectData->whereDate('start_date', '<=', date('Y-m-d', strtotime($startdate)));
                }
                if ($enddate) {
                    $selectData->whereDate('end_date', '>=', date('Y-m-d', strtotime($enddate)));
                }
                if ($customer) {
                    $selectData->where('customer_id', '=', $customer);
                }
                if ($assettrackingname) {
                    $selectData->where('title', 'like', "%{$assettrackingname}%");
                }

                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }
        if ($input['module'] == "consumer-survey") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $surveyName = (isset($input['surveyName']) ? $input['surveyName'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');
            $customer_code = (isset($input['customer_code']) ? $input['customer_code'] : '');

            $selectData = Survey::with(
                'surveyType:id,survey_name',
                'surveyCustomer:id,survey_id,survey_type_id,customer_id',
                'surveyCustomer.customer:id,firstname,lastname',
                'distribution'
            )
                ->where('survey_type_id', '2');

            if (!$allData) {
                if ($startdate) :
                    $selectData->whereDate('start_date', '<=', date('Y-m-d', strtotime($startdate)));
                endif;
                if ($enddate) :
                    $selectData->whereDate('end_date', '>=', date('Y-m-d', strtotime($enddate)));
                endif;
                if ($surveyName) :
                    $selectData->where('name', 'like', "%{$surveyName}%");
                endif;
                if ($customerName) :
                    $selectData->whereHas('surveyCustomer', function ($query) use ($customerName) {
                        //$query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                        $query->whereHas('customer', function ($query1) use ($customerName) {
                            $query1->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%'");
                        });
                    });
                endif;
                if ($customer_code) :
                    $selectData->whereHas('surveyCustomer', function ($query) use ($customer_code) {
                        $query->whereHas('customer', function ($query1) use ($customer_code) {
                            $query1->whereHas('customerInfo', function ($query2) use ($customer_code) {
                                $query2->where("customer_code", $customer_code);
                            });
                        });
                    });
                endif;
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "sensory-survey") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $surveyName = (isset($input['surveyName']) ? $input['surveyName'] : '');
            $customerName = (isset($input['customerName']) ? $input['customerName'] : '');

            $selectData = Survey::with(
                'surveyType:id,survey_name',
                'surveyCustomer:id,survey_id,survey_type_id,customer_id',
                'surveyCustomer.customer:id,firstname,lastname',
                'distribution'
            )
                ->where('survey_type_id', '3');

            if (!$allData) {
                if ($startdate) :
                    $selectData->whereDate('start_date', '<=', date('Y-m-d', strtotime($startdate)));
                endif;
                if ($enddate) :
                    $selectData->whereDate('end_date', '>=', date('Y-m-d', strtotime($enddate)));
                endif;
                if ($surveyName) :
                    $selectData->where('name', 'like', "%{$surveyName}%");
                endif;
                if ($customerName) :
                    $selectData->whereHas('surveyCustomer', function ($query) use ($customerName) {
                        //$query->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%' ");
                        $query->whereHas('customer', function ($query1) use ($customerName) {
                            $query1->whereRaw("concat(firstname, ' ',lastname) like '%$customerName%'");
                        });
                    });
                endif;
                if ($customer_code) :
                    $selectData->whereHas('surveyCustomer', function ($query) use ($customer_code) {
                        $query->whereHas('customer', function ($query1) use ($customer_code) {
                            $query1->whereHas('customerInfo', function ($query2) use ($customer_code) {
                                $query2->where("customer_code", $customer_code);
                            });
                        });
                    });
                endif;
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }
        if ($input['module'] == "promotional") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $itemName = (isset($input['itemName']) ? $input['itemName'] : '');
            $item_code = (isset($input['item_code']) ? $input['item_code'] : '');
            $amount = (isset($input['amount']) ? $input['amount'] : '');

            $selectData =  Promotional::with('item:id,item_name,item_code');

            if (!$allData) {
                if ($startdate) {
                    $selectData->whereDate('start_date', '<=', date('Y-m-d', strtotime($startdate)));
                }
                if ($enddate) {
                    $selectData->whereDate('end_date', '>=', date('Y-m-d', strtotime($enddate)));
                }
                if ($itemName) {
                    $selectData->whereHas('item', function ($query) use ($itemName) {
                        $query->where('item_name', 'like', "%{$itemName}%");
                    });
                }
                if ($item_code) {
                    $selectData->whereHas('item', function ($query) use ($item_code) {
                        $query->where('item_code', $item_code);
                    });
                }
                if ($amount) {
                    $selectData->where('amount', 'like', "%{$amount}%");
                }
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        if ($input['module'] == "shelf-display") {
            $startdate = (isset($input['startdate']) ? date("Y-m-d", strtotime($input['startdate'])) : '');
            $enddate = (isset($input['enddate']) ? date("Y-m-d", strtotime($input['enddate'])) : '');
            $distributionName = (isset($input['display_name']) ? $input['display_name'] : '');
            $customer = (isset($input['customer']) ? $input['customer'] : '');
            $customer_code = (isset($input['customer_code']) ? $input['customer_code'] : '');

            $selectData =  Distribution::with('distributionCustomer');

            if (!$allData) {
                if ($distributionName) :
                    $selectData->where('name', 'like', "%" . $distributionName . "%");
                endif;
                if ($startdate) :
                    $selectData->whereDate('start_date', '<=', date('Y-m-d', strtotime($startdate)));
                endif;
                if ($enddate) :
                    $selectData->whereDate('end_date', '>=', date('Y-m-d', strtotime($enddate)));
                endif;
                if ($customer) :
                    $selectData->whereHas('distributionCustomer', function ($query) use ($customer) {
                        $query->where('customer_id', $customer);
                    });
                endif;
                if ($customer_code) :
                    $selectData->whereHas('distributionCustomer', function ($query) use ($customer_code) {
                        $query->whereHas('customer', function ($query1) use ($customer_code) {
                            $query1->whereHas('customerInfo', function ($query2) use ($customer_code) {
                                $query2->where("customer_code", $customer_code);
                            });
                        });
                    });
                endif;
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                if (count($data)) {
                    $data1 = customPaginate($page, $limit, $data);
                    $data = $data1['data'];
                    $pagination = $data1['pagination'];
                } else {
                    $data = array();
                }
            } else {
                $data = $selectData->orderBy('id', 'desc')->get()->toArray();
                $data1 = customPaginate($page, $limit, $data);
                $data = $data1['data'];
                $pagination = $data1['pagination'];
            }
        }

        return prepareResult(true, $data, [], "Advanced Search Listing", $this->success, $pagination);
    }
}
