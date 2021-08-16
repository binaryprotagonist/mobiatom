<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\DistributionImport;
use App\Model\CustomerInfo;
use App\Model\Distribution;
use App\Model\DistributionCustomer;
use App\Model\DistributionDamageItem;
use App\Model\DistributionExpireItem;
use App\Model\DistributionModelStock;
use App\Model\DistributionModelStockDetails;
use App\Model\DistributionPostImage;
use App\Model\DistributionStock;
use App\Model\ImportTempFile;
use App\Model\ShareOfShelf;
use App\Model\Survey;
use App\Model\Item;
use App\Model\ItemUom;
use Illuminate\Http\Request;
use League\OAuth2\Server\RequestEvent;
use stdClass;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use File;
use URL;

class DistributionController extends Controller
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

        $distribution_query = Distribution::with(
            'distributionCustomer',
            'distributionCustomer.customer:id,firstname,lastname',
            'distributionCustomer.customer.customerInfo:id,user_id,customer_code'
        );

        if ($request->name) {
            $distribution_query->where('name', $request->name);
        }

        if ($request->date) {
            $distribution_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->start_date) {
            $distribution_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $distribution_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        $distribution = $distribution_query->orderBy('id', 'desc')
            ->get();

        $distribution_array = array();
        if (is_object($distribution)) {
            foreach ($distribution as $key => $distribution1) {
                $distribution_array[] = $distribution[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distribution_array[$offset])) {
                    $data_array[] = $distribution_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distribution_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($distribution_array);
        } else {
            $data_array = $distribution_array;
        }
        return prepareResult(true, $data_array, [], "Distribution listing", $this->success, $pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution", $this->unprocessableEntity);
        }

        if (is_array($request->customer) && sizeof($request->customer) < 1) {
            return prepareResult(false, [], "Please add atleast one customer.", "Error while validating distribution", $this->success);
        }

        \DB::beginTransaction();
        try {

            $distribution = new Distribution;
            $distribution->name = $request->name;
            $distribution->trip_id = $request->trip_id;
            $distribution->start_date = $request->start_date;
            $distribution->end_date = $request->end_date;
            $distribution->height = $request->height;
            $distribution->width = $request->width;
            $distribution->depth = $request->depth;
            $distribution->status = $request->status;
            $distribution->save();

            foreach ($request->customer as $customer) {
                $distribution_customer = new DistributionCustomer;
                $distribution_customer->customer_id = $customer;
                $distribution_customer->distribution_id = $distribution->id;
                $distribution_customer->save();
            }

            \DB::commit();

            $distribution->distributionCustomer;
            if (count($distribution->distributionCustomer)) {
                foreach ($distribution->distributionCustomer as $k => $customer) {
                    $distribution->distributionCustomer[$k]->customer = $customer->customer;
                }
            }

            return prepareResult(true, $distribution, [], "Distribution added successfully", $this->created);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating distribution", $this->unauthorized);
        }

        $distribution = Distribution::where('uuid', $uuid)
            ->with('distributionCustomer', 'distributionCustomer.customer:id,firstname,lastname')
            ->first();

        if (!is_object($distribution)) {
            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
        }

        return prepareResult(true, $distribution, [], "Distribution Edit", $this->success);
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
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution", $this->unprocessableEntity);
        }

        if (is_array($request->customer) && sizeof($request->customer) < 1) {
            return prepareResult(false, [], "Please add atleast one customer.", "Error while validating distribution", $this->success);
        }

        \DB::beginTransaction();
        try {

            $distribution = Distribution::where('uuid', $uuid)->first();
            DistributionCustomer::where('distribution_id', $distribution->id)->delete();

            $distribution->name = $request->name;
            $distribution->start_date = $request->start_date;
            $distribution->end_date = $request->end_date;
            $distribution->height = $request->height;
            $distribution->width = $request->width;
            $distribution->depth = $request->depth;
            $distribution->status = $request->status;
            $distribution->save();

            foreach ($request->customer as $customer) {
                $distribution_customer = new DistributionCustomer;
                $distribution_customer->customer_id = $customer;
                $distribution_customer->distribution_id = $distribution->id;
                $distribution_customer->save();
            }

            \DB::commit();

            $distribution->getSaveData();

            return prepareResult(true, $distribution, [], "Distribution updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating distribution", $this->unauthorized);
        }

        $distribution = Distribution::where('uuid', $uuid)
            ->first();

        if (is_object($distribution)) {
            DistributionCustomer::where('distribution_id', $distribution->id)->delete();
            DistributionDamageItem::where('distribution_id', $distribution->id)->delete();
            DistributionExpireItem::where('distribution_id', $distribution->id)->delete();
            DistributionModelStock::where('distribution_id', $distribution->id)->delete();
            DistributionPostImage::where('distribution_id', $distribution->id)->delete();
            DistributionStock::where('distribution_id', $distribution->id)->delete();
            $distribution->delete();
            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'name' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'height' => 'required',
                'width' => 'required',
                'depth' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "addPostImage") {
            $validator = \Validator::make($input, [
                'distribution_id' => 'required|integer|exists:distributions,id',
                'customer_id' => 'required|integer|exists:users,id',
                'salesman_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "expireitems") {
            $validator = \Validator::make($input, [
                'distribution_id' => 'required|integer|exists:distributions,id',
                'customer_id' => 'required|integer|exists:users,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'item_id' => 'required|integer|exists:items,id',
                'item_uom_id' => 'required|integer|exists:item_uoms,id',
                'qty' => 'required',
                'expiry_date' => 'required|date'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "damageitems") {
            $validator = \Validator::make($input, [
                'distribution_id' => 'required|integer|exists:distributions,id',
                'customer_id' => 'required|integer|exists:users,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'item_id' => 'required|integer|exists:items,id',
                'item_uom_id' => 'required|integer|exists:item_uoms,id',
                'damage_item_qty' => 'required',
                'expire_item_qty' => 'required',
                'saleable_item_qty' => 'required',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "stockitems") {
            $validator = \Validator::make($input, [
                'distribution_id' => 'required|integer|exists:distributions,id',
                'customer_id' => 'required|integer|exists:users,id',
                'salesman_id' => 'required|integer|exists:users,id',
                'item_id' => 'required|integer|exists:items,id',
                'item_uom_id' => 'required|integer|exists:item_uoms,id',
                'stock' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "allInOne") {
            $validator = \Validator::make($input, [
                'distribution_id' => 'required|integer|exists:distributions,id',
                'customer_id' => 'required|integer|exists:users,id',
                'salesman_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function storeExpireItems(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "expireitems");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution expire items", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $distribution_expire_items = new DistributionExpireItem;
            $distribution_expire_items->distribution_id = $request->distribution_id;
            $distribution_expire_items->customer_id = $request->customer_id;
            $distribution_expire_items->salesman_id = $request->salesman_id;
            $distribution_expire_items->item_id = $request->item_id;
            $distribution_expire_items->item_uom_id = $request->item_uom_id;
            $distribution_expire_items->qty = $request->qty;
            $distribution_expire_items->expiry_date = $request->expiry_date;
            $distribution_expire_items->save();

            \DB::commit();

            return prepareResult(true, $distribution_expire_items, [], "Distribution expire items added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function expireItemsList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->distribution_id) {
            return prepareResult(false, [], [], "Error while validating expire item", $this->unprocessableEntity);
        }

        $distribution_id = $request->distribution_id;

        $distribution_expire_item_query = DistributionExpireItem::with(
            'item:id,item_name,item_code',
            'itemUom:id,name',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'distribution'
        )
            ->where('distribution_id', $distribution_id);
        if ($request->date) {
            $distribution_expire_item_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $distribution_expire_item_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distribution_expire_item_query->whereHas('salesman', function ($q) use ($n) {
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
                $distribution_expire_item_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distribution_expire_item_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $distribution_expire_item_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->item_name) {
            $item_name = $request->item_name;
            $distribution_expire_item_query->whereHas('item', function ($q) use ($item_name) {
                $q->where('item_name', $item_name);
            });
        }

        if ($request->item_code) {
            $code = $request->item_code;
            $distribution_expire_item_query->whereHas('item', function ($q) use ($code) {
                $q->where('item_code', $code);
            });
        }

        if ($request->distribution_name) {
            $distribution_name = $request->distribution_name;
            $distribution_expire_item_query->whereHas('distribution', function ($q) use ($distribution_name) {
                $q->where('name', $distribution_name);
            });
        }

        if ($request->all) {
            $distribution_expire_items = $distribution_expire_item_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $distribution_expire_item_query->whereDate('created_at', date('Y-m-d'));
            }
            $distribution_expire_items = $distribution_expire_item_query->orderBy('id', 'desc')->get();
        }

        $distribution_expire_items_array = array();
        if (is_object($distribution_expire_items)) {
            foreach ($distribution_expire_items as $key => $distribution_expire_items1) {
                $distribution_expire_items_array[] = $distribution_expire_items[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distribution_expire_items_array[$offset])) {
                    $data_array[] = $distribution_expire_items_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distribution_expire_items_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($distribution_expire_items_array);
        } else {
            $data_array = $distribution_expire_items_array;
        }

        return prepareResult(true, $data_array, [], "Distribution Expire Items listing", $this->success, $pagination);
    }

    public function storeDamageItems(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "damageitems");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution damage.", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $distribution_damage_items = new DistributionDamageItem;
            $distribution_damage_items->distribution_id = $request->distribution_id;
            $distribution_damage_items->customer_id = $request->customer_id;
            $distribution_damage_items->salesman_id = $request->salesman_id;
            $distribution_damage_items->item_id = $request->item_id;
            $distribution_damage_items->item_uom_id = $request->item_uom_id;
            $distribution_damage_items->damage_item_qty = $request->damage_item_qty;
            $distribution_damage_items->expire_item_qty = $request->expire_item_qty;
            $distribution_damage_items->saleable_item_qty = $request->saleable_item_qty;
            $distribution_damage_items->save();

            \DB::commit();

            return prepareResult(true, $distribution_damage_items, [], "Distribution damage items added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function damageItemsList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->distribution_id) {
            return prepareResult(false, [], [], "Error while validating damage item", $this->unprocessableEntity);
        }

        $distribution_id = $request->distribution_id;

        $distribution_damage_item_query = DistributionDamageItem::with(
            'item:id,item_name,item_code',
            'itemUom:id,name',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'distribution'
        )
            ->where('distribution_id', $distribution_id);
        if ($request->date) {
            $distribution_damage_item_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $distribution_damage_item_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distribution_damage_item_query->whereHas('salesman', function ($q) use ($n) {
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
                $distribution_damage_item_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distribution_damage_item_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $distribution_damage_item_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->item_name) {
            $item_name = $request->item_name;
            $distribution_damage_item_query->whereHas('item', function ($q) use ($item_name) {
                $q->where('item_name', $item_name);
            });
        }

        if ($request->item_code) {
            $code = $request->item_code;
            $distribution_damage_item_query->whereHas('item', function ($q) use ($code) {
                $q->where('item_code', $code);
            });
        }

        if ($request->distribution_name) {
            $distribution_name = $request->distribution_name;
            $distribution_damage_item_query->whereHas('distribution', function ($q) use ($distribution_name) {
                $q->where('name', $distribution_name);
            });
        }

        if ($request->all) {
            $distribution_damage_items = $distribution_damage_item_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $distribution_damage_item_query->whereDate('created_at', date('Y-m-d'));
            }
            $distribution_damage_items = $distribution_damage_item_query->orderBy('id', 'desc')->get();
        }

        $distribution_damage_items_array = array();
        if (is_object($distribution_damage_items)) {
            foreach ($distribution_damage_items as $key => $distribution_damage_items1) {
                $distribution_damage_items_array[] = $distribution_damage_items[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distribution_damage_items_array[$offset])) {
                    $data_array[] = $distribution_damage_items_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distribution_damage_items_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($distribution_damage_items_array);
        } else {
            $data_array = $distribution_damage_items_array;
        }

        return prepareResult(true, $data_array, [], "Distribution Expire Items listing", $this->success, $pagination);
    }

    public function storeStockItems(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "stockitems");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution stock.", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {

            $distribution_stock = new DistributionStock;
            $distribution_stock->distribution_id = $request->distribution_id;
            $distribution_stock->customer_id = $request->customer_id;
            $distribution_stock->salesman_id = $request->salesman_id;
            $distribution_stock->item_id = $request->item_id;
            $distribution_stock->item_uom_id = $request->item_uom_id;
            $distribution_stock->stock = $request->stock;
            $distribution_stock->is_out_of_stock = $request->is_out_of_stock;
            $distribution_stock->save();

            \DB::commit();

            $distribution_stock->getSaveData();

            return prepareResult(true, $distribution_stock, [], "Distribution stock added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function stockItemsList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->distribution_id) {
            return prepareResult(false, [], [], "Error while validating stock item", $this->unprocessableEntity);
        }

        $distribution_id = $request->distribution_id;

        $distribution_stock_query = DistributionStock::with(
            'item:id,item_name,item_code',
            'itemUom:id,name,code',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'distribution'
        )
            ->where('distribution_id', $distribution_id);

        if ($request->date) {
            $distribution_stock_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $distribution_stock_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distribution_stock_query->whereHas('salesman', function ($q) use ($n) {
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
                $distribution_stock_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distribution_stock_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $distribution_stock_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->item_name) {
            $item_name = $request->item_name;
            $distribution_stock_query->whereHas('item', function ($q) use ($item_name) {
                $q->where('item_name', $item_name);
            });
        }

        if ($request->item_code) {
            $code = $request->item_code;
            $distribution_stock_query->whereHas('item', function ($q) use ($code) {
                $q->where('item_code', $code);
            });
        }

        if ($request->all) {
            $distribution_stock = $distribution_stock_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $distribution_stock_query->whereDate('created_at', date('Y-m-d'));
            }
            $distribution_stock = $distribution_stock_query->orderBy('id', 'desc')->get();
        }

        $distribution_stock_array = array();
        if (is_object($distribution_stock)) {
            foreach ($distribution_stock as $key => $distribution_stock1) {
                $distribution_stock_array[] = $distribution_stock[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distribution_stock_array[$offset])) {
                    $data_array[] = $distribution_stock_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distribution_stock_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($distribution_stock_array);
        } else {
            $data_array = $distribution_stock_array;
        }

        return prepareResult(true, $data_array, [], "Distribution Expire Items listing", $this->success, $pagination);
    }

    public function distributionSurveyList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->distribution_id) {
            return prepareResult(false, [], [], "Error while validating survey", $this->unprocessableEntity);
        }

        $distribution_id = $request->distribution_id;

        $survey_query = Survey::with(
            'distribution',
            'distribution.distributionModelStock',
            'surveyType:id,survey_name'
        )
            ->where('distribution_id', $distribution_id);

        if ($request->date) {
            $survey_query->where('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->end_date) {
            $survey_query->where('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        if ($request->start_date) {
            $survey_query->where('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->name) {
            $survey_query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->all) {
            $survey = $survey_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $survey_query->whereDate('created_at', date('Y-m-d'));
            }
            $survey = $survey_query->orderBy('id', 'desc')->get();
        }

        $survey_array = array();
        if (is_object($survey)) {
            foreach ($survey as $key => $survey1) {
                $survey_array[] = $survey[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($survey_array[$offset])) {
                    $data_array[] = $survey_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($survey_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($survey_array);
        } else {
            $data_array = $survey_array;
        }

        return prepareResult(true, $data_array, [], "Survey Distribution listing", $this->success, $pagination);
    }

    public function storeAllInOneItems(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }


        $input = $request->json()->all();
        $validate = $this->validations($input, "allInOne");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution stock.", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            foreach ($request->items as $item) {

                $distribution_stock = new DistributionStock;
                $distribution_stock->distribution_id = $request->distribution_id;
                $distribution_stock->customer_id = $request->customer_id;
                $distribution_stock->salesman_id = $request->salesman_id;
                $distribution_stock->trip_id = $request->trip_id;
                $distribution_stock->item_id = $item['item_id'];
                $distribution_stock->item_uom_id = $item['item_uom_id'];
                $distribution_stock->stock = $item['stock'];
                $distribution_stock->capacity = $item['capacity'];
                $distribution_stock->is_out_of_stock = $item['is_out_of_stock'] ? 1 : 0;
                $distribution_stock->save();

                if ($distribution_stock->capacity) {
                    $customer_id = $distribution_stock->customer_id;
                    $model_stock = DistributionModelStockDetails::with('distributionModelStock')
                        ->where('item_id', $distribution_stock->item_id)
                        ->where('item_uom_id', $item['item_uom_id'])
                        ->whereHas('distributionModelStock', function ($q) use ($customer_id) {
                            $q->where('customer_id', $customer_id);
                        })
                        ->first();

                    $model_stock->capacity = $distribution_stock->capacity;
                    $model_stock->save();
                }

                if (is_array($item['expiry']) && sizeof($item['expiry']) >= 1) {
                    foreach ($item['expiry'] as $expiry) {
                        $distribution_expire_items = new DistributionExpireItem;
                        $distribution_expire_items->distribution_id = $request->distribution_id;
                        $distribution_expire_items->customer_id = $request->customer_id;
                        $distribution_expire_items->salesman_id = $request->salesman_id;
                        $distribution_expire_items->item_id = $expiry['item_id'];
                        $distribution_expire_items->item_uom_id = $expiry['item_uom_id'];
                        $distribution_expire_items->qty = $expiry['qty'];
                        $distribution_expire_items->expiry_date = $expiry['expiry_date'];
                        $distribution_expire_items->save();
                    }
                }

                if (is_array($item['damage']) && sizeof($item['damage']) >= 1) {
                    foreach ($item['damage'] as $damage) {
                        $distribution_damage_items = new DistributionDamageItem;
                        $distribution_damage_items->distribution_id = $request->distribution_id;
                        $distribution_damage_items->customer_id = $request->customer_id;
                        $distribution_damage_items->salesman_id = $request->salesman_id;
                        $distribution_damage_items->item_id = $damage['item_id'];
                        $distribution_damage_items->item_uom_id = $damage['item_uom_id'];
                        $distribution_damage_items->damage_item_qty = $damage['damage_item_qty'];
                        $distribution_damage_items->expire_item_qty = $damage['expire_item_qty'];
                        $distribution_damage_items->saleable_item_qty = $damage['saleable_item_qty'];
                        $distribution_damage_items->save();
                    }
                }

                if (is_array($item['share_of_shelf']) && sizeof($item['share_of_shelf']) >= 1) {
                    foreach ($item['share_of_shelf'] as $sos) {
                        $share_of_shelf = new ShareOfShelf;
                        $share_of_shelf->distribution_id = $request->distribution_id;
                        $share_of_shelf->customer_id = $request->customer_id;
                        $share_of_shelf->salesman_id = $request->salesman_id;
                        $share_of_shelf->item_id = $sos['item_id'];
                        $share_of_shelf->item_uom_id = $sos['item_uom_id'];
                        $share_of_shelf->total_number_of_facing = $sos['total_number_of_facing'];
                        $share_of_shelf->actual_number_of_facing = $sos['actual_number_of_facing'];
                        $share_of_shelf->score = $sos['score'];
                        $share_of_shelf->save();

                        // $distribution_model_stock = DistributionModelStockDetails::where('distribution_id', $request->distribution_id)
                        //     ->where('item_id', $sos['item_id'])
                        //     ->where('item_uom_id', $sos['item_uom_id'])
                        //     ->first();

                        // if (is_object($distribution_model_stock)) {
                        //     if ($distribution_model_stock->total_number_of_facing == 0) {
                        //         $distribution_model_stock->total_number_of_facing = $share_of_shelf->total_number_of_facing;
                        //         $distribution_model_stock->save();
                        //     }
                        // }
                    }
                }
            }


            \DB::commit();

            return prepareResult(true, $distribution_stock, [], "Distribution stock added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function imports(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'distribution_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate distribution import", $this->unauthorized);
        }
        $errors = array();
        try {
            $file = request()->file('distribution_file')->store('import');
            $import = new DistributionImport($request->skipduplicate);
            $import->import($file);
            if (count($import->failures()) > 16) {
                $errors[] = $import->failures();
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                info($failure->row());
                info($failure->attribute());
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
                $errors[] = $failure->errors();
            }

            return prepareResult(true, [], $errors, "Failed to validate distribution import", $this->success);
        }
        return prepareResult(true, [], $errors, "Distribution successfully imported", $this->success);
    }

    public function storePostImage(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "addPostImage");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating distribution", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $dpi = new DistributionPostImage;
            $dpi->distribution_id = $request->distribution_id;
            $dpi->salesman_id = $request->salesman_id;
            $dpi->customer_id = $request->customer_id;

            if ($request->image1) {
                $saveImage1 = saveImage("dpi_" . Str::slug(rand(100000000000, 99999999999999)), $request->image1, "distribution-post-image");
                $dpi->image1 = $saveImage1;
            }
            if ($request->image2) {
                $saveImage1 = saveImage("dpi_" . Str::slug(rand(100000000000, 99999999999999)), $request->image2, "distribution-post-image");
                $dpi->image2 = $saveImage2;
            }
            if ($request->image3) {
                $saveImage3 = saveImage("dpi_" . Str::slug(rand(100000000000, 99999999999999)), $request->image3, "distribution-post-image");
                $dpi->image3 = $saveImage3;
            }
            if ($request->image4) {
                $saveImage4 = saveImage("dpi_" . Str::slug(rand(100000000000, 99999999999999)), $request->image3, "distribution-post-image");
                $dpi->image4 = $saveImage4;
            }

            // $dpi->image2 = $this->saveImage($request->image2);
            // $dpi->image3 = $this->saveImage($request->image3);
            // $dpi->image4 = $this->saveImage($request->image4);
            $dpi->save();

            \DB::commit();

            return prepareResult(true, $dpi, [], "Distribution post image added successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexPostImage(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->distribution_id) {
            return prepareResult(false, [], [], "Error while validating distribution post image", $this->unprocessableEntity);
        }

        $distribution_id = $request->distribution_id;

        $distributions_query = DistributionPostImage::with(
            'distribution:id,name',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code'
        )
            ->where('distribution_id', $distribution_id);

        if ($request->date) {
            $distributions_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $distributions_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distributions_query->whereHas('salesman', function ($q) use ($n) {
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
                $distributions_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $distributions_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $distributions_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        $distributions = $distributions_query->orderBy('id', 'desc')
            ->get();

        $distributions_array = array();
        if (is_object($distributions)) {
            foreach ($distributions as $key => $distributions1) {
                $distributions_array[] = $distributions[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($distributions_array[$offset])) {
                    $data_array[] = $distributions_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($distributions_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($distributions_array);
        } else {
            $data_array = $distributions_array;
        }

        return prepareResult(true, $data_array, [], "Distribution post image listing", $this->success, $pagination);
    }

    // private function saveImage($image)
    // {
    //     if ($image) {
    //
    //         $destinationPath = 'uploads/distribution-post-image/';
    //         $image_name = \Str::slug(rand(100000000000, 99999999999999));
    //         $image = $image;
    //         $getBaseType = explode(',', $image);
    //         $getExt = explode(';', $image);
    //         $image = str_replace($getBaseType[0] . ',', '', $image);
    //         $image = str_replace(' ', '+', $image);
    //         $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
    //         \File::put($destinationPath . $fileName, base64_decode($image));
    //         $url = URL('/') . '/' . $destinationPath . $fileName;
    //         return $url;
    //     } else {
    //         return NULL;
    //     }
    // }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        // {"Name": "Name","Start date": "Start date","End date": "End date","Height": "Height","Width": "Width","Depth": "Depth","Status": "Status","Customer code": "Customer code","Item code": "Item code","Item uom": "Item uom","Capacity": "Capacity","Total no of facing": "Total no of facing"}

        $mappingarray = array("Name", "Start date", "End date", "Height", "Width", "Depth", "Status", "Customer code", "Item", "Uom", "Capacity");

        return prepareResult(true, $mappingarray, [], "Distribution Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'distribution_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate distribution import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('distribution_file')->store('import');
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

            $import = new DistributionImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);
            // dd($import);
            // print_r($import);
            // exit;
            $succussrecords = 0;
            $successfileids = 0;
            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());
                $data = json_encode($import->successAllRecords());
                $fileName = time() . '_datafile.txt';
                \File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();
                $successfileids = $importtempfiles->id;
            }
            $errorrecords = 0;
            $errror_array = array();
            if ($import->failures()) {

                foreach ($import->failures() as $failure_key => $failure) {
                    //echo $failure_key.'--------'.$failure->row().'||';
                    //print_r($failure);
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
            $result['skipduplicate'] = $request->skipduplicate;
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
        return prepareResult(true, $result, $errors, "distribution successfully imported", $this->success);
    }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);
            $skipduplicate = $request->skipduplicate;
            if ($finaldata) :
                foreach ($finaldata as $row) :
                    $current_organisation_id = request()->user()->organisation_id;

                    $customer = CustomerInfo::where('customer_code', $row[7])->first();

                    $distribution = Distribution::where('name', $row[0])
                        ->whereDate('start_date', date('Y-m-d', strtotime($row[1])))
                        ->whereDate('end_date', date('Y-m-d', strtotime($row[2])))
                        ->first();

                    $item = Item::where('item_code', $row[8])->first();
                    $item_uom = ItemUom::where('name', $row[9])->first();

                    if ((!isset($item->id) && $item->id) && (!isset($item_uom->id) && $item_uom->id) && (!$customer->id) && $customer->id) {
                        continue;
                    }

                    if ($skipduplicate) {
                        if (is_object($distribution)) {
                            $distribution_customers = DistributionCustomer::where('distribution_id', $distribution->id)
                                ->where('customer_id', $customer->user_id)
                                ->first();

                            // not find distribustion customer create new
                            if (!is_object($distribution_customers)) {
                                $distribution_customers = new DistributionCustomer;
                            }

                            if (isset($distribution_customers->id) && $distribution_customers->id) {
                                $distribution_model_stock_details = DistributionModelStockDetails::where('distribution_id', $distribution->id)
                                    ->where('item_id', $row[8])
                                    ->where('item_uom_id', $row[9])
                                    ->first();
                                if (is_object($distribution_model_stock_details)) {
                                    continue;
                                } else {
                                    $distribution_model_stock_details = new DistributionModelStockDetails;
                                }
                            } else {
                                $distribution_model_stock_details = new DistributionModelStockDetails;
                            }
                        } else {
                            $distribution = new Distribution;
                            $distribution_customers = new DistributionCustomer;
                            $distribution_model_stock_details = new DistributionModelStockDetails;
                        }

                        $distribution->name = $row[0];
                        $distribution->start_date  = date('Y-m-d', strtotime($row[1]));
                        $distribution->end_date = date('Y-m-d', strtotime($row[2]));
                        $distribution->height = $row[3];
                        $distribution->width = $row[4];
                        $distribution->depth = $row[5];
                        if (isset($row[6]) && $row[6] == "Yes") {
                            $distribution->status = 1;
                        } else {
                            $distribution->status = 0;
                        }
                        $distribution->save();

                        $distribution_customers->distribution_id = $distribution->id;
                        $distribution_customers->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $distribution_customers->save();

                        $distribution_model_stocks = DistributionModelStock::where('distribution_id', $distribution->id)
                            ->where('customer_id', $customer->user_id)
                            ->first();

                        if (!is_object($distribution_model_stocks)) {
                            if (!isset($distribution_model_stocks->id)) {
                                $distribution_model_stocks = new DistributionModelStock;
                                $distribution_model_stocks->distribution_id = $distribution->id;
                                $distribution_model_stocks->customer_id = $customer->user_id;
                                $distribution_model_stocks->save();
                                $distribution_model_stock_details = new DistributionModelStockDetails;
                            }
                        } else {
                            $distribution_model_stock_details = new DistributionModelStockDetails;
                        }

                        $distribution_model_stock_details->distribution_model_stock_id = $distribution_model_stocks->id;
                        $distribution_model_stock_details->distribution_id = $distribution->id;
                        $distribution_model_stock_details->item_id = (is_object($item)) ? $item->id : null;
                        $distribution_model_stock_details->item_uom_id = (is_object($item_uom)) ? $item_uom->id : null;
                        $distribution_model_stock_details->capacity = $row[10];
                        $distribution_model_stock_details->total_number_of_facing = $row[11];
                        $distribution_model_stock_details->save();
                    } else {
                        if (is_object($distribution)) {
                            $distribution_customers = DistributionCustomer::where('distribution_id', $distribution->id)
                                ->where('customer_id', $customer->user_id)
                                ->first();

                            // not find distribustion customer create new
                            if (!is_object($distribution_customers)) {
                                $distribution_customers = new DistributionCustomer;
                            }

                            if (isset($distribution_customers->id) && $distribution_customers->id) {
                                $distribution_model_stock_details = DistributionModelStockDetails::where('distribution_id', $distribution->id)
                                    ->where('item_id', $row[8])
                                    ->where('item_uom_id', $row[9])
                                    ->first();
                                if (!is_object($distribution_model_stock_details)) {
                                    $distribution_model_stock_details = new DistributionModelStockDetails;
                                }
                            } else {
                                $distribution_model_stock_details = new DistributionModelStockDetails;
                            }
                        } else {
                            $distribution = new Distribution;
                            $distribution_customers = new DistributionCustomer;
                            $distribution_model_stock_details = new DistributionModelStockDetails;
                        }

                        $distribution->name = $row[0];
                        $distribution->start_date  = date('Y-m-d', strtotime($row[1]));
                        $distribution->end_date = date('Y-m-d', strtotime($row[2]));
                        $distribution->height = $row[3];
                        $distribution->width = $row[4];
                        $distribution->depth = $row[5];
                        if (isset($row[6]) && $row[6] == "Yes") {
                            $distribution->status = 1;
                        } else {
                            $distribution->status = 0;
                        }
                        $distribution->save();

                        $distribution_customers->distribution_id = $distribution->id;
                        $distribution_customers->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $distribution_customers->save();

                        $distribution_model_stocks = DistributionModelStock::where('distribution_id', $distribution->id)
                            ->where('customer_id', $customer->user_id)
                            ->first();

                        if (!is_object($distribution_model_stocks)) {
                            if (!isset($distribution_model_stocks->id)) {
                                $distribution_model_stocks = new DistributionModelStock;
                                $distribution_model_stocks->distribution_id = $distribution->id;
                                $distribution_model_stocks->customer_id = $customer->user_id;
                                $distribution_model_stocks->save();
                                $distribution_model_stock_details = new DistributionModelStockDetails;
                            }
                        } else {
                            $distribution_model_stock_details = new DistributionModelStockDetails;
                        }

                        $distribution_model_stock_details->distribution_model_stock_id = $distribution_model_stocks->id;
                        $distribution_model_stock_details->distribution_id = $distribution->id;
                        $distribution_model_stock_details->item_id = (is_object($item)) ? $item->id : null;
                        $distribution_model_stock_details->item_uom_id = (is_object($item_uom)) ? $item_uom->id : null;
                        $distribution_model_stock_details->capacity = $row[10];
                        $distribution_model_stock_details->total_number_of_facing = $row[11];
                        $distribution_model_stock_details->save();
                    }


                // if (is_object($distribution)) {
                //     $distribution->name = $row[0];
                //     $distribution->start_date  = date('Y-m-d', strtotime($row[1]));
                //     $distribution->end_date = date('Y-m-d', strtotime($row[2]));
                //     $distribution->height = $row[3];
                //     $distribution->width = $row[4];
                //     $distribution->depth = $row[5];
                //     $distribution->status = $row[6];
                //     $distribution->save();

                //     $distribution_customer = DistributionCustomer::where('distribution_id', $distribution->id)
                //         ->where('customer_id', $customer->user_id)
                //         ->first();
                //     if (!is_object($distribution_customer)) {
                //         $distribution_customer = new DistributionCustomer;
                //     }
                //     $distribution_customer->distribution_id = $distribution->id;
                //     $distribution_customer->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                //     $distribution_customer->save();

                //     $distribution_model_stocks = DistributionModelStock::where('customer_id', $customer->user_id)
                //         ->where('distribution_id', $distribution->id)
                //         ->first();
                //     if (!is_object($distribution_model_stocks)) {
                //         $distribution_model_stocks = new DistributionModelStock;
                //     }

                //     $distribution_model_stocks->customer_id = (is_object($customer)) ? $customer->user_id : NULL;
                //     $distribution_model_stocks->distribution_id = $distribution->id;
                //     $distribution_model_stocks->save();

                //     $distribution_model_stock_detail = DistributionModelStockDetails::where('distribution_model_stock_id', $distribution_model_stocks->id)
                //         ->where('distribution_id', $distribution->id)
                //         ->where('item_id', $item->id)
                //         ->where('item_uom_id', $item_uom->id)
                //         ->first();

                //     if (!is_object($distribution_model_stock_detail)) {
                //         $distribution_model_stock_detail = new DistributionModelStockDetails;
                //     }

                //     $distribution_model_stock_detail->distribution_model_stock_id = $distribution_model_stocks->id;
                //     $distribution_model_stock_detail->distribution_id = $distribution->id;
                //     $distribution_model_stock_detail->item_id = (is_object($item)) ? $item->id : 0;
                //     $distribution_model_stock_detail->item_uom_id = (is_object($item_uom)) ? $item_uom->id : 0;
                //     $distribution_model_stock_detail->capacity = $row[10];
                //     $distribution_model_stock_detail->save();
                // } else {

                //     if (!is_object($customer)) {
                //         return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                //     }

                //     $distribution = new Distribution;
                //     $distribution->organisation_id = $current_organisation_id;
                //     $distribution->name = $row[0];
                //     $distribution->start_date  = date('Y-m-d', strtotime($row[1]));
                //     $distribution->end_date = date('Y-m-d', strtotime($row[2]));
                //     $distribution->height = $row[3];
                //     $distribution->width = $row[4];
                //     $distribution->depth = $row[5];
                //     $distribution->status = $row[6];
                //     $distribution->save();

                //     $distribution_customer = new DistributionCustomer;
                //     $distribution_customer->distribution_id = $distribution->id;
                //     $distribution_customer->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                //     $distribution_customer->save();

                //     $distribution_model_stocks = DistributionModelStock::where('customer_id', $customer->user_id)
                //         ->where('distribution_id', $distribution->id)
                //         ->first();

                //     if (!is_object($distribution_model_stocks)) {
                //         $distribution_model_stocks = new DistributionModelStock;
                //     }

                //     $distribution_model_stocks->customer_id = (is_object($customer)) ? $customer->user_id : NULL;
                //     $distribution_model_stocks->distribution_id = $distribution->id;
                //     $distribution_model_stocks->save();

                //     $item = Item::where('item_code', $row[8])->first();
                //     $item_uom = ItemUom::where('name', $row[9])->first();

                //     $distribution_model_stock_detail = DistributionModelStockDetails::where('distribution_model_stock_id', $distribution_model_stocks->id)
                //         ->where('distribution_id', $distribution->id)
                //         ->where('item_id', $item->id)
                //         ->where('item_uom_id', $item_uom->id)
                //         ->first();

                //     if (!is_object($distribution_model_stock_detail)) {
                //         $distribution_model_stock_detail = new DistributionModelStockDetails;
                //     }
                //     $distribution_model_stock_detail->distribution_model_stock_id = $distribution_model_stocks->id;
                //     $distribution_model_stock_detail->distribution_id = $distribution->id;
                //     $distribution_model_stock_detail->item_id = (is_object($item)) ? $item->id : 0;
                //     $distribution_model_stock_detail->item_uom_id = (is_object($item_uom)) ? $item_uom->id : 0;
                //     $distribution_model_stock_detail->capacity = $row[10];
                //     $distribution_model_stock_detail->save();
                // }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "Distribution successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }
}
