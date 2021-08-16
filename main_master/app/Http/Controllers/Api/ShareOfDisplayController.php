<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\ShareOfDisplay;
use App\Model\ShareOfDisplayCompetitor;
use App\Model\ShareOfDisplayOurBrand;
use Illuminate\Http\Request;

class ShareOfDisplayController extends Controller
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

        $share_of_display_query = ShareOfDisplay::select('id', 'salesman_id', 'customer_id', 'date', 'gandola_store', 'stands_store', 'added_on')
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

        if ($request->date) {
            $share_of_display_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $share_of_display_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $share_of_display_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $share_of_display_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $share_of_display_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customerCode = $request->customer_code;
            $share_of_display_query->whereHas('customer.customerInfo', function ($q) use ($customerCode) {
                $q->where('customer_code', $customerCode);
            });
        }

        $share_of_display = $share_of_display_query->orderBy('id', 'desc')
            ->get();

        $share_of_display_array = array();
        if (is_object($share_of_display)) {
            foreach ($share_of_display as $key => $share_of_display1) {
                $share_of_display_array[] = $share_of_display[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
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

        return prepareResult(true, $data_array, [], "Share assortment listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating share of display", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $share_of_display = new ShareOfDisplay;
            $share_of_display->salesman_id = $request->salesman_id;
            $share_of_display->customer_id  = $request->customer_id;
            $share_of_display->date  = $request->date;
            $share_of_display->gandola_store  = $request->gandola_store;
            $share_of_display->stands_store  = $request->stands_store;
            $share_of_display->added_on  = $request->added_on;
            $share_of_display->save();

            if (is_array($request->share_of_our_brand) && sizeof($request->share_of_our_brand) >= 1) {
                foreach ($request->share_of_our_brand as $brand) {
                    $share_of_our_brand = new ShareOfDisplayOurBrand;
                    $share_of_our_brand->share_of_display_id = $share_of_display->id;
                    $share_of_our_brand->brand_id = $brand['brand_id'];
                    $share_of_our_brand->item_major_category_id = $brand['item_major_category_id'];
                    $share_of_our_brand->catured_gandola = $brand['catured_gandola'];
                    $share_of_our_brand->catured_stand = $brand['catured_stand'];
                    $share_of_our_brand->brand_share = $brand['brand_share'];
                    $share_of_our_brand->save();
                }
            }

            if (is_array($request->share_of_competitor) && sizeof($request->share_of_competitor) >= 1) {
                foreach ($request->share_of_competitor as $competitor) {
                    $share_of_competitor = new ShareOfDisplayCompetitor;
                    $share_of_competitor->share_of_display_id = $share_of_display->id;
                    $share_of_competitor->competitor_brand_id = $competitor['competitor_brand_id'];
                    $share_of_competitor->competitor_catured_gandola = $competitor['competitor_catured_gandola'];
                    $share_of_competitor->competitor_catured_stand = $competitor['competitor_catured_stand'];
                    $share_of_competitor->competitor_brand_share = $competitor['competitor_brand_share'];
                    $share_of_competitor->save();
                }
            }


            \DB::commit();

            $share_of_display->getSaveData();

            return prepareResult(true, $share_of_display, [], "Share assortment successfully", $this->created);
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
     * @param  \App\Model\ShareOfAssortment  $shareOfAssortment
     * @return \Illuminate\Http\Response
     */
    public function edit(ShareOfAssortment $shareOfAssortment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\ShareOfAssortment  $shareOfAssortment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShareOfAssortment $shareOfAssortment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\ShareOfAssortment  $shareOfAssortment
     * @return \Illuminate\Http\Response
     */
    public function destroy(ShareOfAssortment $shareOfAssortment)
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
                'date' => 'required|date',
                'gandola_store' => 'required',
                'stands_store' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
