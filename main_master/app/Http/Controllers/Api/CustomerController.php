<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Channel;
use App\Model\CodeSetting;
use App\Model\Collection;
use App\Model\Country;
use App\Model\CreditNote;
use App\Model\CustomerComment;
use Illuminate\Http\Request;
use App\User;
use App\Model\ImportTempFile;
use App\Model\CustomerInfo;
use App\Model\CustomerType;
use App\Model\CustomFieldValueSave;
use App\Model\Delivery;
use App\Model\Estimation;
use App\Model\Expense;
use App\Model\Invoice;
use App\Model\PaymentTerm;
use App\Model\Region;
use App\Model\CustomerGroup;
use App\Model\SalesOrganisation;
use App\Model\CustomerCategory;
use App\Model\Route;
use App\Model\CountryMaster;
use App\Model\WorkFlowObject;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Imports\UsersImport;
use App\Model\CustomerDocument;
use Carbon\Carbon;
use Meneses\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Illuminate\Support\Facades\Validator;
use File;
use URL;
use App\Model\CustomerLob;
use App\Model\DebitNote;
use App\Model\SalesmanInfo;
use App\Model\Order;
use App\Model\SupervisorCustomerApproval;
use App\Model\CustomerVisit;
use App\Model\InvoiceDetail;
use App\Model\PDPItem;
use App\Model\Transaction;
use App\Model\CollectionDetails;
use App\Model\SalesmanUnload;
use App\Model\PriceDiscoPromoPlan;
use App\Model\WorkFlowRuleApprovalUser;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $users_query = CustomerInfo::select('id', 'uuid', 'organisation_id', 'user_id', 'region_id', 'route_id', 'customer_group_id', 'sales_organisation_id', 'channel_id', 'customer_code', 'erp_code', 'customer_type_id', 'payment_term_id', 'customer_category_id', 'customer_address_1', 'customer_address_2', 'customer_city', 'customer_state', 'customer_zipcode', 'customer_phone', 'customer_address_1_lat', 'customer_address_1_lang', 'customer_address_2_lat', 'customer_address_2_lang', 'balance', 'credit_limit', 'credit_days', 'ship_to_party', 'sold_to_party', 'payer', 'bill_to_payer', 'current_stage', 'current_stage_comment', 'status', 'is_lob', 'expired_date', 'due_on')
            ->with(
                'user:id,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status,parent_id',
                'user_country',
                'customerMerchandiser',
                'customerMerchandiser.merchandizer',
                'route:id,route_code,route_name,status',
                'channel:id,name,status',
                'region:id,region_name,region_status',
                'customerGroup:id,group_code,group_name',
                'customerCategory:id,customer_category_code,customer_category_name',
                'customerType:id,customer_type_name',
                'salesOrganisation:id,name',
                'paymentTerm:id,name',
                'shipToParty:id,user_id,customer_code',
                'shipToParty.user:id,firstname,lastname',
                'soldToParty:id,user_id,customer_code',
                'soldToParty.user:id,firstname,lastname',
                'payer:id,user_id,customer_code',
                'payer.user:id,firstname,lastname',
                'billToPayer:id,user_id,customer_code',
                //'paymentTerm:id,name,number_of_days',
                'billToPayer.user:id,firstname,lastname',
                'customFieldValueSave',
                'customFieldValueSave.customField',
                'customerlob',
                'customerlob.country:id,name',
                'customerlob.route:id,route_code,route_name,status',
                'customerlob.channel:id,name,status',
                'customerlob.region:id,region_code,region_name,region_status',
                'customerlob.customerType:id,customer_type_name',
                'customerlob.customerCategory:id,customer_category_code,customer_category_name',
                'customerlob.customerGroup:id,group_code,group_name',
                'customerlob.salesOrganisation:id,name',
                'customerlob.lob:id,name',
                'customerlob.paymentTerm:id,name',
                'customerlob.shipToParty:id,customer_code,user_id',
                'customerlob.shipToParty.user',
                'customerlob.soldToParty:id,customer_code,user_id',
                'customerlob.soldToParty.user',
                'customerlob.payer:id,customer_code,user_id',
                'customerlob.payer.user',
                'customerlob.billToPayer:id,customer_code,user_id',
                'customerlob.billToPayer.user',
                'customerDocument'
            )
            ->orderBy('id', 'desc');

        if ($request->customer_code) {
            $users_query->where('customer_code', 'like', '%' . $request->customer_code . '%');
        }

        if ($request->customer_phone) {
            $users_query->where('customer_phone', 'like', '%' . $request->customer_phone . '%');
        }

        if ($request->email) {
            $email = $request->email;
            $users_query->whereHas('user', function ($q) use ($email) {
                $q->where('email', $email);
            });
        }

        if ($request->name) {
            $name = $request->name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $users_query->whereHas('user', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $users_query->whereHas('user', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        $users = $users_query->get();

        // approval
        $results = GetWorkFlowRuleObject('Customer');
        $approve_need_customer = array();
        $approve_need_customer_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_customer[] = $raw['object']->raw_id;
                $approve_need_customer_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $users_array = array();
        if (is_object($users)) {
            foreach ($users as $key => $user1) {
                if (in_array($users[$key]->id, $approve_need_customer)) {
                    $users[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_customer_object_id[$users[$key]->id])) {
                        $users[$key]->objectid = $approve_need_customer_object_id[$users[$key]->id];
                    } else {
                        $users[$key]->objectid = '';
                    }
                } else {
                    $users[$key]->need_to_approve = 'no';
                    $users[$key]->objectid = '';
                }

                if ($users[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || $users[$key]->user->parent_id == auth()->id() || in_array($users[$key]->id, $approve_need_customer)) {
                    $users_array[] = $users[$key];
                }
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($users_array[$offset])) {
                    $data_array[] = $users_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($users_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($users_array);
        } else {
            $data_array = $users_array;
        }
        return prepareResult(true, $data_array, [], "Customer listing", $this->success, $pagination);
    }

    public function store(Request $request)
    {

        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        /* $input = $request->json()->all();
        $validate = $this->validations($input, "add");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer", $this->unprocessableEntity);
        } */

        $input = $request->json()->all();
        $rules_1 = [
            'firstname' => 'required',
            // 'lastname' => 'required',
            'email' => 'required|email|unique:users,email',
            'status' => 'required',
            'customer_address_1' => 'required',
            'is_lob' => 'required',
        ];
        $validator = Validator::make($input, $rules_1);


        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate add customer", $this->unauthorized);
        }

        if ($request->is_lob == 0) {
            $rules_2 = [
                // 'amount'  => 'required',
                'balance'  => 'required',
                // 'credit_days'  => 'required',
                'region_id' => 'required|integer|exists:regions,id',
                'channel_id' => 'required|integer|exists:channels,id',
                'sales_organisation_id' => 'required|integer|exists:sales_organisations,id',
                'route_id'  => 'required|integer|exists:routes,id',
                'customer_category_id'  => 'required|integer|exists:customer_categories,id',
                'customer_type_id' => 'required|integer|exists:customer_types,id',
                'ship_to_party' => 'required',
                'sold_to_party' => 'required',
                'payer' => 'required',
                'bill_to_payer' => 'required',
            ];


            if ($request->customer_type_id == 1) {
                $credit_validation = [
                    'credit_limit'  => 'required',
                ];
                $result_3 = array_merge($rules_2, $credit_validation);
                $rules =  $result_3;
            } else {
                $rules =  $rules_2;
            }
        }

        if ($request->is_lob == 1) {
            $rules_3 = [
                'customer_lob.*.region_id' => 'required|integer|exists:regions,id',
                'customer_lob.*.channel_id' => 'required|integer|exists:channels,id',
                'customer_lob.*.sales_organisation_id' => 'required|integer|exists:sales_organisations,id',
                'customer_lob.*.lob_id' => 'required',
                // 'customer_lob.*.amount' => 'required',
                'customer_lob.*.route_id' => 'required|integer|exists:routes,id',
                'customer_lob.*.customer_category_id' => 'required|integer|exists:customer_categories,id',
                'customer_lob.*.customer_type_id'  => 'required|integer|exists:customer_types,id',
                'customer_lob.*.balance' => 'required',
                // 'customer_lob.*.credit_limit' => 'required',
                // 'customer_lob.*.credit_days' => 'required',
                'customer_lob.*.ship_to_party' => 'required',
                'customer_lob.*.sold_to_party' => 'required',
                'customer_lob.*.payer' => 'required',
                'customer_lob.*.bill_to_payer' => 'required'
            ];

            $credit_validation = [];
            if ($request->customer_type_id == 1) {
                $credit_validation = [
                    'customer_lob.*.credit_limit' => 'required',
                    'customer_lob.*.credit_days' => 'required',
                ];
            }

            $rules = array_merge($rules_3, $credit_validation);
        }

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate add customer", $this->unauthorized);
        }

        if ($request->is_lob == 1) {
            if (is_array($request->customer_lob) && sizeof($request->customer_lob) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one lob details.", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Customer', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Customer',$request);
            }

            if ($request->email) {
                $user_result = User::where('email', $request->email)->first();
                if (is_object($user_result)) {
                    return prepareResult(false, [], 'Email is already used please use another one', "Oops!!!, please try again.", $this->internal_server_error);
                }
            }

            $user = new User;
            $user->usertype = 2;
            $user->parent_id = $request->parent_id;
            $user->firstname = $request->firstname;
            $user->lastname = (!empty($request->lastname)) ? $request->lastname : " ";
            $user->email = $request->email;
            $user->password = \Hash::make('123456');
            $user->mobile = $request->mobile;
            $user->country_id = $request->country_id;
            $user->api_token = \Str::random(35);
            $user->status = $status;
            $user->save();

            $customer_infos = new CustomerInfo;
            $customer_infos->user_id = $user->id;
            $customer_infos->customer_code = nextComingNumber('App\Model\CustomerInfo', 'customer', 'customer_code', $request->customer_code);
            $customer_infos->erp_code = $request->erp_code;
            $customer_infos->customer_address_1 = $request->customer_address_1;
            $customer_infos->customer_address_2 = $request->customer_address_2;
            $customer_infos->customer_city = $request->customer_city;
            $customer_infos->customer_state = $request->customer_state;
            $customer_infos->customer_zipcode = $request->customer_zipcode;
            $customer_infos->customer_phone = $request->customer_phone;
            $customer_infos->customer_address_1_lat = $request->customer_address_1_lat;
            $customer_infos->customer_address_1_lang = $request->customer_address_1_lang;
            $customer_infos->customer_address_2_lat = $request->customer_address_2_lat;
            $customer_infos->customer_address_2_lang = $request->customer_address_2_lang;
            if ($request->customer_profile) {
                $customer_infos->profile_image = saveImage($request->firstname . ' ' . $request->lastname, $request->customer_profile, 'customer-profile');
            }
            $customer_infos->payment_term_id = $request->payment_term_id;
            $customer_infos->current_stage = $current_stage;
            $customer_infos->current_stage_comment = $request->current_stage_comment;
            $customer_infos->status = $status;
            $customer_infos->is_lob = $request->is_lob;
            $customer_infos->customer_group_id      = (!empty($request->customer_group_id)) ? $request->customer_group_id : null;
            $customer_infos->lop      = (!empty($request->lop)) ? $request->lop : null;

            $customer_infos->expired_date = $request->expired_date;
            $customer_infos->source = (!empty($request->source)) ? $request->source : 3;

            if ($request->is_lob == 0) {
                $customer_infos->amount        = $request->amount;

                $customer_infos->balance      = $request->balance;
                $customer_infos->credit_limit = $request->credit_limit;
                $customer_infos->credit_days  = $request->credit_days;
                $customer_infos->region_id = $request->region_id;
                // $customer_infos->customer_group_id = (!empty($request->customer_group_id)) ? $request->customer_group_id : null;
                $customer_infos->sales_organisation_id = $request->sales_organisation_id;
                $customer_infos->route_id = $request->route_id;
                $customer_infos->channel_id = $request->channel_id;
                $customer_infos->customer_category_id = $request->customer_category_id;
                $customer_infos->customer_type_id = $request->customer_type_id;
                $customer_infos->due_on = (!empty($request->due_on)) ? $request->due_on : 1;


                if (!empty($request->customer_group_id)) {
                    $submit = DB::select('call sp_add_routegroup(?,?)', array($request->route_id, $request->customer_group_id));
                }
            }
            $customer_infos->save();

            if (isset($request->source) && $request->source == 1) {
                // add here Send Notification to supervisor New Customer register by salesman

                $salesmanInfo = SalesmanInfo::where('user_id', $request->salesman_id)->first();
                $csa = new SupervisorCustomerApproval;
                $csa->salesman_id = $request->salesman_id;
                $csa->customer_id = $user->id;
                $csa->supervisor_id = model($salesmanInfo, 'salesman_supervisor');
                $csa->status = "Pending";
                $csa->save();
            }

            if (is_array($request->documents) && sizeof($request->documents) >= 1) {
                collect($request->documents)->each(function ($document, $key) use ($customer_infos) {
                    CustomerDocument::create([
                        'customer_id' => $customer_infos->user_id,
                        'doc_string' => saveImage($customer_infos->customer_code . '_' . time(), $document, 'customer_document')
                    ]);
                });
            }

            if ($request->is_lob == 1) {
                if (is_array($request->customer_lob)) {
                    foreach ($request->customer_lob as $customer_lob_value) {
                        $customer_lob = new CustomerLob;
                        $customer_lob->customer_info_id             = $customer_infos->id;
                        $customer_lob->region_id                    = (!empty($customer_lob_value['region_id'])) ? $customer_lob_value['region_id'] : null;
                        $customer_lob->route_id                     = (!empty($customer_lob_value['route_id'])) ? $customer_lob_value['route_id'] : null;
                        $customer_lob->country_id                   = (!empty($customer_lob_value['country_id'])) ? $customer_lob_value['country_id'] : null;
                        $customer_lob->payment_term_id              = (!empty($customer_lob_value['payment_term_id'])) ? $customer_lob_value['payment_term_id'] : null;
                        $customer_lob->lob_id                       = (!empty($customer_lob_value['lob_id'])) ? $customer_lob_value['lob_id'] : null;
                        $customer_lob->amount                       = (!empty($customer_lob_value['amount'])) ? $customer_lob_value['amount'] : "0.00";
                        $customer_lob->customer_group_id            = (!empty($customer_lob_value['customer_group_id'])) ? $customer_lob_value['customer_group_id'] : null;
                        $customer_lob->sales_organisation_id        = (!empty($customer_lob_value['sales_organisation_id'])) ? $customer_lob_value['sales_organisation_id'] : null;
                        $customer_lob->channel_id                   = (!empty($customer_lob_value['channel_id'])) ? $customer_lob_value['channel_id'] : null;
                        $customer_lob->customer_category_id         = (!empty($customer_lob_value['customer_category_id'])) ? $customer_lob_value['customer_category_id'] : null;
                        $customer_lob->customer_type_id             = (!empty($customer_lob_value['customer_type_id'])) ? $customer_lob_value['customer_type_id'] : null;
                        $customer_lob->balance                      = (!empty($customer_lob_value['balance'])) ? $customer_lob_value['balance'] : null;
                        $customer_lob->credit_limit                 = (!empty($customer_lob_value['credit_limit'])) ? $customer_lob_value['credit_limit'] : null;
                        $customer_lob->credit_days                  = (!empty($customer_lob_value['credit_days'])) ? $customer_lob_value['credit_days'] : null;
                        $customer_lob->due_on                       = (!empty($customer_lob_value['due_on'])) ? $customer_lob_value['due_on'] : 1;

                        if (!empty($request->customer_group_id)) {
                            $submit = DB::select('call sp_add_routegroup(?,?)', array($customer_lob_value['route_id'], $request->customer_group_id));
                        }

                        $getInfoSTP = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['ship_to_party'])->first();
                        if ($getInfoSTP) {
                            $customer_lob->ship_to_party = $getInfoSTP->id;
                        }

                        $getInfoSTParty = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['sold_to_party'])->first();
                        if ($getInfoSTParty) {
                            $customer_lob->sold_to_party = $getInfoSTParty->id;
                        }

                        $getInfoP = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['payer'])->first();
                        if ($getInfoP) {
                            $customer_lob->payer = $getInfoP->id;
                        }

                        $getInfoBTP = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['bill_to_payer'])->first();

                        if ($getInfoBTP) {
                            $customer_lob->bill_to_payer = $getInfoBTP->id;
                        }

                        if (!$getInfoSTP || !$getInfoSTParty || !$getInfoP || !$getInfoBTP) {
                            \DB::rollback();
                            return prepareResult(false, [], ['ship_to_party' => $getInfoSTP, 'sold_to_party' => $getInfoSTParty, 'payer' => $getInfoP, 'bill_to_payer' => $getInfoBTP], "Please enter proper value of ship to party, sold to party, payer & bill to payer information.", $this->internal_server_error);
                        }
                        $customer_lob->save();
                    }
                }
            }

            if ($isActivate = checkWorkFlowRule('Customer', 'create', $current_organisation_id)) {
                $this->createWorkFlowObject($isActivate, 'Customer', $request, $customer_infos);
            }

            //action history
            create_action_history("Customer", $customer_infos->id, auth()->user()->id, "create", "Customer created by " . auth()->user()->firstname . " " . auth()->user()->lastname);

            if ($request->is_lob == 0) {
                $updateInfo = CustomerInfo::find($customer_infos->id);
                $getInfoSTP = CustomerInfo::select('id')->where('customer_code', $request->ship_to_party)->first();
                if ($getInfoSTP) {
                    $updateInfo->ship_to_party = $getInfoSTP->id;
                }

                $getInfoSTParty = CustomerInfo::select('id')->where('customer_code', $request->sold_to_party)->first();
                if ($getInfoSTParty) {
                    $updateInfo->sold_to_party = $getInfoSTParty->id;
                }

                $getInfoP = CustomerInfo::select('id')->where('customer_code', $request->payer)->first();
                if ($getInfoP) {
                    $updateInfo->payer = $getInfoP->id;
                }

                $getInfoBTP = CustomerInfo::select('id')->where('customer_code', $request->bill_to_payer)->first();

                if ($getInfoBTP) {
                    $updateInfo->bill_to_payer = $getInfoBTP->id;
                }
                $updateInfo->save();

                if (!$getInfoSTP || !$getInfoSTParty || !$getInfoP || !$getInfoBTP) {
                    \DB::rollback();
                    return prepareResult(false, [], ['ship_to_party' => $getInfoSTP, 'sold_to_party' => $getInfoSTParty, 'payer' => $getInfoP, 'bill_to_payer' => $getInfoBTP], "Please enter proper value of ship to party, sold to party, payer & bill to payer information.", $this->internal_server_error);
                }
            }


            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($customer_infos->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\CustomerInfo', 'customer');

            $customer_infos->getSaveData();
            return prepareResult(true, $customer_infos, [], "Customer added successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $users = CustomerInfo::where('uuid', $uuid)
            ->with(
                'user:id,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status',
                'user_country',
                'merchandiser:id,firstname,lastname',
                'route:id,route_code,route_name,status',
                'channel:id,name,status',
                'region:id,region_code,region_name,region_status',
                'customerType:id,customer_type_name',
                'customerCategory:id,customer_category_code,customer_category_name',
                'customerGroup:id,group_code,group_name',
                'salesOrganisation:id,name',
                'shipToParty:id,user_id,customer_code',
                'shipToParty.user:id,firstname,lastname',
                'soldToParty:id,user_id,customer_code',
                'soldToParty.user:id,firstname,lastname',
                'payer:id,user_id,customer_code',
                'paymentTerm:id,name,number_of_days',
                'payer.user:id,firstname,lastname',
                'billToPayer:id,user_id,customer_code',
                'billToPayer.user:id,firstname,lastname',
                'customFieldValueSave',
                'customFieldValueSave.customField',
                'customerlob',
                'customerlob.route:id,route_code,route_name,status',
                'customerlob.channel:id,name,status',
                'customerlob.region:id,region_code,region_name,region_status',
                'customerlob.customerType:id,customer_type_name',
                'customerlob.customerCategory:id,customer_category_code,customer_category_name',
                'customerlob.customerGroup:id,group_code,group_name',
                'customerlob.salesOrganisation:id,name',
                'customerlob.lob:id,name',

                'customerlob.shipToParty:id,customer_code,user_id',
                'customerlob.shipToParty.user',

                'customerlob.soldToParty:id,customer_code,user_id',
                'customerlob.soldToParty.user',

                'customerlob.payer:id,customer_code,user_id',
                'customerlob.payer.user',

                'customerlob.billToPayer:id,customer_code,user_id',
                'customerlob.billToPayer.user',
                'customerDocument'
            )->first();

        if (!is_object($users)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $users, [], "Customer Edit", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $rules_1 = [
            'firstname' => 'required',
            // 'lastname' => 'required',
            'status' => 'required',
            'customer_address_1' => 'required',
            'is_lob' => 'required',
        ];
        $validator = Validator::make($input, $rules_1);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate update customer", $this->unauthorized);
        }

        if ($request->is_lob == 0) {

            $rules_2 = [
                // 'amount'  => 'required',
                'balance'  => 'required',
                // 'credit_days'  => 'required',
                'region_id' => 'required|integer|exists:regions,id',
                'channel_id' => 'required|integer|exists:channels,id',
                'sales_organisation_id' => 'required|integer|exists:sales_organisations,id',
                // 'customer_group_id'  => 'required|integer|exists:customer_groups,id',
                'route_id'  => 'required|integer|exists:routes,id',
                'customer_category_id'  => 'required|integer|exists:customer_categories,id',
                'customer_type_id' => 'required|integer|exists:customer_types,id',
                'ship_to_party' => 'required',
                'sold_to_party' => 'required',
                'payer' => 'required',
                'bill_to_payer' => 'required',
            ];
            //$rules =  $rules_2;
            if ($request->customer_type_id == 1) {
                $credit_validation = [
                    'credit_limit'  => 'required',
                ];
                $result_3 = array_merge($rules_2, $credit_validation);
                $rules =  $result_3;
            } else {
                $rules =  $rules_2;
            }
        }

        if ($request->is_lob == 1) {
            $rules_2 = [
                'customer_lob.*.region_id' => 'required|integer|exists:regions,id',
                'customer_lob.*.channel_id' => 'required|integer|exists:channels,id',
                'customer_lob.*.sales_organisation_id' => 'required|integer|exists:sales_organisations,id',
                'customer_lob.*.lob_id' => 'required',
                // 'customer_lob.*.amount' => 'required',
                'customer_lob.*.route_id' => 'required|integer|exists:routes,id',
                //'customer_lob.*.customer_group_id'  => 'required|integer|exists:customer_groups,id',
                'customer_lob.*.customer_category_id' => 'required|integer|exists:customer_categories,id',
                'customer_lob.*.customer_type_id'  => 'required|integer|exists:customer_types,id',
                'customer_lob.*.balance' => 'required',
                // 'customer_lob.*.credit_limit' => 'required',
                // 'customer_lob.*.credit_days' => 'required',
                'customer_lob.*.ship_to_party' => 'required',
                'customer_lob.*.sold_to_party' => 'required',
                'customer_lob.*.payer' => 'required',
                'customer_lob.*.bill_to_payer' => 'required'
            ];

            $credit_validation = [];
            if ($request->customer_type_id == 1) {
                $credit_validation = [
                    'customer_lob.*.credit_limit' => 'required',
                    'customer_lob.*.credit_days' => 'required',
                ];
            }

            $rules = array_merge($rules_2, $credit_validation);
        }

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate update customer", $this->unauthorized);
        }

        if ($request->is_lob == 1) {
            if (is_array($request->customer_lob) && sizeof($request->customer_lob) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one lob details.", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            // $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Customer', 'edit', $current_organisation_id)) {
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Customer',$request);
            }
            $status = $request->status;

            $customer_infos = CustomerInfo::where('uuid', $uuid)->first();

            //all record deleted in  Customer Lob matches in customer_info_id
            CustomerLob::where('customer_info_id', $customer_infos->id)->delete();

            $customer_infos->erp_code = $request->erp_code;
            $customer_infos->customer_address_1 = $request->customer_address_1;
            $customer_infos->customer_address_2 = $request->customer_address_2;
            $customer_infos->customer_city = $request->customer_city;
            $customer_infos->customer_state = $request->customer_state;
            $customer_infos->customer_zipcode = $request->customer_zipcode;
            $customer_infos->customer_phone = $request->customer_phone;
            $customer_infos->customer_address_1_lat = $request->customer_address_1_lat;
            $customer_infos->customer_address_1_lang = $request->customer_address_1_lang;
            $customer_infos->customer_address_2_lat = $request->customer_address_2_lat;
            $customer_infos->customer_address_2_lang = $request->customer_address_2_lang;
            if ($request->customer_profile) {
                $customer_infos->profile_image = saveImage($request->firstname . ' ' . $request->lastname, $request->customer_profile, 'customer-profile');
            }
            $customer_infos->customer_group_id      = (!empty($request->customer_group_id)) ? $request->customer_group_id : null;
            $customer_infos->lop      = (!empty($request->lop)) ? $request->lop : null;
            $customer_infos->payment_term_id = $request->payment_term_id;
            $customer_infos->current_stage = $current_stage;
            $customer_infos->current_stage_comment = $request->current_stage_comment;
            $customer_infos->status = $status;
            $customer_infos->is_lob = $request->is_lob;
            $customer_infos->expired_date = $request->expired_date;
            $customer_infos->source = (!empty($request->source)) ? $request->source : 3;

            if ($request->is_lob == 0) {
                $customer_infos->amount          = $request->amount;
                $customer_infos->balance                = $request->balance;
                $customer_infos->credit_limit           = $request->credit_limit;
                $customer_infos->credit_days            = $request->credit_days;
                $customer_infos->region_id              = $request->region_id;
                $customer_infos->sales_organisation_id  = $request->sales_organisation_id;
                $customer_infos->route_id               = $request->route_id;
                $customer_infos->channel_id             = $request->channel_id;
                $customer_infos->customer_category_id   = $request->customer_category_id;
                $customer_infos->customer_type_id       = $request->customer_type_id;
                $customer_infos->due_on                 = (!empty($request->due_on)) ? $request->due_on : 1;

                if (!empty($request->customer_group_id)) {
                    $submit = DB::select('call sp_add_routegroup(?,?)', array($request->route_id, $request->customer_group_id));
                }
            }

            if ($request->is_lob == 1) {
                $customer_infos->amount                 = null;
                $customer_infos->balance                = null;
                $customer_infos->credit_limit           = null;
                $customer_infos->credit_days            = null;
                $customer_infos->region_id              = null;
                // $customer_infos->customer_group_id      = null;
                $customer_infos->sales_organisation_id  = null;
                $customer_infos->route_id               = null;
                $customer_infos->channel_id             = null;
                $customer_infos->customer_category_id   = null;
                $customer_infos->customer_type_id       = null;
                $customer_infos->ship_to_party          = null;
                $customer_infos->sold_to_party          = null;
                $customer_infos->payer                  = null;
                $customer_infos->bill_to_payer          = null;
                $customer_infos->due_on                 = null;
            }

            $customer_infos->save();

            if ($request->is_lob == 1) {
                if (is_array($request->customer_lob)) {
                    foreach ($request->customer_lob as $customer_lob_value) {
                        $customer_lob = new CustomerLob;
                        $customer_lob->customer_info_id             = $customer_infos->id;
                        $customer_lob->region_id                    = (!empty($customer_lob_value['region_id'])) ? $customer_lob_value['region_id'] : null;
                        $customer_lob->route_id                     = (!empty($customer_lob_value['route_id'])) ? $customer_lob_value['route_id'] : null;
                        $customer_lob->country_id                   = (!empty($customer_lob_value['country_id'])) ? $customer_lob_value['country_id'] : null;
                        $customer_lob->payment_term_id              = (!empty($customer_lob_value['payment_term_id'])) ? $customer_lob_value['payment_term_id'] : null;
                        $customer_lob->lob_id                       = (!empty($customer_lob_value['lob_id'])) ? $customer_lob_value['lob_id'] : null;
                        $customer_lob->amount                       = (!empty($customer_lob_value['amount'])) ? $customer_lob_value['amount'] : "0.00";
                        $customer_lob->customer_group_id            = (!empty($customer_lob_value['customer_group_id'])) ? $customer_lob_value['customer_group_id'] : null;
                        $customer_lob->sales_organisation_id        = (!empty($customer_lob_value['sales_organisation_id'])) ? $customer_lob_value['sales_organisation_id'] : null;
                        $customer_lob->channel_id                   = (!empty($customer_lob_value['channel_id'])) ? $customer_lob_value['channel_id'] : null;
                        $customer_lob->customer_category_id         = (!empty($customer_lob_value['customer_category_id'])) ? $customer_lob_value['customer_category_id'] : null;
                        $customer_lob->customer_type_id             = (!empty($customer_lob_value['customer_type_id'])) ? $customer_lob_value['customer_type_id'] : null;
                        $customer_lob->balance                      = (!empty($customer_lob_value['balance'])) ? $customer_lob_value['balance'] : null;
                        $customer_lob->credit_limit                 = (!empty($customer_lob_value['credit_limit'])) ? $customer_lob_value['credit_limit'] : null;
                        $customer_lob->credit_days                  = (!empty($customer_lob_value['credit_days'])) ? $customer_lob_value['credit_days'] : null;
                        $customer_lob->due_on                       = (!empty($customer_lob_value['due_on'])) ? $customer_lob_value['due_on'] : 1;

                        if (!empty($request->customer_group_id)) {
                            $submit = DB::select('call sp_add_routegroup(?,?)', array($customer_lob_value['route_id'], $request->customer_group_id));
                        }

                        $getInfoSTP = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['ship_to_party'])->first();
                        if ($getInfoSTP) {
                            $customer_lob->ship_to_party = $getInfoSTP->id;
                        }

                        $getInfoSTParty = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['sold_to_party'])->first();
                        if ($getInfoSTParty) {
                            $customer_lob->sold_to_party = $getInfoSTParty->id;
                        }

                        $getInfoP = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['payer'])->first();
                        if ($getInfoP) {
                            $customer_lob->payer = $getInfoP->id;
                        }

                        $getInfoBTP = CustomerInfo::select('id')->where('customer_code', $customer_lob_value['bill_to_payer'])->first();

                        if ($getInfoBTP) {
                            $customer_lob->bill_to_payer = $getInfoBTP->id;
                        }

                        if (!$getInfoSTP || !$getInfoSTParty || !$getInfoP || !$getInfoBTP) {
                            \DB::rollback();
                            return prepareResult(false, [], ['ship_to_party' => $getInfoSTP, 'sold_to_party' => $getInfoSTParty, 'payer' => $getInfoP, 'bill_to_payer' => $getInfoBTP], "Please enter proper value of ship to party, sold to party, payer & bill to payer information.", $this->internal_server_error);
                        }

                        $customer_lob->save();
                    }
                }
            }

            $user = $customer_infos->user;
            $user->parent_id = $request->parent_id;
            $user->firstname = $request->firstname;
            $user->lastname = (!empty($request->lastname)) ? $request->lastname : " ";
            $user->mobile = $request->mobile;
            $user->country_id = $request->country_id;
            $user->status = $status;
            $user->save();

            if (isset($request->source) && $request->source == 1) {
                $salesmanInfo = SalesmanInfo::where('user_id', $request->salesman_id)->first();
                $csa = new SupervisorCustomerApproval;
                $csa->salesman_id = $request->salesman_id;
                $csa->customer_id = $user->id;
                $csa->supervisor_id = model($salesmanInfo, 'salesman_supervisor');
                $csa->status = "Pending";
                $csa->save();
            }

            if (is_array($request->documents) && sizeof($request->documents) >= 1) {
                CustomerDocument::where('customer_id', $user->id)->delete();
                collect($request->documents)->each(function ($document, $key) use ($customer_infos) {
                    CustomerDocument::create([
                        'customer_id' => $customer_infos->user_id,
                        'doc_string' => saveImage($customer_infos->customer_code . '_' . time(), $document, 'customer_document')
                    ]);
                });
            }

            if ($isActivate = checkWorkFlowRule('Customer', 'edit')) {
                $this->createWorkFlowObject($isActivate, 'Customer', $request, $customer_infos);
            }

            //action history
            create_action_history("Customer", $customer_infos->id, auth()->user()->id, "update", "Customer updated by " . auth()->user()->firstname . " " . auth()->user()->lastname);
            //action history

            if ($request->is_lob == 0) {
                $updateInfo = CustomerInfo::find($customer_infos->id);
                $getInfoSTP = CustomerInfo::select('id')->where('customer_code', $request->ship_to_party)->first();
                if ($getInfoSTP) {
                    $updateInfo->ship_to_party = $getInfoSTP->id;
                }

                $getInfoSTParty = CustomerInfo::select('id')->where('customer_code', $request->sold_to_party)->first();
                if ($getInfoSTParty) {
                    $updateInfo->sold_to_party = $getInfoSTParty->id;
                }

                $getInfoP = CustomerInfo::select('id')->where('customer_code', $request->payer)->first();
                if ($getInfoP) {
                    $updateInfo->payer = $getInfoP->id;
                }

                $getInfoBTP = CustomerInfo::select('id')->where('customer_code', $request->bill_to_payer)->first();

                if ($getInfoBTP) {
                    $updateInfo->bill_to_payer = $getInfoBTP->id;
                }
                $updateInfo->save();

                if (!$getInfoSTP || !$getInfoSTParty || !$getInfoP || !$getInfoBTP) {
                    \DB::rollback();
                    return prepareResult(false, [], ['ship_to_party' => $getInfoSTP, 'sold_to_party' => $getInfoSTParty, 'payer' => $getInfoP, 'bill_to_payer' => $getInfoBTP], "Please enter proper value of ship to party, sold to party, payer & bill to payer information.", $this->internal_server_error);
                }
            }


            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                CustomFieldValueSave::where('record_id', $customer_infos->id)->delete();
                foreach ($request->modules as $module) {
                    savecustomField($customer_infos->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();

            $customer_infos->getSaveData();

            return prepareResult(true, $customer_infos, [], "Customer updated successfully", $this->success);
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
     * @param  int $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating depots", $this->unauthorized);
        }

        $customer_infos = CustomerInfo::where('uuid', $uuid)->first();

        $user = $customer_infos->user;

        if (is_object($user)) {
            $user->delete();
            $customer_infos->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        /* if ($type == "add") {
            $validator = \Validator::make($input, [                
                'region_id' => 'required|integer|exists:regions,id',
                'channel_id' => 'required|integer|exists:channels,id',
                'sales_organisation_id' => 'required|integer|exists:sales_organisations,id',
                'ship_to_party' => 'required',
                'sold_to_party' => 'required',
                'payer' => 'required',
                'bill_to_payer' => 'required',
                'firstname' => 'required',
                'lastname' => 'required',
                'email' => 'required|email|unique:users,email',
                'status' => 'required',
                'customer_address_1' => 'required', 
                'is_lob' => 'required', 

                // 'sessions.*.title'      => 'required',

                // 'password' => 'required',
                // 'country_id' => 'required|integer|exists:countries,id',
                // 'customer_group_id' => 'required|integer|exists:customer_groups,id',
                // 'mobile' => 'required',
                // 'role_id' => 'required',
                // 'customer_type_id' => 'required',
                // 'customer_city' => 'required',
                // 'customer_state' => 'required',
                // 'customer_zipcode' => 'required'                
            ]);
        } */

        /* if ($type == "edit") {
            $validator = \Validator::make($input, [
                'region_id' => 'required|integer|exists:regions,id',
                'channel_id' => 'required|integer|exists:channels,id',
                'sales_organisation_id' => 'required|integer|exists:sales_organisations,id',
                'firstname' => 'required',
                'lastname' => 'required',
                'customer_code' => 'required',
                'status' => 'required',
                'customer_address_1' => 'required',
                'is_lob' => 'required', 
                // 'password' => 'required',
                // 'customer_type_id' => 'required',
                // 'country_id' => 'required|integer|exists:countries,id',
                // 'customer_group_id' => 'required|integer|exists:customer_groups,id',
                // 'mobile' => 'required',
                // 'role_id' => 'required',
                // 'customer_city' => 'required',
                // 'customer_state' => 'required',
                // 'customer_zipcode' => 'required'
                 
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        } */

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'customer_ids' => 'required'
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }


        return ["error" => $error, "errors" => $errors];
    }

    public function customerComment(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "comment");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating Customer CommentS", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $user = new CustomerComment;
            $user->customer_id = $request->customer_id;
            $user->comment = $request->comment;
            $user->comment_date = date("Y-m-d");
            $user->status = 1;
            $user->save();

            \DB::commit();
            return prepareResult(true, $user, [], "Comment added successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function customerDetails(Request $request, $customer_id)
    {
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $search = (isset($_REQUEST['search'])) ? $_REQUEST['search'] : '';

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$customer_id) {
            return prepareResult(false, [], [], "Error while validating customer id.", $this->unauthorized);
        }

        if ($search == '') {
            return prepareResult(false, [], [], "Error while validating searching module", $this->unauthorized);
        }

        //Customer Invoices
        if ($search == 'invoice') {
            $data_array = Invoice::select(
                'invoices.id',
                'invoices.uuid',
                'invoices.customer_id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.grand_total',
                'invoices.status',
                'collection_details.pending_amount',
                \DB::raw("CASE
                        WHEN collection_details.pending_amount=0 or collection_details.pending_amount=0.00 THEN 'Paid'
                        WHEN invoices.grand_total = collection_details.pending_amount THEN 'Approved'
                        WHEN invoices.grand_total > collection_details.pending_amount THEN 'Overdue'
                        ELSE 'Draft'
                    END As status")
            )
                ->leftJoin('collection_details', function ($join) {
                    $join->on('collection_details.invoice_id', '=', 'invoices.id');
                    $join->on(DB::raw('collection_details.id'), DB::raw('(SELECT MAX(id) from collection_details where invoice_id=invoices.id)'), DB::raw(''));
                });

            if ($request->lob_id) {
                $data_array->where('customer_id', $customer_id)
                    ->where('lob_id', $request->lob_id);
            } else {
                $data_array->where('customer_id', $customer_id);
            }

            if ($page != '' && $limit != '') {
                $data_array = $data_array->orderBy('id', 'desc')->paginate($limit)->toArray();
                $dataArray['total_pages'] = ceil($data_array['total'] / $limit);
                $dataArray['current_page'] = (int)$data_array['current_page'];
                $dataArray['total_records'] = (int) $data_array['total'];
                $dataArray['data'] = $data_array['data'];
                return prepareResult(true, $dataArray, [], "Customer Invoice listing", $this->success);
            } else {
                $data_array = $data_array->orderBy('id', 'desc')->get()->toArray();
                $dataArray = $data_array;
            }
        }

        //Customer Credit Notes
        if ($search == 'creditnote') {
            $data_array = CreditNote::select(
                'id',
                'uuid',
                'customer_id',
                'credit_note_number',
                'credit_note_date',
                'grand_total',
                \DB::raw("CASE WHEN (status = 1) THEN 'Active' ELSE 'InActive' END AS status")
            );
            if ($request->lob_id) {
                $data_array->where('customer_id', $customer_id)
                    ->where('lob_id', $request->lob_id);
            } else {
                $data_array->where('customer_id', $customer_id);
            }

            if ($page != '' && $limit != '') {
                $data_array = $data_array->orderBy('id', 'desc')->paginate($limit)->toArray();
                $dataArray['total_pages'] = ceil($data_array['total'] / $limit);
                $dataArray['current_page'] = (int)$data_array['current_page'];
                $dataArray['total_records'] = (int) $data_array['total'];
                $dataArray['data'] = $data_array['data'];
                return prepareResult(true, $dataArray, [], "Customer Credit Note listing", $this->success);
            } else {
                $data_array = $data_array->orderBy('id', 'desc')->get()->toArray();
                $dataArray = $data_array;
            }
        }

        //Customer Expenses
        if ($search == 'expense') {
            $data_array = Expense::select(
                'id',
                'uuid',
                'customer_id',
                'reference',
                'amount',
                'expense_date',
                \DB::raw("CASE WHEN (status = 1) THEN 'Active' ELSE 'InActive' END AS status")
            )
                ->with('expenseCategory:id,name')
                ->where('customer_id', $customer_id);

            if ($page != '' && $limit != '') {
                $data_array = $data_array->orderBy('id', 'desc')->paginate($limit)->toArray();
                $dataArray['total_pages'] = ceil($data_array['total'] / $limit);
                $dataArray['current_page'] = (int)$data_array['current_page'];
                $dataArray['total_records'] = (int) $data_array['total'];
                $dataArray['data'] = $data_array['data'];
                return prepareResult(true, $dataArray, [], "Customer Expense listing", $this->success);
            } else {
                $data_array = $data_array->orderBy('id', 'desc')->get()->toArray();
                $dataArray = $data_array;
            }
        }

        //Customer Delivery Details
        if ($search == 'delivery_detail') {
            $data_array = Delivery::select(
                'id',
                'uuid',
                'customer_id',
                'delivery_number',
                'delivery_date',
                'grand_total',
                \DB::raw("CASE WHEN (status = 1) THEN 'Active' ELSE 'InActive' END AS status")
            );
            // ->where('customer_id', $customer_id);

            if ($request->lob_id) {
                $data_array->where('customer_id', $customer_id)
                    ->where('lob_id', $request->lob_id);
            } else {
                $data_array->where('customer_id', $customer_id);
            }

            if ($page != '' && $limit != '') {
                $data_array = $data_array->orderBy('id', 'desc')->paginate($limit)->toArray();
                $dataArray['total_pages'] = ceil($data_array['total'] / $limit);
                $dataArray['current_page'] = (int)$data_array['current_page'];
                $dataArray['total_records'] = (int) $data_array['total'];
                $dataArray['data'] = $data_array['data'];
                return prepareResult(true, $dataArray, [], "Customer Delivery Detail listing", $this->success);
            } else {
                $data_array = $data_array->orderBy('id', 'desc')->get()->toArray();
                $dataArray = $data_array;
            }
        }

        //Customer Estimation
        if ($search == 'estimation') {
            $data_array = Estimation::select(
                'id',
                'uuid',
                'customer_id',
                'reference',
                'estimate_code',
                'estimate_date',
                'total',
                \DB::raw("CASE WHEN (status = 1) THEN 'Active' ELSE 'InActive' END AS status")
            )
                ->where('customer_id', $customer_id);

            if ($page != '' && $limit != '') {
                $data_array = $data_array->orderBy('id', 'desc')->paginate($limit)->toArray();
                $dataArray['total_pages'] = ceil($data_array['total'] / $limit);
                $dataArray['current_page'] = (int)$data_array['current_page'];
                $dataArray['total_records'] = (int) $data_array['total'];
                $dataArray['data'] = $data_array['data'];
                return prepareResult(true, $dataArray, [], "Customer Estimation listing", $this->success);
            } else {
                $data_array = $data_array->orderBy('id', 'desc')->get()->toArray();
                $dataArray = $data_array;
            }
        }

        //Customer Collection
        if ($search == 'collection') {
            $data_array = Collection::select(
                'id',
                'uuid',
                'customer_id',
                'collection_number',
                'invoice_amount',
                \DB::raw("CASE
                        WHEN payemnt_type=1 THEN 'Cash'
                        WHEN payemnt_type=2 THEN 'Cheque'
                        WHEN payemnt_type=3 THEN 'NEFT'
                        ELSE ''
                    END As payment_mode")
            );
            //->where('customer_id', $customer_id);
            if ($request->lob_id) {
                $data_array->where('customer_id', $customer_id)
                    ->where('lob_id', $request->lob_id);
            } else {
                $data_array->where('customer_id', $customer_id);
            }

            if ($page != '' && $limit != '') {
                $data_array = $data_array->orderBy('id', 'desc')->paginate($limit)->toArray();
                $dataArray['total_pages'] = ceil($data_array['total'] / $limit);
                $dataArray['current_page'] = (int)$data_array['current_page'];
                $dataArray['total_records'] = (int) $data_array['total'];
                $dataArray['data'] = $data_array['data'];
                return prepareResult(true, $dataArray, [], "Customer Collection listing", $this->success);
            } else {
                $data_array = $data_array->orderBy('id', 'desc')->get()->toArray();
                $dataArray = $data_array;
            }
        }

        //Prepare Results
        return prepareResult(true, $dataArray, [], "Customer Detail listing", $this->success);
    }

    public function deleteCustomerComment($comment_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$comment_id) {
            return prepareResult(false, [], [], "Error while validating customer comment", $this->unauthorized);
        }

        $customer_comment = CustomerComment::where('id', $comment_id)->first();


        if (is_object($customer_comment)) {
            $customer_comment->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }


        return prepareResult(false, [], [], "No Record Found", $this->unauthorized);
    }

    public function listCustomerComments($customer_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$customer_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $customer_comment = CustomerComment::where('customer_id', $customer_id)->orderBy('created_at', 'DESC')->get();


        $dataArray = $customer_comment;

        //Prepare Results
        return prepareResult(true, $dataArray, [], "Customer comments listing", $this->success);
    }

    public function customerTypes()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $customer_type = CustomerType::get();

        return prepareResult(true, $customer_type, [], "Customer type listing", $this->success);
    }

    public function createWorkFlowObject1($work_flow_rule_id, $module_name, $row, $obj)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id   = $work_flow_rule_id;
        $createObj->module_name         = $module_name;
        $createObj->raw_id                 = $obj->raw_id;
        $createObj->request_object      = $row;
        $createObj->save();

        $wfrau = WorkFlowRuleApprovalUser::where('work_flow_rule_id', $work_flow_rule_id)->first();

        $data = array(
            'uuid' => (is_object($obj)) ? $obj->uuid : 0,
            'user_id' => $wfrau->user_id,
            'type' => $module_name,
            'message' => "Approve the New " . $module_name,
            'status' => 1,
        );
        saveNotificaiton($data);
    }

    public function createWorkFlowObject($work_flow_rule_id, $module_name, Request $request, $obj)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id = $work_flow_rule_id;
        $createObj->module_name = $module_name;
        $createObj->raw_id = $obj->id;
        $createObj->request_object = $request->all();
        $createObj->save();

        $wfrau = WorkFlowRuleApprovalUser::where('work_flow_rule_id', $work_flow_rule_id)->first();

        $data = array(
            'uuid' => (is_object($obj)) ? $obj->uuid : 0,
            'user_id' => $wfrau->user_id,
            'type' => $module_name,
            'message' => "Approve the New " . $module_name,
            'status' => 1,
        );
        saveNotificaiton($data);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'customer_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate customer import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('customer_file')->store('import');
            $filename = storage_path("app/" . $file);
            $fp = fopen($filename, "r");
            $content = fread($fp, filesize($filename));
            $lines = explode("\n", $content);
            $heading_array_line = isset($lines[0]) ? $lines[0] : '';
            $heading_array = explode(",", trim($heading_array_line));
            fclose($fp);

            if (!$heading_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            if (!$map_key_value_array) {
                return prepareResult(false, [], [], "Import file and mapping field not match!", $this->success);
            }
            /*$file_data = fopen(storage_path("app/".$file), "r");
            $row_counter = 1;
            while(!feof($file_data)) {
               if($row_counter == 1){
                    echo fgets($file_data). "<br>";
               }
               $row_counter++;
            }
            fclose($file_data);
            */
            //exit;

            $import = new UsersImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

            //print_r($import);
            //exit;
            $succussrecords = 0;
            $successfileids = 0;
            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());
                $data = json_encode($import->successAllRecords());
                $fileName = time() . '_datafile.txt';
                File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();
                $successfileids = $importtempfiles->id;
            }
            $errorrecords = 0;
            $errror_array = array();
            if ($import->failures()) {

                foreach ($import->failures() as $failure_key => $failure) {
                    if ($failure->row() != 1) {
                        $failure->row(); // row that went wrong
                        $failure->attribute(); // either heading key (if using heading row concern) or column index
                        $failure->errors(); // Actual error messages from Laravel validator
                        $failure->values(); // The values of the row that has failed.
                        //print_r($failure->errors());

                        $error_msg = isset($failure->errors()[0]) ? $failure->errors()[0] : '';
                        if ($error_msg != "") {
                            //$errror_array['errormessage'][] = array("There was an error on row ".$failure->row().". ".$error_msg);
                            //$errror_array['errorresult'][] = $failure->values();
                            $error_result = array();
                            $error_row_loop = 0;
                            foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
                                $error_result[$map_key_value_array_value] = isset($failure->values()[$error_row_loop]) ? $failure->values()[$error_row_loop] : '';
                                $error_row_loop++;
                            }
                            $errror_array[] = array(
                                'errormessage' => "There was an error on row " . $failure->row() . ". " . $error_msg,
                                'errorresult' => $error_result, //$failure->values(),
                                //'attribute' => $failure->attribute(),//$failure->values(),
                                //'error_result' => $error_result,
                                //'map_key_value_array' => $map_key_value_array,
                            );
                        }
                    }
                }
                $errorrecords = count($errror_array);
            }
            $errors = $errror_array;
            $result['successrecordscount'] = $succussrecords;
            $result['errorrcount'] = $errorrecords;
            $result['successfileids'] = $successfileids;
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                if ($failure->row() != 1) {
                    info($failure->row());
                    info($failure->attribute());
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                    $errors[] = $failure->errors();
                }
            }

            return prepareResult(true, [], $errors, "Failed to validate bank import", $this->success);
        }
        return prepareResult(true, $result, $errors, "Customer successfully imported", $this->success);
    }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);
            if ($finaldata) :
                foreach ($finaldata as $row) :
                    $status = 0;
                    $current_stage = 'Approved';

                    $country = CountryMaster::where('name', 'LIKE', '%' . $row[5] . '%')->first();
                    $region = Region::where('region_name', $row[7])->first();
                    //                    $CustomerGroup = CustomerGroup::where('group_name', $row[8])->first();
                    $SalesOrganisation = SalesOrganisation::where('name', $row[8])->first();
                    $Route = Route::where('route_name', $row[9])->first();
                    $Channel = Channel::where('name', $row[10])->first();
                    $CustomerCategory = CustomerCategory::where('customer_category_name', $row[11])->first();
                    $CustomerType = CustomerType::where('customer_type_name', $row[13])->first();
                    $PaymentTerm = PaymentTerm::where('name', $row[23])->first();
                    $Merchandiser = SalesmanInfo::where('salesman_code', $row[24])->first();
                    $user = User::where('email', $row[2])->first();

                    $current_organisation_id = request()->user()->organisation_id;
                    if ($isActivate = checkWorkFlowRule('Customer', 'create', $current_organisation_id)) {
                        $status = 0;
                        $current_stage = 'Pending';
                        //$this->createWorkFlowObject($isActivate, 'Customer',$request);
                    }

                    if (is_object($user)) {
                        $user->usertype = 2;
                        $user->parent_id = auth()->user()->id;
                        $user->firstname = $row[0];
                        $user->lastname  = $row[1];
                        $user->email = $row[2];
                        $user->email_verified_at = date('Y-m-d H:i:s');
                        $user->password = Hash::make($row[3]);
                        $user->mobile = $row[4];
                        $user->country_id = (is_object($country)) ? $country->id : 0;
                        $user->api_token = \Str::random(35);
                        $user->status = $row[6];
                        $user->save();

                        $customer_infos = CustomerInfo::where('user_id', $user->id)->first();
                        $customer_infos->user_id = $user->id;
                        $customer_infos->region_id = (is_object($region)) ? $region->id : 0;
                        //$customer_infos->customer_group_id = (is_object($CustomerGroup)) ? $CustomerGroup->id : 0;
                        $customer_infos->sales_organisation_id = (is_object($SalesOrganisation)) ? $SalesOrganisation->id : 0;
                        $customer_infos->route_id = (is_object($Route)) ? $Route->id : 0;
                        $customer_infos->channel_id = (is_object($Channel)) ? $Channel->id : 0;
                        $customer_infos->customer_category_id = (is_object($CustomerCategory)) ? $CustomerCategory->id : 0;
                        $customer_infos->customer_code = $row[12];
                        $customer_infos->customer_type_id = (is_object($CustomerType)) ? $CustomerType->id : 0;
                        $customer_infos->customer_address_1 = $row[14];
                        $customer_infos->customer_address_2 = $row[15];
                        $customer_infos->customer_city = $row[16];
                        $customer_infos->customer_state = $row[17];
                        $customer_infos->customer_zipcode = $row[18];
                        $customer_infos->customer_phone = $row[19];
                        if (is_object($CustomerType) && $CustomerType->id != 2) {
                            $customer_infos->balance = $row[20];
                            $customer_infos->credit_limit = $row[21];
                            $customer_infos->credit_days = $row[22];
                            if (is_object($PaymentTerm)) {
                                $customer_infos->payment_term_id = $PaymentTerm->id;
                            }
                        }
                        $customer_infos->merchandiser_id = (is_object($Merchandiser)) ? $Merchandiser->id : 0;
                        // $customer_infos->ship_to_party = $row[25];
                        // $customer_infos->sold_to_party = $row[26];
                        // $customer_infos->payer = $row[27];
                        // $customer_infos->bill_to_payer = $row[28];
                        $customer_infos->customer_address_1_lat = $row[29];
                        $customer_infos->customer_address_1_lang = $row[30];
                        $customer_infos->erp_code = $row[12];

                        $customer_infos->current_stage = $current_stage;
                        $customer_infos->current_stage_comment = "";

                        $customer_infos->status = $status;

                        $customer_infos->save();
                        // pre($customer_infos);
                        $updateInfo = CustomerInfo::find($customer_infos->id);
                        $getInfoSTP = CustomerInfo::select('id')->where('customer_code', $row[25])->first();
                        if ($getInfoSTP) {
                            $updateInfo->ship_to_party = $getInfoSTP->id;
                        }

                        $getInfoSTParty = CustomerInfo::select('id')->where('customer_code', $row[26])->first();
                        if ($getInfoSTParty) {
                            $updateInfo->sold_to_party = $getInfoSTParty->id;
                        }

                        $getInfoP = CustomerInfo::select('id')->where('customer_code', $row[27])->first();
                        if ($getInfoP) {
                            $updateInfo->payer = $getInfoP->id;
                        }

                        $getInfoBTP = CustomerInfo::select('id')->where('customer_code', $row[28])->first();

                        if ($getInfoBTP) {
                            $updateInfo->bill_to_payer = $getInfoBTP->id;
                        }
                        $updateInfo->save();
                    } else {
                        $status = $row[6];
                        $current_stage = 'Approved';
                        $current_organisation_id = request()->user()->organisation_id;
                        if ($isActivate = checkWorkFlowRule('Customer', 'create', $current_organisation_id)) {
                            $status = 0;
                            $current_stage = 'Pending';
                            //$this->createWorkFlowObject($isActivate, 'Customer',$request);
                        }


                        $user = new User;
                        $user->usertype = 2;
                        $user->parent_id = auth()->user()->id;
                        $user->firstname = $row[0];
                        $user->lastname  = $row[1];
                        $user->email = $row[2];
                        $user->password = Hash::make($row[3]);
                        $user->email_verified_at = date('Y-m-d H:i:s');
                        $user->mobile = $row[4];
                        $user->country_id = (is_object($country)) ? $country->id : 0;
                        $user->api_token = \Str::random(35);
                        $user->status = $status;
                        $user->save();

                        $customer_infos = new CustomerInfo;
                        $customer_infos->user_id = $user->id;
                        $customer_infos->region_id = (is_object($region)) ? $region->id : 0;
                        //                        $customer_infos->customer_group_id = (is_object($CustomerGroup)) ? $CustomerGroup->id : 0;
                        $customer_infos->sales_organisation_id = (is_object($SalesOrganisation)) ? $SalesOrganisation->id : 0;
                        $customer_infos->route_id = (is_object($Route)) ? $Route->id : NULL;
                        $customer_infos->channel_id = (is_object($Channel)) ? $Channel->id : 0;
                        $customer_infos->customer_category_id = (is_object($CustomerCategory)) ? $CustomerCategory->id : 0;
                        $customer_infos->customer_code = $row[12];
                        $customer_infos->customer_type_id = (is_object($CustomerType)) ? $CustomerType->id : 0;
                        $customer_infos->customer_address_1 = $row[14];
                        $customer_infos->customer_address_2 = $row[15];
                        $customer_infos->customer_city = $row[16];
                        $customer_infos->customer_state = $row[17];
                        $customer_infos->customer_zipcode = $row[18];
                        $customer_infos->customer_phone = $row[19];

                        if (is_object($CustomerType) && $CustomerType->id != 2) {
                            $customer_infos->balance = $row[20];
                            $customer_infos->credit_limit = $row[21];
                            $customer_infos->credit_days = $row[22];
                            if (is_object($PaymentTerm)) {
                                $customer_infos->payment_term_id = $PaymentTerm->id;
                            }
                        }

                        $customer_infos->merchandiser_id = (is_object($Merchandiser)) ? $Merchandiser->id : 0;
                        // $customer_infos->ship_to_party = $row[25];
                        // $customer_infos->sold_to_party = $row[26];
                        // $customer_infos->payer = $row[27];
                        // $customer_infos->bill_to_payer = $row[28];
                        $customer_infos->customer_address_1_lat = $row[29];
                        $customer_infos->customer_address_1_lang = $row[30];
                        $customer_infos->erp_code = $row[12];

                        $customer_infos->current_stage = $current_stage;
                        $customer_infos->current_stage_comment = "";

                        $customer_infos->status = $status;

                        $customer_infos->save();
                        // pre($customer_infos);
                        $updateInfo = CustomerInfo::find($customer_infos->id);
                        $getInfoSTP = CustomerInfo::select('id')->where('customer_code', $row[25])->first();
                        if ($getInfoSTP) {
                            $updateInfo->ship_to_party = $getInfoSTP->id;
                        }

                        $getInfoSTParty = CustomerInfo::select('id')->where('customer_code', $row[26])->first();
                        if ($getInfoSTParty) {
                            $updateInfo->sold_to_party = $getInfoSTParty->id;
                        }

                        $getInfoP = CustomerInfo::select('id')->where('customer_code', $row[27])->first();
                        if ($getInfoP) {
                            $updateInfo->payer = $getInfoP->id;
                        }

                        $getInfoBTP = CustomerInfo::select('id')->where('customer_code', $row[28])->first();

                        if ($getInfoBTP) {
                            $updateInfo->bill_to_payer = $getInfoBTP->id;
                        }
                        $updateInfo->save();

                        if ($isActivate = checkWorkFlowRule('Customer', 'create', $current_organisation_id)) {
                            $this->createWorkFlowObject1($isActivate, 'Customer', $row, $customer_infos);
                        }
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "Customer successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    public function customerBalances(Request $request, $customer_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$customer_id) {
            return prepareResult(false, [], [], "Error while validating customer id.", $this->unprocessableEntity);
        }

        //Customer Invoices
        $invoices_results = Invoice::select(
            DB::raw('SUM(pending_credit) as outstanding_receivable')
        );
        if ($request->lob_id) {
            $invoices_results->where('lob_id', $request->lob_id)->where('customer_id', $customer_id);
        } else {
            $invoices_results->where('customer_id', $customer_id);
        }

        $invoices = $invoices_results->first();

        $dataArray['outstanding_receivable'] = $invoices['outstanding_receivable'];

        //Customer Unused Credit
        $creditNote = CreditNote::select(DB::raw('SUM(pending_credit) as unused_credit'));
        if ($request->lob_id) {
            $creditNote->where('lob_id', $request->lob_id)->where('customer_id', $customer_id);
        } else {
            $creditNote->where('customer_id', $customer_id);
        }
        $creditNote_res =   $creditNote->first();

        $dataArray['unused_credit'] = $creditNote_res['unused_credit'];

        return prepareResult(true, $dataArray, [], "Customer Balances", $this->success);
    }

    public function customerBalanceStatement(Request $request)
    {
        $input = $request->json()->all();
        $customer_id = $input['customer_id'];
        $startdate = Carbon::parse($input['startdate'])->format('Y-m-d');
        $enddate = Carbon::parse($input['enddate'])->format('Y-m-d');
        $status = (isset($input['status']) ? $input['status'] : '');

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$customer_id and !$startdate and !$enddate) {
            return prepareResult(false, [], [], "Error while validating parameters.", $this->unauthorized);
        }

        $lastDateOfPre = Carbon::parse($startdate)->subDays(1);
        $startDateCurrent = Carbon::parse($startdate)->format("d/m/Y");
        $endDateCurrent = Carbon::parse($enddate)->format("d/m/Y");
        //Customer Invoices
        $userDetails = User::Select('*')
            ->with(
                'organisation',
                'organisation.countryInfo:id,name',
                'customerInfo'
            )
            ->where('id', $customer_id)
            ->first();

        $previousBalance_results = Invoice::select(
            DB::raw('SUM(collection_details.pending_amount) as opening_balance')
        )
            ->leftJoin('collection_details', function ($join) {
                $join->on('collection_details.invoice_id', '=', 'invoices.id');
                $join->on(DB::raw('collection_details.id'), DB::raw('(SELECT MAX(id) from collection_details where invoice_id=invoices.id)'));
            });

        if ($request->lob_id) {
            $previousBalance_results->where('invoices.lob_id', $request->lob_id)->where('invoices.customer_id', $customer_id)
                ->where('invoice_date', '<=', $lastDateOfPre);
        } else {
            $previousBalance_results->where('invoices.customer_id', $customer_id)->where('invoice_date', '<=', $lastDateOfPre);
        }
        $previousBalance  = $previousBalance_results->first()->toArray();

        $openBalance = 0.00;
        if (!empty($previousBalance['opening_balance']))
            $openBalance = $previousBalance['opening_balance'];

        $openingBalance['c_date'] = $startDateCurrent;
        $openingBalance['transaction'] = '***Opening Balance***';
        $openingBalance['detail'] = '';
        $openingBalance['amount'] = $openBalance;
        $openingBalance['payment'] = '';
        $openingBalance['status'] = '0';


        //Customer Invoices
        $invoices_result = Invoice::select(DB::raw("DATE_FORMAT(invoice_date,'%d/%m/%Y') as c_date,'Invoice' as transaction,CONCAT(invoice_number,' - due on ',DATE_FORMAT(invoice_due_date,'%d/%m/%y')) as detail,grand_total as amount,'0.00' as payment,1 as status, created_at"));
        if ($request->lob_id) {
            $invoices_result->where('invoices.lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('invoice_date', [$startdate, $enddate]);
        } else {
            $invoices_result->where('customer_id', $customer_id)->whereBetween('invoice_date', [$startdate, $enddate]);
        }
        $invoices = $invoices_result->orderBy('created_at', 'ASC'); //orderBy('invoice_date', 'ASC'); 


        /* $collections_result = Collection::select(DB::raw("DATE_FORMAT(cheque_date,'%d/%m/%Y') as c_date,'Payment Received' as transaction,CONCAT(invoice_amount,' for payment of ',collection_number) as detail,'0.00' as amount,invoice_amount as payment,2 as status, created_at"));
    if ($request->lob_id) {
        $collections_result->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
            ->whereBetween('cheque_date', [$startdate, $enddate]);
    } else {
        $collections_result->where('customer_id', $customer_id)->whereBetween('cheque_date', [$startdate, $enddate]);
    }
    $collections = $collections_result->orderBy('created_at', 'ASC'); //orderBy('cheque_date', 'ASC');
 */

        $collections_result = CollectionDetails::select(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y') as c_date,'Collection' as transaction,
  (select CONCAT(' For payment of ',t2.collection_number)  from collections t2 where t2.id = collection_details.collection_id) as detail,
  '0.00' as amount,amount as payment,2 as status, created_at"));
        if ($request->lob_id) {
            $collections_result->where('lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('created_at', [$startdate, $enddate]);
        } else {
            $collections_result->where('customer_id', $customer_id)->whereBetween('created_at', [$startdate, $enddate]);
        }
        $collections = $collections_result->orderBy('created_at', 'ASC'); //orderBy('cheque_date', 'ASC');

        $credit_note_result = CreditNote::select(DB::raw("DATE_FORMAT(credit_note_date,'%d/%m/%Y') as c_date,'Credit Note' as transaction,credit_note_number as detail, '0.00' as amount, grand_total as payment,3 as status, created_at"));

        if ($request->lob_id) {
            $credit_note_result->where('credit_notes.lob_id', $request->lob_id)->where('customer_id', $customer_id)
                ->whereBetween('credit_note_date', [$startdate, $enddate]);
        } else {
            $credit_note_result->where('customer_id', $customer_id)->whereBetween('credit_note_date', [$startdate, $enddate]);
        }
        $credit_note = $credit_note_result->orderBy('created_at', 'ASC'); //orderBy('credit_note_date', 'ASC');


        $balanceStatement_result = DebitNote::select(DB::raw("DATE_FORMAT(debit_note_date,'%d/%m/%Y') as c_date,  
                                                            (CASE
                                                                WHEN is_debit_note=1  THEN 'Debit Note'
                                                                WHEN is_debit_note=0  THEN 
                                                                    (select t2.item_name from debit_note_listingfee_shelfrent_rebatediscount_details t2 where t2.debit_note_id = debit_notes.id) 
                                                            END ) as transaction , 
                                                            debit_note_number as detail,
                                                            '0.00' as amount,
                                                            grand_total as payment,
                                                            4 as status, created_at"));

        if ($request->lob_id) {
            $balanceStatement_result->where('debit_notes.lob_id', $request->lob_id)
                ->where('customer_id', $customer_id)
                ->whereBetween('debit_note_date', [$startdate, $enddate]);
        } else {
            $balanceStatement_result->where('customer_id', $customer_id)->whereBetween('debit_note_date', [$startdate, $enddate]);
        }

        $balanceStatement = $balanceStatement_result->orderBy('debit_note_date', 'ASC')
            ->union($invoices)
            ->union($collections)
            ->union($credit_note)
            ->orderBy('created_at', 'ASC') //orderBy('c_date', 'ASC')
            ->get();

        $balanceStatement->splice(0, 0, [$openingBalance]);

        if (!is_object($balanceStatement)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        $dataArray['balanceStatement'] = $balanceStatement;
        $openingBalance = $invoiceAmount = $paymentReceived = $paymentReceived = number_format((float)0, 2, '.', '');
        foreach ($balanceStatement as $balance) {
            if ($balance['status'] == 0) {
                $openingBalance = number_format((float)$openingBalance + $balance['amount'], 2, '.', '');
            } elseif ($balance['status'] == 1) {
                $invoiceAmount = number_format((float)$invoiceAmount + $balance['amount'], 2, '.', '');
            } elseif ($balance['status'] == 2) {
                $paymentReceived = number_format((float)$paymentReceived + $balance['payment'], 2, '.', '');
            } elseif ($balance['status'] == 3) {
                $paymentReceived = number_format((float)$paymentReceived + $balance['payment'], 2, '.', '');
            } elseif ($balance['status'] == 4) {
                $paymentReceived = number_format((float)$paymentReceived + $balance['payment'], 2, '.', '');
            }
        }
        $balanceDue = number_format((float)$openingBalance + $invoiceAmount - $paymentReceived, 2, '.', '');
        $accountSummary['statement_date'] = $startDateCurrent . " To " . $endDateCurrent;
        $accountSummary['openingBalance'] = $openingBalance;
        $accountSummary['invoiceAmount'] = $invoiceAmount;
        $accountSummary['paymentReceived'] = $paymentReceived;
        $accountSummary['balanceDue'] = $balanceDue;

        $dataArray['userDetails'] = $userDetails;
        $dataArray['accountSummary'] = (object)$accountSummary;

        if ($status == "pdf") {
            $pdfFilePath = public_path() . "/uploads/statement/balance_statement.pdf";
            $pdfFilePath = url('uploads/statement/balance_statement.pdf');
            PDF::loadView('html.balance_statement_pdf', $dataArray)->save($pdfFilePath);

            $dataArray = array();
            $dataArray['file_url'] = $pdfFilePath;
        } else {
            $html = view('html.balance_statement', $dataArray)->render();
            $dataArray['html_string'] = $html;
        }

        //Prepare Results
        return prepareResult(true, $dataArray, [], "Customer Balance Statement", $this->success);
    }

    public function invoiceChart(Request $request)
    {
        $input = $request->json()->all();
        $customer_id = $input['customer_id'];
        $totalMonths = $input['totalMonths'];
        $lob_id = $input['lob_id'] ? $input['lob_id'] : 'NULL';
        $lob_id_status = $input['lob_id'] ? 1 : 0;

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$customer_id) {
            return prepareResult(false, [], [], "Error while validating customer id.", $this->unauthorized);
        }

        $startDate = Carbon::now()->subMonths($totalMonths);

        //Customer Invoices
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $expenses = DB::select("SELECT y_m,yearmonth,SUM(invoiceBalance) as balance,SUM(expenseBalance) as expenseBalance FROM (
                            (SELECT
                                    DATE_FORMAT(`expense_date`, '%Y-%m') AS y_m,
                                    DATE_FORMAT(`expense_date`, '%b %Y') AS yearmonth,
                                    0 AS invoiceBalance,
                                    SUM(amount) AS expenseBalance
                                    FROM `expenses`
                                    WHERE `expense_date` >=  '$startDate' 
                                    AND
                                        (CASE
                                            WHEN $lob_id_status=1  THEN `customer_id` = $customer_id AND  `lob_id` = $lob_id  
                                            WHEN $lob_id_status=0  THEN
                                                `customer_id` = $customer_id
                                        END )
                                    AND `deleted_at` IS NULL GROUP BY `y_m`
                            )
                        UNION
                            (SELECT
                                    DATE_FORMAT(`invoice_date`, '%Y-%m') AS y_m,
                                    DATE_FORMAT(`invoice_date`, '%b %Y') AS yearmonth,
                                    SUM(grand_total) AS balance,
                                    0 AS expenseBalance
                                FROM `invoices`
                                WHERE  `invoice_date` >=  '$startDate'
                                AND
                                    (CASE
                                            WHEN $lob_id_status=1 THEN `customer_id` = $customer_id AND `lob_id` = $lob_id  
                                            WHEN $lob_id_status=0 THEN
                                                `customer_id` = $customer_id  
                                    END )
                                AND `deleted_at` IS NULL  GROUP BY `y_m`                 
                            )

                        ) as  charts GROUP BY y_m
                    ");

        // Mechanism for Getting empty months
        $yms = array();
        $now = date('Y-m');
        for ($x = $totalMonths - 1; $x >= 0; $x--) {
            $ym = date('Y-m', strtotime($now . " -$x month"));
            //            $ym = date_format(strtotime($ym),'y/m');
            $yms[$ym] = $ym;
        }

        $data_sorted = array();

        foreach ($yms as $key => $value) {
            $found_obj = 0;
            $count = 0;
            $yr_mon = $value;
            foreach ($expenses as $k => $v) {
                if ($v->y_m == $yr_mon) {
                    $count++;
                    $found_obj = $v;
                }
            }
            if ($count == 0) {
                //                Months Not Exists
                $dt_comp = $yr_mon . "-01";
                $date_formatted = date('M Y', strtotime($dt_comp));
                $empty_obj = (object)['y_m' => $yr_mon, 'yearmonth' => $date_formatted, 'balance' => (string)"0.00", 'expenseBalance' => (string)"0.00"];
                array_push($data_sorted, $empty_obj);
            } else {
                array_push($data_sorted, $found_obj);
            }
        }

        $dataArray = $data_sorted;

        //Prepare Results
        return prepareResult(true, $dataArray, [], "Customer Invoice Chart", $this->success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $action
     * @param  string $status
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // if (!checkPermission('item-group-bulk-action')) {
        //     return prepareResult(false, [], [], "You do not have the required authorization.", $this->forbidden);
        // }

        $input = $request->json()->all();
        $validate = $this->validations($input, "bulk-action");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating customer.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->customer_ids;

            foreach ($uuids as $uuid) {
                CustomerInfo::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            // $CustomerInfo = $this->index();
            return prepareResult(true, "", [], "Customer Info status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->customer_ids;
            foreach ($uuids as $uuid) {
                CustomerInfo::where('uuid', $uuid)->delete();
            }

            $CustomerInfo = $this->index();
            return prepareResult(true, $CustomerInfo, [], "Customer Info deleted success", $this->success);
        }
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("First Name", "Last Name", "Email", "Password", "Mobile", "Country", "Status", "Region", "Sales Organisation", "Route", "Channel", "Customer Category", "Customer Code", "Customer Type", "Office Address", "Home Address", "City", "State", "Zipcode", "Phone", "Balance", "Credit Limit", "Credit Days", "Payment Term", "Merchandiser Name", "Ship to party", "Sold to party", "Payer", "Bill to party", "LATITUDE", "LONGITUDE");

        return prepareResult(true, $mappingarray, [], "Customer Mapping Field.", $this->success);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  int $user_id
     * @return \Illuminate\Http\Response
     */
    public function customer_lob($user_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $users = CustomerInfo::select('id', 'user_id', 'is_lob')->where('user_id', $user_id)->where('is_lob', 1)
            ->with(
                'customerlob:id,organisation_id,customer_info_id,lob_id',
                'customerlob.lob:id,name'
            )->get();

        if (!is_object($users) || $users->isEmpty()) {
            return prepareResult(false, [], [], "Customer lob list not present.", $this->unprocessableEntity);
        }

        $users_array = array();
        if (is_object($users)) {
            foreach ($users as $key => $users_1) {
                $users_array[] = $users[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($users_array[$offset])) {
                    $data_array[] = $users_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($users_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($users_array);
        } else {
            $data_array = $users_array;
        }
        return prepareResult(true, $data_array, [], "Customer lob list", $this->success, $pagination);
    }







    /**
     * Get route based invoice or order or Credit list
     *
     * @return \Illuminate\Http\Response
     */

    public function getRouteDetail(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (empty($request->route_id)) {
            return prepareResult(false, [], [], "Route id is required ", $this->unprocessableEntity);
        }
        if (empty($request->type)) {
            return prepareResult(false, [], [], "Type  is required ", $this->unprocessableEntity);
        }

        $salesman_results = SalesmanInfo::where('route_id', $request->route_id)->first();

        $datevalue = Carbon::today();

        if (!is_object($salesman_results)) {
            return prepareResult(false, [], [], "Salesman is not present, for given route id", $this->unprocessableEntity);
        }

        if ($request->type == 'invoice') {
            $invoices = Invoice::with(array('user' => function ($query) {
                $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
            }))
                ->with(
                    'user:id,parent_id,firstname,lastname,email',
                    'depot',
                    'order',
                    'order.orderDetails',
                    'invoices',
                    'invoices.item:id,item_name',
                    'invoices.itemUom:id,name,code',
                    'orderType:id,name,description',
                    'invoiceReminder:id,uuid,is_automatically,message,invoice_id',
                    'invoiceReminder.invoiceReminderDetails',
                    'lob'
                )
                ->where('salesman_id', $salesman_results->user_id)
                ->whereDate('created_at',  Carbon::today())
                ->orderBy('id', 'desc')
                ->get();

            $results = GetWorkFlowRuleObject('Invoice');
            $approve_need_invoice = array();
            $approve_need_invoice_detail_object_id = array();
            if (count($results) > 0) {
                foreach ($results as $raw) {
                    $approve_need_invoice[] = $raw['object']->raw_id;
                    $approve_need_invoice_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
                }
            }

            // approval
            $invoices_array = array();
            if (is_object($invoices)) {
                foreach ($invoices as $key => $invoices1) {
                    if (in_array($invoices[$key]->id, $approve_need_invoice)) {
                        $invoices[$key]->need_to_approve = 'yes';
                        if (isset($approve_need_invoice_detail_object_id[$invoices[$key]->id])) {
                            $invoices[$key]->objectid = $approve_need_invoice_detail_object_id[$invoices[$key]->id];
                        } else {
                            $invoices[$key]->objectid = '';
                        }
                    } else {
                        $invoices[$key]->need_to_approve = 'no';
                        $invoices[$key]->objectid = '';
                    }

                    if ($invoices[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($invoices[$key]->id, $approve_need_invoice)) {
                        $invoices_array[] = $invoices[$key];
                    }
                }
            }

            $invoice_data_array = array();
            $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
            $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($invoices_array[$offset])) {
                        $invoice_data_array[] = $invoices_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($invoices_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($invoices_array);
            } else {
                $invoice_data_array = $invoices_array;
            }
            $data_array = $invoice_data_array;
            return prepareResult(true, $data_array, [], "Todays Invoices listing", $this->success, $pagination);
        } elseif ($request->type == 'credit') {
            $creditnotes_query = CreditNote::with(array('customer' => function ($query) {
                $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
            }))
                ->with(
                    'customer:id,firstname,lastname',
                    'customer.customerinfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmaninfo:id,user_id,salesman_code',
                    'invoice',
                    'creditNoteDetails',
                    'creditNoteDetails.item:id,item_name',
                    'creditNoteDetails.itemUom:id,name,code',
                    'lob'
                );
            $creditnotes = $creditnotes_query->where('salesman_id', $salesman_results->user_id)
                ->whereDate('created_at',  Carbon::today())
                ->orderBy('id', 'desc')->get();

            $results = GetWorkFlowRuleObject('Credit Note');
            $approve_need_creditnotes = array();
            $approve_need_creditnotes_detail_object_id = array();
            if (count($results) > 0) {
                foreach ($results as $raw) {
                    $approve_need_creditnotes[] = $raw['object']->raw_id;
                    $approve_need_creditnotes_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
                }
            }

            // approval
            $creditnotes_array = array();
            if (is_object($creditnotes)) {
                foreach ($creditnotes as $key => $creditnotes1) {
                    if (in_array($creditnotes[$key]->id, $approve_need_creditnotes)) {
                        $creditnotes[$key]->need_to_approve = 'yes';
                        if (isset($approve_need_creditnotes_detail_object_id[$creditnotes[$key]->id])) {
                            $creditnotes[$key]->objectid = $approve_need_creditnotes_detail_object_id[$creditnotes[$key]->id];
                        } else {
                            $creditnotes[$key]->objectid = '';
                        }
                    } else {
                        $creditnotes[$key]->need_to_approve = 'no';
                        $creditnotes[$key]->objectid = '';
                    }

                    if ($creditnotes[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($creditnotes[$key]->id, $approve_need_creditnotes)) {
                        $creditnotes_array[] = $creditnotes[$key];
                    }
                }
            }

            $credit_data_array = array();
            $page = (isset($request->page)) ? $request->page : '';
            $limit = (isset($request->page_size)) ? $request->page_size : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($creditnotes_array[$offset])) {
                        $credit_data_array[] = $creditnotes_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($creditnotes_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($creditnotes_array);
            } else {
                $credit_data_array = $creditnotes_array;
            }
            $data_array =  $credit_data_array;
            return prepareResult(true, $data_array, [], "Todays credit listing", $this->success, $pagination);
        } elseif ($request->type == 'order') {
            $orders_query = Order::with(array('customer' => function ($query) {
                $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
            }))
                ->with(
                    'customer:id,firstname,lastname',
                    'customer.customerInfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmanInfo:id,user_id,salesman_code',
                    'orderType:id,name,description',
                    'paymentTerm:id,name,number_of_days',
                    'orderDetails',
                    'orderDetails.item:id,item_name',
                    'orderDetails.itemUom:id,name,code',
                    'depot:id,depot_name',
                    'lob'
                );
            $orders = $orders_query->where('salesman_id', $salesman_results->user_id)
                ->whereDate('created_at',  Carbon::today())
                ->orderBy('id', 'desc')
                ->get();

            // approval
            $results = GetWorkFlowRuleObject('Order');
            $approve_need_order = array();
            $approve_need_order_object_id = array();
            if (count($results) > 0) {
                foreach ($results as $raw) {
                    $approve_need_order[] = $raw['object']->raw_id;
                    $approve_need_order_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
                }
            }

            // approval
            $orders_array = array();
            if (is_object($orders)) {
                foreach ($orders as $key => $order1) {
                    if (in_array($orders[$key]->id, $approve_need_order)) {
                        $orders[$key]->need_to_approve = 'yes';
                        if (isset($approve_need_order_object_id[$orders[$key]->id])) {
                            $orders[$key]->objectid = $approve_need_order_object_id[$orders[$key]->id];
                        } else {
                            $orders[$key]->objectid = '';
                        }
                    } else {
                        $orders[$key]->need_to_approve = 'no';
                        $orders[$key]->objectid = '';
                    }

                    if ($orders[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($orders[$key]->id, $approve_need_order)) {
                        $orders_array[] = $orders[$key];
                    }
                }
            }
            $data_array = array();
            $page = (isset($request->page)) ? $request->page : '';
            $limit = (isset($request->page_size)) ? $request->page_size : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($orders_array[$offset])) {
                        $data_array[] = $orders_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($orders_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($orders_array);
            } else {
                $data_array = $orders_array;
            }
            return prepareResult(true, $data_array, [], "Todays Orders listing", $this->success, $pagination);
        } else if ($request->type == 'call') {
            $CustomerVisit = CustomerVisit::with(array('customer' => function ($query) {
                $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
            }))
                ->with(
                    'trip',
                    'customer:id,firstname,lastname',
                    'customer.customerinfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmaninfo:id,user_id,salesman_code',
                    'route:id,route_code,route_name',
                    'journeyPlan:id,name'
                )
                ->where('salesman_id', $salesman_results->user_id)
                ->whereDate('created_at', date('Y-m-d'))
                ->groupBy('customer_id', 'date')
                ->orderBy('id', 'desc')
                ->get();


            $CustomerVisit_array = array();
            if (is_object($CustomerVisit)) {
                foreach ($CustomerVisit as $key => $CustomerVisit1) {
                    $CustomerVisit_array[] = $CustomerVisit[$key];
                }
            }

            $data_array = array();
            $page = (isset($request->page)) ? $request->page : '';
            $limit = (isset($request->page_size)) ? $request->page_size : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($CustomerVisit_array[$offset])) {
                        $data_array[] = $CustomerVisit_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($CustomerVisit_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($CustomerVisit_array);
            } else {
                $data_array = $CustomerVisit_array;
            }
            return prepareResult(true, $data_array, [], "Customer Visit listing", $this->success, $pagination);
        } else if ($request->type == 'visit') {
            $CustomerVisit = CustomerVisit::with(array('customer' => function ($query) {
                $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
            }))
                ->with(
                    'trip',
                    'customer:id,firstname,lastname',
                    'customer.customerinfo:id,user_id,customer_code',
                    'salesman:id,firstname,lastname',
                    'salesman.salesmaninfo:id,user_id,salesman_code',
                    'route:id,route_code,route_name',
                )
                ->where('route_id', $request->route_id)
                ->whereDate('created_at', date('Y-m-d'))
                ->where('shop_status', "open")
                ->whereNull('reason')
                ->groupBy('customer_id', 'date')
                ->get();

            $CustomerVisit_array = array();
            if (is_object($CustomerVisit)) {
                foreach ($CustomerVisit as $key => $CustomerVisit1) {
                    $CustomerVisit_array[] = $CustomerVisit[$key];
                }
            }

            $data_array = array();
            $page = (isset($request->page)) ? $request->page : '';
            $limit = (isset($request->page_size)) ? $request->page_size : '';
            $pagination = array();
            if ($page != '' && $limit != '') {
                $offset = ($page - 1) * $limit;
                for ($i = 0; $i < $limit; $i++) {
                    if (isset($CustomerVisit_array[$offset])) {
                        $data_array[] = $CustomerVisit_array[$offset];
                    }
                    $offset++;
                }

                $pagination['total_pages'] = ceil(count($CustomerVisit_array) / $limit);
                $pagination['current_page'] = (int)$page;
                $pagination['total_records'] = count($CustomerVisit_array);
            } else {
                $data_array = $CustomerVisit_array;
            }
            return prepareResult(true, $data_array, [], "Customer Visit listing", $this->success, $pagination);
        }
    }

    /**
     * Get route based sales Amount, Order amount,Return Amount
     *
     * @return \Illuminate\Http\Response
     */
    public function getRouteInfo(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (empty($request->route_id)) {
            return prepareResult(false, [], [], "Route id is required ", $this->unprocessableEntity);
        }

        $salesman_results = SalesmanInfo::where('route_id', $request->route_id)->get();

        if (!count($salesman_results)) {
            return prepareResult(false, [], [], "Salesman is not present, for given route id", $this->unprocessableEntity);
        }

        $user_ids = $salesman_results->pluck('user_id')->toArray();

        $invoices = Invoice::select('id', 'grand_total')
            ->whereIn('salesman_id', $user_ids)
            ->whereDate('created_at', date('Y-m-d'))
            ->get();

        if (count($invoices)) {
            $data = array_sum($invoices->pluck('grand_total')->toArray());
            $data_array['sales_amount'] = $data;
        } else {
            $data_array['sales_amount'] = 0;
        }

        $credit_note = CreditNote::select('id', 'grand_total')
            ->whereIn('salesman_id', $user_ids)
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        if (count($credit_note)) {
            $data = array_sum($credit_note->pluck('grand_total')->toArray());
            $data_array['return_amount'] = $data;
        } else {
            $data_array['return_amount'] = 0;
        }

        $collection = Collection::select('id', 'invoice_amount')
            ->whereIn('salesman_id', $user_ids)
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        if (count($collection)) {
            $data = array_sum($collection->pluck('invoice_amount')->toArray());
            $data_array['collection_amount'] = $data;
        } else {
            $data_array['collection_amount'] = 0;
        }

        $orders = Order::select('id', 'grand_total')
            ->whereIn('salesman_id', $user_ids)
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        if (count($orders)) {
            $data = array_sum($orders->pluck('grand_total')->toArray());
            $data_array['order_amount'] = $data;
        } else {
            $data_array['order_amount'] = 0;
        }

        $customer_visit = CustomerVisit::whereIn('salesman_id', $user_ids)
            ->where('shop_status', "open")
            ->whereNull('reason')
            ->whereDate('date', date('Y-m-d'))
            ->groupBy('customer_id', 'date')
            ->get();

        if (count($customer_visit)) {
            $data_array['visit_count'] = count($customer_visit);
        } else {
            $data_array['visit_count'] = 0;
        }

        return prepareResult(true, $data_array, [], "Amount listing", $this->success);
    }



    /**
     * Get invoice count, Total No of Visit, Item count,Carry over count, based on route id
     *
     * @return \Illuminate\Http\Response
     */
    public function getCount(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (empty($request->route_id)) {
            return prepareResult(false, [], [], "Route id is required ", $this->unprocessableEntity);
        }

        $salesman_results = SalesmanInfo::where('route_id', $request->route_id)->first();

        if (!is_object($salesman_results)) {
            return prepareResult(false, [], [], "Salesman is not present, for given route id", $this->unprocessableEntity);
        }

        $invoices_result = Invoice::where('salesman_id', $salesman_results->user_id)
            ->whereDate('invoice_date', Carbon::today())
            ->get()->count();
        $data_array['invoice_count'] = $invoices_result;


        $visit_result = CustomerVisit::where('salesman_id', $salesman_results->user_id)
            ->whereDate('date', Carbon::today())
            ->get()->count();
        $data_array['customer_visit_count'] = $visit_result;


        $invoices_result = Invoice::select(DB::raw("id"))
            ->where('salesman_id', $salesman_results->user_id)
            ->whereDate('invoice_date', Carbon::today())
            ->get()->toArray();

        $invoices_details_result = InvoiceDetail::select(DB::raw("promotion_id"))->whereIn('invoice_id', $invoices_result)
            ->where('is_free', 1)
            ->get()->toArray();

        $pdp_item_result = PDPItem::whereIn('price_disco_promo_plan_id', $invoices_details_result)
            ->get()->count();

        $data_array['pdp_item_count'] = $pdp_item_result;

        return prepareResult(true, $data_array, [], "count details", $this->success);
    }












    /**
     * Get invoice count, Total No of Visit, Item count,Carry over count, based on route id
     *
     * @return \Illuminate\Http\Response
     */
    public function getAlldetails(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (empty($request->route_id)) {
            return prepareResult(false, [], [], "Route id is required ", $this->unprocessableEntity);
        }

        if (empty($request->type)) {
            return prepareResult(false, [], [], "type is required ", $this->unprocessableEntity);
        }

        if (empty($request->date)) {
            $date = Carbon::today();
        } else {
            $date = $request->date;
        }

        $salesman_results = SalesmanInfo::select('user_id')->where('route_id', $request->route_id)->get()->pluck('user_id')->toArray();


        if (!count($salesman_results)) {
            return prepareResult(false, [], [], "Salesman is not present, for given route id", $this->unprocessableEntity);
        }

        if ($request->type == "invoice") {
            return $this->getInoicedetails($request, $salesman_results, $date);
        }

        if ($request->type == "call") {
            return $this->getCustomerVisit($request, $salesman_results, $date);
        }

        if ($request->type == "foc") {
            return $this->getFoc($request, $salesman_results, $date);
        }

        if ($request->type == "cov") {
            return $this->getCarryover($request, $salesman_results, $date);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getInoicedetails($request, $salesman_id, $date)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $invoices = Invoice::with(array('user' => function ($query) {
            $query->select('id', 'firstname', 'lastname', \DB::raw("CONCAT('firstname','lastname') AS display_name"));
        }))
            ->with(
                'user:id,parent_id,firstname,lastname,email',
                'user.customerInfo:id,user_id,customer_code',
                'depot',
                'order',
                'order.orderDetails',
                'invoices',
                'invoices.item:id,item_name',
                'invoices.itemUom:id,name,code',
                'orderType:id,name,description',
                'invoiceReminder:id,uuid,is_automatically,message,invoice_id',
                'invoiceReminder.invoiceReminderDetails',
                'lob'
            )
            ->whereIn('salesman_id', $salesman_id)
            ->whereDate('invoice_date', $date)
            ->orderBy('id', 'desc')
            ->get();
        //->limit($limit)->offset($offset)->get();
        // approval
        $results = GetWorkFlowRuleObject('Invoice');
        $approve_need_invoice = array();
        $approve_need_invoice_detail_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_invoice[] = $raw['object']->raw_id;
                $approve_need_invoice_detail_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $invoices_array = array();
        if (is_object($invoices)) {
            foreach ($invoices as $key => $invoices1) {
                if (in_array($invoices[$key]->id, $approve_need_invoice)) {
                    $invoices[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_invoice_detail_object_id[$invoices[$key]->id])) {
                        $invoices[$key]->objectid = $approve_need_invoice_detail_object_id[$invoices[$key]->id];
                    } else {
                        $invoices[$key]->objectid = '';
                    }
                } else {
                    $invoices[$key]->need_to_approve = 'no';
                    $invoices[$key]->objectid = '';
                }

                if ($invoices[$key]->current_stage == 'Approved' || request()->user()->usertype == 1 || in_array($invoices[$key]->id, $approve_need_invoice)) {
                    $invoices_array[] = $invoices[$key];
                }
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($invoices_array[$offset])) {
                    $data_array[] = $invoices_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($invoices_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($invoices_array);
        } else {
            $data_array = $invoices_array;
        }

        return prepareResult(true, $data_array, [], "Invoices listing", $this->success, $pagination);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerVisit($request, $salesman_id, $date)
    {
        // echo "<pre>"; print_r($salesman_id); exit;

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $CustomerVisit = DB::table('customer_visits')->select('customer_visits.salesman_id'/* ,'customer_visits.customer_id' */)
            ->selectRaw("COUNT(  customer_visits.id) as Visited_count")
            ->selectRaw("COUNT(  (case when customer_visits.is_sequnece = '1' then 1 end)) as schedule_call")
            ->selectRaw("COUNT(  (case when customer_visits.is_sequnece = '0' then 1 end)) as unschedule_call")

            ->whereIn('customer_visits.salesman_id', $salesman_id)
            ->groupBy('customer_visits.salesman_id')
            ->whereDate('date', $date)
            ->orderBy('customer_visits.id', 'desc')
            ->get();

        $CustomerVisit_invoice_schedule_sale = DB::table('customer_visits')->select('customer_visits.salesman_id'/* ,'customer_visits.customer_id' */)
            ->selectRaw("COUNT( distinct invoices.customer_id) as schedule_sale")

            ->leftJoin('invoices', function ($join) {
                $join->on('invoices.customer_id', '=', 'customer_visits.customer_id')->whereNull('invoices.deleted_at');
            })

            ->whereIn('customer_visits.salesman_id', $salesman_id)
            ->groupBy('customer_visits.salesman_id')
            ->where('customer_visits.is_sequnece', '1')
            ->whereDate('date', $date)
            ->orderBy('customer_visits.id', 'desc')
            ->get();


        $CustomerVisit_invoice_unschedule_sale = DB::table('customer_visits')->select('customer_visits.salesman_id'/* ,'customer_visits.customer_id' */)
            ->selectRaw("COUNT( distinct invoices.customer_id) as unschedule_sale")

            ->leftJoin('invoices', function ($join) {
                $join->on('invoices.customer_id', '=', 'customer_visits.customer_id')->whereNull('invoices.deleted_at');
            })
            ->whereIn('customer_visits.salesman_id', $salesman_id)
            ->groupBy('customer_visits.salesman_id')
            ->where('customer_visits.is_sequnece', '0')
            ->whereDate('date', $date)
            ->orderBy('customer_visits.id', 'desc')
            ->get();

        /*---------- get index of values from 2nd array if match the particular index of values from 1st array   ---------- */
        $CustomerVisit = json_decode(json_encode($CustomerVisit), true);
        $CustomerVisit_invoice_schedule_sale = json_decode(json_encode($CustomerVisit_invoice_schedule_sale), true);
        $CustomerVisit_invoice_unschedule_sale = json_decode(json_encode($CustomerVisit_invoice_unschedule_sale), true);

        foreach ($CustomerVisit as $key => $invoices_value) {
            $i = array_search($invoices_value['salesman_id'], array_column($CustomerVisit_invoice_schedule_sale, 'salesman_id'));
            $return_val = ($i !== false ? $CustomerVisit_invoice_schedule_sale[$i] : null);
            $CustomerVisit[$key]['schedule_sale'] = $return_val['schedule_sale'];
        }

        foreach ($CustomerVisit as $key => $invoices_value) {
            $i = array_search($invoices_value['salesman_id'], array_column($CustomerVisit_invoice_unschedule_sale, 'salesman_id'));
            $return_val = ($i !== false ? $CustomerVisit_invoice_unschedule_sale[$i] : null);
            $CustomerVisit[$key]['unschedule_sale'] = $return_val['unschedule_sale'];
        }

        /*---------- get index of values from 2nd array if match the particular index of values from 1st array   ---------- */


        $CustomerVisit_array = array();
        if (!empty($CustomerVisit)) {
            foreach ($CustomerVisit as $key => $CustomerVisit1) {
                $CustomerVisit_array[] = $CustomerVisit[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($CustomerVisit_array[$offset])) {
                    $data_array[] = $CustomerVisit_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($CustomerVisit_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($CustomerVisit_array);
        } else {
            $data_array = $CustomerVisit_array;
        }
        return prepareResult(true, $data_array, [], "Customer Visit listing", $this->success, $pagination);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getFoc($request, $salesman_id, $date)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $invoices_result = Invoice::select(DB::raw("id"))
            ->whereIn('salesman_id', $salesman_id)
            ->whereDate('invoice_date', $date)
            ->get()->toArray();

        $invoices_details_result = InvoiceDetail::select(DB::raw("promotion_id"))
            ->whereIn('invoice_id', $invoices_result)
            ->where('is_free', 1)
            ->get()->toArray();

        $pdp_item_result = PriceDiscoPromoPlan::with(
            'PDPItems',
            'PDPPromotionOfferItems',
            'PDPPromotionOfferItems',

        )
            ->whereIn('id', $invoices_details_result)
            ->get();

        // $pdp_item_result = PDPItem::with('item', 'itemUom')
        //     ->whereIn('price_disco_promo_plan_id', $invoices_details_result)
        //     ->get();

        $PDPItem_array = array();
        if (is_object($pdp_item_result)) {
            foreach ($pdp_item_result as $key => $pdp_item_result_1) {
                $PDPItem_array[] = $pdp_item_result[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($PDPItem_array[$offset])) {
                    $data_array[] = $PDPItem_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($PDPItem_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($PDPItem_array);
        } else {
            $data_array = $PDPItem_array;
        }

        return prepareResult(true, $data_array, [], "FOC details", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCarryover($request, $salesman_id, $date)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $UnloadHeader = SalesmanUnload::with(
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'salesmanUnloadDetail',
            'salesmanUnloadDetail.item:id,item_name,item_code',
            'salesmanUnloadDetail.itemUom:id,name'
        )
            ->whereIn('salesman_id', $salesman_id)
            ->whereDate('created_at', $date)
            ->orderBy('id', 'desc')
            ->get();

        $UnloadHeader_array = array();
        if (is_object($UnloadHeader)) {
            foreach ($UnloadHeader as $key => $UnloadHeader1) {
                $UnloadHeader_array[] = $UnloadHeader[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($UnloadHeader_array[$offset])) {
                    $data_array[] = $UnloadHeader_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($UnloadHeader_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($UnloadHeader_array);
        } else {
            $data_array = $UnloadHeader_array;
        }

        return prepareResult(true, $data_array, [], "Salesman Unload listing", $this->success, $pagination);
    }

    public function searchCustomer(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $name = $request->name;

        $users_query = CustomerInfo::select('id', 'user_id', 'customer_code')
            ->with(
                'user:id,firstname,lastname',
            )
            ->orderBy('id', 'asc');

        $users_query->where('customer_code', 'like', '%' . $name . '%');

        $exploded_name = explode(" ", $name);

        if (count($exploded_name) < 2) {
            $users_query->whereHas('user', function ($q) use ($name) {
                $q->orWhere('firstname', 'like', '%' . $name . '%')
                    ->orWhere('lastname', 'like', '%' . $name . '%');
            });
        } else {
            foreach ($exploded_name as $n) {
                $users_query->whereHas('user', function ($q) use ($n) {
                    $q->orWhere('firstname', 'like', '%' . $n . '%')
                        ->orWhere('lastname', 'like', '%' . $n . '%');
                });
            }
        }

        $users = $users_query->get();

        $users_array = array();
        if (is_object($users)) {
            foreach ($users as $key => $users1) {
                $users_array[] = $users[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($users_array[$offset])) {
                    $data_array[] = $users_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($users_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($users_array);
        } else {
            $data_array = $users_array;
        }

        return prepareResult(true, $data_array, [], "Customer listing", $this->success, $pagination);
    }
}
