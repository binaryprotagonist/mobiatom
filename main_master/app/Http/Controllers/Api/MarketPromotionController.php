<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\MarketPromotion;
use Illuminate\Http\Request;

class MarketPromotionController extends Controller
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

        $market_promotion_query = MarketPromotion::with(
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'item:id,item_name,item_code'
        );

        if ($request->date) {
            $market_promotion_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $name = $request->salesman_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $market_promotion_query->whereHas('salesman', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                    ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $market_promotion_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                        ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->salesman_code) {
            $salesmanCode = $request->salesman_code;
            $market_promotion_query->whereHas('salesman.salesmanInfo', function ($q) use ($salesmanCode) {
                $q->where('salesman_code', $salesmanCode);
            });
        }
        
        if ($request->customer_name) {
            $name = $request->customer_name;
            $exploded_name = explode(" ", $name);
            if (count($exploded_name) < 2) {
                $market_promotion_query->whereHas('customer', function ($q) use ($name) {
                    $q->where('firstname', 'like', '%' . $name . '%')
                    ->orWhere('lastname', 'like', '%' . $name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $market_promotion_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                        ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }
        
        if ($request->customer_code) {
            $customerCode = $request->customer_code;
            $market_promotion_query->whereHas('customer.customerInfo', function ($q) use ($customerCode) {
                $q->where('customer_code', $customerCode);
            });
        }
        
        if ($request->item) {
            $item = $request->item;
            $market_promotion_query->whereHas('item', function ($q) use ($item) {
                $q->where('item_name', $item);
            });
        }
        
        if ($request->item_code) {
            $item_code = $request->item_code;
            $market_promotion_query->whereHas('item', function ($q) use ($item_code) {
                $q->where('item_code', $item_code);
            });
        }

        if ($request->start_date) {
            $market_promotion_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $market_promotion_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        $market_promotion = $market_promotion_query->orderBy('id', 'desc')
        ->get();

        $market_promotion_array = array();
        if (is_object($market_promotion)) {
            foreach ($market_promotion as $key => $market_promotion1) {
                $market_promotion_array[] = $market_promotion[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($market_promotion_array[$offset])) {
                    $data_array[] = $market_promotion_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($market_promotion_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($market_promotion_array);
        } else {
            $data_array = $market_promotion_array;
        }

        return prepareResult(true, $data_array, [], "Market promotion listing", $this->success, $pagination);
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

            $market_promotion = new MarketPromotion;
            $market_promotion->salesman_id = $request->salesman_id;
            $market_promotion->customer_id = $request->customer_id;
            $market_promotion->item_id = $request->item_id;
            $market_promotion->start_date = $request->start_date;
            $market_promotion->end_date = $request->end_date;
            $market_promotion->type = $request->type;
            $market_promotion->desctription = $request->desctription;
            $market_promotion->qty = $request->qty;
            $market_promotion->added_on = $request->added_on;
            if ($request->image) {
                $market_promotion->image = saveImage($request->type, $request->image, 'market_promotion');
            }
            $market_promotion->save();

            \DB::commit();

            return prepareResult(true, $market_promotion, [], "Market Promotion successfully", $this->created);
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
     * @param  \App\Model\MarketPromotion  $marketPromotion
     * @return \Illuminate\Http\Response
     */
    public function edit(MarketPromotion $marketPromotion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\MarketPromotion  $marketPromotion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MarketPromotion $marketPromotion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\MarketPromotion  $marketPromotion
     * @return \Illuminate\Http\Response
     */
    public function destroy(MarketPromotion $marketPromotion)
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
                'item_id' => 'required|integer|exists:items,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'type' => 'required',
                'qty' => 'required'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error, "errors" => $errors];
    }
}
