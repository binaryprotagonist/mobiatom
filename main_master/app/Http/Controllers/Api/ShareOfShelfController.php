<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\DistributionModelStock;
use App\Model\ShareOfShelf;
use Illuminate\Http\Request;

class ShareOfShelfController extends Controller
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

        if (!$request->distribution_id) {
            return prepareResult(false, [], [], "Error while validating SOS", $this->unprocessableEntity);
        }

        $distribution_id = $request->distribution_id;

        $share_of_shelf_query = ShareOfShelf::with(
            'item:id,item_name,item_code',
            'itemUom:id,name',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'distribution'
        )
            ->where('distribution_id', $distribution_id);
        if ($request->date) {
            $share_of_shelf_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $share_of_shelf_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $share_of_shelf_query->whereHas('salesman', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }
        if ($request->customer_name) {
            $customer_name = $request->customer_name;
            $exploded_name = explode(" ", $customer_name);
            if (count($exploded_name) < 2) {
                $share_of_shelf_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $share_of_shelf_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $share_of_shelf_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->item_name) {
            $item_name = $request->item_name;
            $share_of_shelf_query->whereHas('item', function ($q) use ($item_name) {
                $q->where('item_name', $item_name);
            });
        }

        if ($request->item_code) {
            $code = $request->item_code;
            $share_of_shelf_query->whereHas('item', function ($q) use ($code) {
                $q->where('item_code', $code);
            });
        }

        if ($request->all) {
            $share_of_shelfs = $share_of_shelf_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $share_of_shelf_query->whereDate('created_at', date('Y-m-d'));
            }
            $share_of_shelfs = $share_of_shelf_query->orderBy('id', 'desc')->get();
        }

        $share_of_shelfs_array = array();
        if (is_object($share_of_shelfs)) {
            foreach ($share_of_shelfs as $key => $share_of_shelfs1) {
                $share_of_shelfs_array[] = $share_of_shelfs[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($share_of_shelfs_array[$offset])) {
                    $data_array[] = $share_of_shelfs_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($share_of_shelfs_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($share_of_shelfs_array);
        } else {
            $data_array = $share_of_shelfs_array;
        }

        return prepareResult(true, $data_array, [], "Share of shelf listing", $this->success, $pagination);
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
        $validate = $this->validations($input, "sosAdd");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating share of shelf.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $share_of_shelf = new ShareOfShelf;
            $share_of_shelf->distribution_id = $request->distribution_id;
            $share_of_shelf->customer_id = $request->customer_id;
            $share_of_shelf->salesman_id = $request->salesman_id;
            $share_of_shelf->item_id = $request->item_id;
            $share_of_shelf->item_uom_id = $request->item_uom_id;
            $share_of_shelf->total_number_of_facing = $request->total_number_of_facing;
            $share_of_shelf->actual_number_of_facing = $request->actual_number_of_facing;
            $share_of_shelf->score = $request->score;
            $share_of_shelf->save();

            $distribution_model_stock = DistributionModelStock::where('distribution_id', $request->distribution_id)
                // ->where('item_id', $request->item_id)
                // ->where('item_uom_id', $request->item_uom_id)
                ->first();

            if (is_object($distribution_model_stock)) {
                if ($distribution_model_stock->total_number_of_facing == 0) {
                    $distribution_model_stock->total_number_of_facing = $share_of_shelf->total_number_of_facing;
                    $distribution_model_stock->save();
                }
            }

            \DB::commit();

            return prepareResult(true, $share_of_shelf, [], "Share of shelf added successfully", $this->created);
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating share of shelf.", $this->unauthorized);
        }

        $share_of_shelf = ShareOfShelf::with(
            'item:id,item_name,item_code',
            'itemUom:id,name',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'distribution'
        )
            ->where('uuid', $uuid)
            ->first();

        if (!is_object($share_of_shelf)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unprocessableEntity);
        }

        return prepareResult(true, $share_of_shelf, [], "Share of shelf Edit", $this->success);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'sosAdd' => 'required|integer|exists:distributions,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }


        return ["error" => $error, "errors" => $errors];
    }
}
