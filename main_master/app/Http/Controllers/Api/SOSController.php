<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\SOS;
use App\Model\SOSCompetitor;
use App\Model\SOSOurBrand;
use Illuminate\Http\Request;

class SOSController extends Controller
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

        $sos_query = SOS::select('id', 'salesman_id', 'customer_id', 'date', 'no_of_Shelves', 'block_store', 'added_on')
            ->with(
                'salesman:id,firstname,lastname',
                'salesman.salesmanInfo:id,user_id,salesman_code',
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,customer_code',
                'sosOurBrand',
                'sosOurBrand.item:id,item_name',
                'sosOurBrand.brand:id,brand_name',
                'sosOurBrand.itemMajorCategory:id,name',
                'sosCompetitor.brand:id,brand'
            );

        if ($request->date) {
            $sos_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $sos_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $sos_query->whereHas('salesman', function ($q) use ($n) {
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
                $sos_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                        ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $sos_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $customerCode = $request->customer_code;
            $sos_query->whereHas('customer.customerInfo', function ($q) use ($customerCode) {
                $q->where('customer_code', $customerCode);
            });
        }

        $sos = $sos_query->orderBy('id', 'desc')
            ->get();

        $sos_array = array();
        if (is_object($sos)) {
            foreach ($sos as $key => $sos1) {
                $sos_array[] = $sos[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
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

            $sos = new SOS;
            $sos->salesman_id = $request->salesman_id;
            $sos->customer_id  = $request->customer_id;
            $sos->date  = $request->date;
            $sos->block_store  = $request->block_store;
            $sos->no_of_Shelves  = $request->no_of_Shelves;
            $sos->added_on  = $request->added_on;
            $sos->save();

            if (is_array($request->sos_our_brand) && sizeof($request->sos_our_brand) >= 1) {
                foreach ($request->sos_our_brand as $brand) {
                    $sos_our_brand = new SOSOurBrand;
                    $sos_our_brand->sos_id = $sos->id;
                    $sos_our_brand->brand_id = $brand['brand_id'];
                    $sos_our_brand->item_major_category_id = $brand['item_major_category_id'];
                    $sos_our_brand->catured_block = $brand['catured_block'];
                    $sos_our_brand->catured_shelves = $brand['catured_shelves'];
                    $sos_our_brand->brand_share = $brand['brand_share'];
                    $sos_our_brand->save();
                }
            }

            if (is_array($request->sos_competitor) && sizeof($request->sos_competitor) >= 1) {
                foreach ($request->sos_competitor as $competitor) {
                    $sos_competitor = new SOSCompetitor;
                    $sos_competitor->sos_id = $sos->id;
                    $sos_competitor->competitor_brand_id = $competitor['competitor_brand_id'];
                    $sos_competitor->competitor_catured_block = $competitor['competitor_catured_block'];
                    $sos_competitor->competitor_catured_shelves = $competitor['competitor_catured_shelves'];
                    $sos_competitor->competitor_brand_share = $competitor['competitor_brand_share'];
                    $sos_competitor->save();
                }
            }


            \DB::commit();

            $sos->getSaveData();

            return prepareResult(true, $sos, [], "share of shelf successfully", $this->created);
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
     * @param  \App\Model\SOS  $sOS
     * @return \Illuminate\Http\Response
     */
    public function show(SOS $sOS)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\SOS  $sOS
     * @return \Illuminate\Http\Response
     */
    public function edit(SOS $sOS)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\SOS  $sOS
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SOS $sOS)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\SOS  $sOS
     * @return \Illuminate\Http\Response
     */
    public function destroy(SOS $sOS)
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
                'block_store' => 'required',
                'no_of_Shelves' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
