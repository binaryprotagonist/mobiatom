<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\PortfolioManagement;
use App\Model\PricingCheck;
use App\Model\PricingCheckDetail;
use App\Model\PricingCheckDetailPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexMobile(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $customer_id = $request->customer_id;
        $salesman_id = $request->salesman_id;

        $portfolioManagement = PortfolioManagement::with(
            'portfolioManagementCustomer',
            'portfolioManagementCustomer.user:id,firstname,lastname',
            'portfolioManagementCustomer.user.customerInfo:id,user_id,customer_code',
            'portfolioManagementItem',
            'portfolioManagementItem.item:id,item_name,item_code',
            'portfolioManagementItem.item.pricingCheckDetail'
        )
            ->whereHas('portfolioManagementCustomer', function ($q) use ($customer_id) {
                $q->where('user_id', $customer_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        // $PricingCheck = array();
        // if (count($portFolio)) {
        //     foreach ($portFolio as $port) {
        //         $item_ids = $port->portfolioManagementItem->pluck('item_id')->toArray();

        //         $pricing_checks = PricingCheckDetail::whereIn('item_id', $item_ids)
        //             ->with('pricingCheck')
        //             ->whereHas('pricingCheck', function ($q) use ($customer_id, $salesman_id) {
        //                 $q->where('customer_id', $customer_id)
        //                     ->where('salesman_id', $salesman_id);
        //             })
        //             ->orderBy('date', 'desc')
        //             ->first();

        //         // if (is_object($pricing_check)) {
        //         //     $final = $pricing_check->pricingDetails()->orderBy('date', 'desc')->first();
        //         // }
        //             $PricingCheck[]  = $pricing_checks;
        //     }
        // }



        $portfolioManagement_array = array();
        if (is_object($portfolioManagement)) {
            foreach ($portfolioManagement as $key => $portfolioManagement1) {
                $portfolioManagement_array[] = $portfolioManagement[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($portfolioManagement_array[$offset])) {
                    $data_array[] = $portfolioManagement_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($portfolioManagement_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($portfolioManagement_array);
        } else {
            $data_array = $portfolioManagement_array;
        }

        return prepareResult(true, $data_array, [], "Share assortment listing", $this->success, $pagination);
    }

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

        $date = date('Y-m-d');
        $extended_date = Date('Y-m-d', strtotime('-9 days'));

        $pricing_check_query = PricingCheck::select('id', 'brand_id', 'salesman_id', 'customer_id', 'added_on', 'created_at')
            ->with(
                'brand:id,brand_name',
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'pricingDetails:id,pricing_check_id,item_id,item_major_category_id',
                'pricingDetails.item:id,item_name,item_code',
                'pricingDetails.itemMajorCategory:id,name',
                'pricingDetails.pricingCheckDetailPrices:id,pricing_check_id,pricing_check_detail_id,price,srp'
            );

        if ($request->date) {
            $pricing_check_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }


        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $pricing_check_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $pricing_check_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $pricing_check_query->whereHas('customer.customerinfo', function ($q) use ($customer_code) {
                $q->where('customer_code', $customer_code);
            });
        }

        if ($request->brand) {
            $brand = $request->brand;
            $pricing_check_query->whereHas('brand', function ($q) use ($brand) {
                $q->where('brand_name', $brand);
            });
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $pricing_check_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $pricing_check_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        $pricing_check = $pricing_check_query->whereBetween('added_on', [$extended_date, $date])
            ->orderBy('id', 'asc')
            ->get();

        $pricing_check_array = array();

        if (is_object($pricing_check)) {
            foreach ($pricing_check as $key => $pricingcheck) {
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating pricing check", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $pricing_check = PricingCheck::where('customer_id', $request->customer_id)
                ->where('salesman_id', $request->salesman_id)
                ->where('brand_id', $request->brand_id)
                ->first();

            if (is_object($pricing_check)) {
                if (is_array($request->day_details) && sizeof($request->day_details) >= 1) {
                    foreach ($request->day_details as $detail) {
                        $pricing_check_detail = PricingCheckDetail::where('item_id', $detail['item_id'])
                            ->where('item_major_category_id', $detail['item_major_category_id'])
                            ->where('pricing_check_id', $pricing_check->id)
                            ->first();
                        if (!is_object($pricing_check_detail)) {
                            $pricing_check_detail = new PricingCheckDetail;
                        }
                        $pricing_check_detail->pricing_check_id = $pricing_check->id;
                        $pricing_check_detail->date = $request->date;
                        $pricing_check->added_on = $request->added_on;
                        $pricing_check_detail->item_id = $detail['item_id'];
                        $pricing_check_detail->item_major_category_id = $detail['item_major_category_id'];
                        // $pricing_check_detail->srp = $detail['srp'];
                        // $pricing_check_detail->price = $detail['price'];
                        $pricing_check_detail->save();

                        if ($detail['price'] && $detail['srp']) {
                            $pricing_check_detailPrice = new PricingCheckDetailPrice;
                            $pricing_check_detailPrice->pricing_check_id = $pricing_check->id;
                            $pricing_check_detailPrice->pricing_check_detail_id = $pricing_check_detail->id;
                            $pricing_check_detailPrice->srp = $detail['srp'];
                            $pricing_check_detailPrice->price = $detail['price'];
                            $pricing_check_detailPrice->save();
                        }
                    }
                }
            } else {
                $pricing_check = new PricingCheck;
                $pricing_check->customer_id = $request->customer_id;
                $pricing_check->salesman_id = $request->salesman_id;
                $pricing_check->brand_id = $request->brand_id;
                $pricing_check->date = $request->date;
                $pricing_check->added_on = $request->added_on;
                $pricing_check->save();

                if (is_array($request->day_details) && sizeof($request->day_details) >= 1) {
                    foreach ($request->day_details as $detail) {
                        $pricing_check_detail = PricingCheckDetail::where('item_id', $detail['item_id'])
                            ->where('item_major_category_id', $detail['item_major_category_id'])
                            ->where('pricing_check_id', $pricing_check->id)
                            ->first();
                        if (!is_object($pricing_check_detail)) {
                            $pricing_check_detail = new PricingCheckDetail;
                        }
                        $pricing_check_detail->pricing_check_id = $pricing_check->id;
                        $pricing_check_detail->date = $request->date;
                        $pricing_check_detail->item_id = $detail['item_id'];
                        $pricing_check_detail->item_major_category_id = $detail['item_major_category_id'];
                        $pricing_check_detail->save();

                        if ($detail['price'] && $detail['srp']) {
                            $pricing_check_detailPrice = new PricingCheckDetailPrice;
                            $pricing_check_detailPrice->pricing_check_id = $pricing_check->id;
                            $pricing_check_detailPrice->pricing_check_detail_id = $pricing_check_detail->id;
                            $pricing_check_detailPrice->srp = $detail['srp'];
                            $pricing_check_detailPrice->price = $detail['price'];
                            $pricing_check_detailPrice->save();
                        }
                    }
                }
            }

            \DB::commit();

            $pricing_check->getSaveData();

            return prepareResult(true, $pricing_check, [], "Pricing Check successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\PricingCheck  $pricingCheck
     * @return \Illuminate\Http\Response
     */
    public function show(PricingCheck $pricingCheck)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\PricingCheck  $pricingCheck
     * @return \Illuminate\Http\Response
     */
    public function edit(PricingCheck $pricingCheck)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\PricingCheck  $pricingCheck
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PricingCheck $pricingCheck)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\PricingCheck  $pricingCheck
     * @return \Illuminate\Http\Response
     */
    public function destroy(PricingCheck $pricingCheck)
    {
        //
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'salesman_id' => 'required|integer|exists:users,id',
                'customer_id' => 'required|integer|exists:users,id',
                'brand_id' => 'required|integer|exists:brands,id',
                'date' => 'required|date'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
