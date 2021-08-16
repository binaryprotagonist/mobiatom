<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CollectionDetails;
use App\Model\CustomerInfo;
use Illuminate\Http\Request;

use App\Model\PremiumDetail;
use App\Model\PremiumCustomer;
/* use App\Model\ItemUom;
use App\Model\Route;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;
use App\Model\SalesmanInfo; */
use App\User;

class PremiumController extends Controller
{
    /**
     * Display a listing of the resource.  status is Pending
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $LoadRequest = PremiumDetail::with(               
                'PremiumCustomer',
                'PremiumCustomer.CustomerInfo', 
                'PremiumCustomer.CustomerInfo.user',
            )
             ->orderBy('id', 'desc')            
            ->get();

        $LoadRequest_array = array();
        if (is_object($LoadRequest)) {
            foreach ($LoadRequest as $key => $LoadRequest1) {
                $LoadRequest_array[] = $LoadRequest[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($LoadRequest_array[$offset])) {
                    $data_array[] = $LoadRequest_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($LoadRequest_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($LoadRequest_array);
        } else {
            $data_array = $LoadRequest_array;
        }


        return prepareResult(true, $data_array, [], "Premium listing", $this->success, $pagination);
    }
  
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();

        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating premium request", $this->unprocessableEntity);
        }

        if (is_array($request->customers) && sizeof($request->customers) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $premium_detail = new PremiumDetail;
            $premium_detail->name             = $request->name;
            $premium_detail->valid_from       = date('Y-m-d', strtotime($request->valid_from));
            $premium_detail->valid_to         = date('Y-m-d', strtotime($request->valid_to));
            $premium_detail->type             = $request->type;
            $premium_detail->qty              = $request->qty;
            $premium_detail->amount           = $request->amount;
            $premium_detail->invoice_amount   = $request->invoice_amount;
            $premium_detail->save();

            if (is_array($request->customers)) {
                foreach ($request->customers as $customer) {
                    $customer_info = CustomerInfo::where('user_id',$customer['customer_id'])->first();
                    $premium_customer = new PremiumCustomer;                    
                    $premium_customer->premium_detail_id = $premium_detail->id;
                    $premium_customer->customer_id       = $customer_info->id;               
                    $premium_customer->save();
                }
            }
            \DB::commit();
            $premium_detail->getSaveData();
            return prepareResult(true, $premium_detail, [], "premium detail added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "select any one premium record id", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], [], "Error while validating premium request", $this->unprocessableEntity);
        }

        if (is_array($request->customers) && sizeof($request->customers) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
        } 

        \DB::beginTransaction();
        try {
            $premium_detail= PremiumDetail::where('uuid', $uuid)->first();  
            if (is_object($premium_detail)) {  

                $premium_detail->name             = $request->name;
                $premium_detail->valid_from       = date('Y-m-d', strtotime($request->valid_from));
                $premium_detail->valid_to         = date('Y-m-d', strtotime($request->valid_to));
                $premium_detail->type             = $request->type;
                $premium_detail->qty              = $request->qty;
                $premium_detail->amount           = $request->amount;
                $premium_detail->invoice_amount   = $request->invoice_amount;
                $premium_detail->save();
                
                $premium_customer_result =  PremiumCustomer::where('premium_detail_id', $premium_detail->id)->get();
                if (is_object($premium_customer_result)) {

                        $premium_customer_result =  PremiumCustomer::where('premium_detail_id', $premium_detail->id)->delete(); 
                    if (is_array($request->customers)) {
                        foreach ($request->customers as $customer) {
                            $customer_info = CustomerInfo::where('user_id',$customer['customer_id'])->first(); 
                            $premium_customer = new PremiumCustomer;
                            $premium_customer->premium_detail_id = $premium_detail->id;
                            $premium_customer->customer_id       = $customer_info->id;                 
                            $premium_customer->save();
                        }
                    } 
                }                
            } else {
                return prepareResult(true, [], [], "Record not found.", $this->not_found);
            }

            \DB::commit();
            $premium_detail->getSaveData();
            return prepareResult(true, $premium_detail, [], "premium detail updated successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating premium.", $this->unauthorized);
        }

        $premium_detail = PremiumDetail::where('uuid', $uuid)->first();

        if (is_object($premium_detail)) {
             if ($premium_detail) {
                PremiumCustomer::where('premium_detail_id', $premium_detail->id)->delete();
            }   
            $premium_detail->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        } else {
            return prepareResult(true, [], [], "Record not found.", $this->not_found);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }


    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [ 
                'name' => 'required',
                'valid_from' => 'required|date',
                'valid_to' => 'required|date',
                'type' => 'required',               
                'invoice_amount' => 'required|integer'   
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

     
}
