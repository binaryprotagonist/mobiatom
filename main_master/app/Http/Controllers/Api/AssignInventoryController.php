<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\AssignInventoryImport;
use App\Model\AssignInventory;
use App\Model\AssignInventoryCustomer;
use App\Model\AssignInventoryDetails;
use App\Model\AssignInventoryExpiry;
use App\Model\AssignInventoryPost;
use App\Model\AssignInventoryPostDamage;
use App\Model\AssignInventoryPostExpiry;
use App\Model\CustomerInfo;
use App\Model\ImportTempFile;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\SurveyQuestionAnswer;
use Illuminate\Http\Request;
use stdClass;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use File;
use URL;

class AssignInventoryController extends Controller
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

        $assign_inventory_query = AssignInventory::select('id', 'uuid', 'activity_name', 'valid_from', 'valid_to', 'status')
            ->with(
                'assignInventoryCustomer',
                'assignInventoryCustomer.customer:id,firstname,lastname',
                'assignInventoryCustomer.customer.customerInfo:id,user_id,customer_code',
                'assignInventoryDetails:id,uuid,assign_inventory_id,item_id,item_uom_id,capacity',
                'assignInventoryDetails.item:id,item_name,item_code',
                'assignInventoryDetails.itemUom:id,name'
            );

        if ($request->name) {
            $assign_inventory_query->where('activity_name', $request->name);
        }

        if ($request->start_date) {
            $assign_inventory_query->where('valid_from', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $assign_inventory_query->where('valid_to', date('Y-m-d', strtotime($request->end_date)));
        }

        $assign_inventory = $assign_inventory_query->get();

        $assign_inventory_array = array();
        if (is_object($assign_inventory)) {
            foreach ($assign_inventory as $key => $assign_inventory1) {
                $assign_inventory_array[] = $assign_inventory[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($assign_inventory_array[$offset])) {
                    $data_array[] = $assign_inventory_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($assign_inventory_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($assign_inventory_array);
        } else {
            $data_array = $assign_inventory_array;
        }

        return prepareResult(true, $data_array, [], "Assign Inventory listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating assign inventory", $this->unprocessableEntity);
        }

        if (is_array($request->assign_inventory_details) && sizeof($request->assign_inventory_details) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one assign inventory details.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $assign_inventory = new AssignInventory;
            $assign_inventory->activity_name = $request->activity_name;
            $assign_inventory->valid_from = $request->valid_from;
            $assign_inventory->valid_to = $request->valid_to;
            $assign_inventory->status = $request->status;
            $assign_inventory->save();

            if (is_array($request->customers)) {
                foreach ($request->customers as $customer) {
                    $assign_inventory_customer = new AssignInventoryCustomer;
                    $assign_inventory_customer->assign_inventory_id = $assign_inventory->id;
                    $assign_inventory_customer->customer_id = $customer;
                    $assign_inventory_customer->save();
                }
            }

            if (is_array($request->assign_inventory_details)) {
                foreach ($request->assign_inventory_details as $details) {
                    $assign_inventory_details = new AssignInventoryDetails;
                    $assign_inventory_details->assign_inventory_id = $assign_inventory->id;
                    $assign_inventory_details->item_id = $details['item_id'];
                    $assign_inventory_details->item_uom_id = $details['item_uom_id'];
                    $assign_inventory_details->capacity = $details['capacity'];
                    $assign_inventory_details->save();
                }
            }

            \DB::commit();

            $assign_inventory->getSaveData();

            return prepareResult(true, $assign_inventory, [], "Assign inventory added successfully", $this->created);
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
     * @param  int  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating area", $this->unauthorized);
        }

        $assign_inventory = AssignInventory::select('id', 'uuid', 'organisation_id', 'activity_name', 'valid_from', 'valid_to', 'status')
            ->with(
                'assignInventoryCustomer',
                'assignInventoryCustomer.customer:id,firstname,lastname',
                'assignInventoryDetails:id,uuid,assign_inventory_id,item_id,item_uom_id,capacity',
                'assignInventoryDetails.item:id,item_name',
                'assignInventoryDetails.itemUom:id,name'
            )
            ->where('uuid', $uuid)
            ->first();

        return prepareResult(true, $assign_inventory, [], "Assign Inventory listing", $this->success);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating assign inventory", $this->unprocessableEntity);
        }

        if (is_array($request->assign_inventory_details) && sizeof($request->assign_inventory_details) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one assign inventory details.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            $assign_inventory = AssignInventory::where('uuid', $uuid)->first();

            AssignInventoryDetails::where('assign_inventory_id', $assign_inventory->id)
                ->delete();

            AssignInventoryCustomer::where('assign_inventory_id', $assign_inventory->id)
                ->delete();

            $assign_inventory->activity_name = $request->activity_name;
            $assign_inventory->valid_from = $request->valid_from;
            $assign_inventory->valid_to = $request->valid_to;
            $assign_inventory->status = $request->status;
            $assign_inventory->save();

            if (is_array($request->customers)) {
                foreach ($request->customers as $customer) {
                    $assign_inventory_customer = new AssignInventoryCustomer;
                    $assign_inventory_customer->assign_inventory_id = $assign_inventory->id;
                    $assign_inventory_customer->customer_id = $customer;
                    $assign_inventory_customer->save();
                }
            }

            if (is_array($request->assign_inventory_details)) {
                foreach ($request->assign_inventory_details as $details) {
                    $assign_inventory_details = new AssignInventoryDetails;
                    $assign_inventory_details->assign_inventory_id = $assign_inventory->id;
                    $assign_inventory_details->item_id = $details['item_id'];
                    $assign_inventory_details->item_uom_id = $details['item_uom_id'];
                    $assign_inventory_details->capacity = $details['capacity'];
                    $assign_inventory_details->save();
                }
            }

            \DB::commit();

            $assign_inventory->getSaveData();

            return prepareResult(true, $assign_inventory, [], "Assign inventory updated successfully", $this->created);
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
            return prepareResult(false, [], [], "Error while validating assign inventory", $this->unauthorized);
        }

        $assign_inventory = AssignInventory::where('uuid', $uuid)
            ->first();

        if (is_object($assign_inventory)) {
            $assign_inventory->delete();

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
                'activity_name' => 'required|string',
                'valid_from' => 'required|date',
                'valid_to' => 'required|date'
            ]);
        }

        if ($type == "inverntory_post_add") {
            $validator = \Validator::make($input, [
                'assign_inventory_id' => 'required|integer|exists:assign_inventories,id',
                'customer_id' => 'required|integer|exists:users,id'
                // 'item_id' => 'required|integer|exists:items,id',
                // 'item_uom_id' => 'required|integer|exists:item_uoms,id',
                // 'qty' => 'required',
                // 'expiry_date' => 'required|date'
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function routeCustomerInventory($route_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $customer_info = CustomerInfo::where('route_id', $route_id)
            ->with('assignInventory', 'assignInventoryDetails:id,uuid,item_id,item_uom_id', 'assignInventoryDetails.item:id,item_name', 'assignInventoryDetails.itemUom:id,name')
            ->first();

        return prepareResult(true, $customer_info, [], "Assign Inventory listing", $this->success);
    }

    /**
     * Store Inverntory Post a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function storeInverntoryPost(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "inverntory_post_add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating assign inventory post", $this->unprocessableEntity);
        }

        if (is_array($request->items) && sizeof($request->items) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one items.", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            foreach ($request->items as $item) {
                $assign_inventory_post = new AssignInventoryPost;
                $assign_inventory_post->assign_inventory_id = $request->assign_inventory_id;
                $assign_inventory_post->customer_id = $request->customer_id;
                $assign_inventory_post->trip_id = $request->trip_id;
                $assign_inventory_post->item_id = $item['item_id'];
                $assign_inventory_post->item_uom_id = $item['item_uom_id'];
                $assign_inventory_post->qty = $item['qty'];
                $assign_inventory_post->capacity = $item['capacity'];
                $assign_inventory_post->refill = $item['refill'];
                $assign_inventory_post->fill = $item['fill'];
                $assign_inventory_post->reorder = $item['reorder'];
                $assign_inventory_post->out_of_stock = (!empty($item['out_of_stock'])) ? 1 : 0;
                // $assign_inventory_post->expiry_date = $item['expiry_date'];
                $assign_inventory_post->status = 1;
                $assign_inventory_post->save();

                if (is_array($item['expiry']) && sizeof($item['expiry']) >= 1) {
                    foreach ($item['expiry'] as $expiry) {
                        $assign_inventory_post_expiry = new AssignInventoryPostExpiry;
                        $assign_inventory_post_expiry->assign_inventory_post_id = $assign_inventory_post->id;
                        $assign_inventory_post_expiry->item_id = $expiry['item_id'];
                        $assign_inventory_post_expiry->item_uom_id = $expiry['item_uom_id'];
                        $assign_inventory_post_expiry->qty = $expiry['qty'];
                        $assign_inventory_post_expiry->expiry_date = $expiry['expiry_date'];
                        $assign_inventory_post_expiry->save();
                    }
                }

                if (is_array($item['damage']) && sizeof($item['damage']) >= 1) {
                    foreach ($item['damage'] as $damage) {
                        $assign_inventory_post_damage = new AssignInventoryPostDamage;
                        $assign_inventory_post_damage->assign_inventory_id = $assign_inventory_post->assign_inventory_id;
                        $assign_inventory_post_damage->assign_inventory_post_id = $assign_inventory_post->id;
                        $assign_inventory_post_damage->customer_id = $request->customer_id;
                        $assign_inventory_post_damage->item_id = $damage['item_id'];
                        $assign_inventory_post_damage->item_uom_id = $damage['item_uom_id'];
                        $assign_inventory_post_damage->damage_item_qty = $damage['damage_item_qty'];
                        $assign_inventory_post_damage->expire_item_qty = $damage['expire_item_qty'];
                        $assign_inventory_post_damage->saleable_item_qty = $damage['saleable_item_qty'];
                        $assign_inventory_post_damage->save();
                    }
                }

                // if ($assign_inventory_post->capacity != 0) {
                //     $assign_inventory_detail = AssignInventoryDetails::where('assign_inventory_id', $assign_inventory_post->assign_inventory_id)
                //         ->where('item_id', $assign_inventory_post->item_id)
                //         ->where('item_uom_id', $assign_inventory_post->item_uom_id)
                //         ->first();

                //     if (is_object($assign_inventory_detail) && $assign_inventory_detail->capacity == "0.000") {
                //         $assign_inventory_detail->capacity = $assign_inventory_post->capacity;
                //         $assign_inventory_detail->save();
                //     }
                // }
            }

            \DB::commit();
            return prepareResult(true, $assign_inventory_post, [], "Assign inventory post successfully", $this->created);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    /**
     * Show a newly created resource in storage.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showInverntoryPost(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$request->inventory_id) {
            return prepareResult(false, [], [], "Error while validating assign invetory", $this->unprocessableEntity);
        }

        $assign_inventory_post_query = AssignInventoryPost::with(
            'item:id,item_name,item_code',
            'itemUom:id,name',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'assignInventory',
            'assignInventoryPostExpiry',
            'assignInventoryPostExpiry.item:id,item_name,item_code',
            'assignInventoryPostExpiry.itemUom:id,name'
        )
            ->where('assign_inventory_id', $request->inventory_id);

        if ($request->date) {
            $assign_inventory_post_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->uom) {
            $uom = $request->uom;
            $assign_inventory_post_query->whereHas('itemUom', function ($q) use ($uom) {
                $q->where('name', $uom);
            });
        }

        if ($request->customer) {
            $customer = $request->customer;
            $assign_inventory_post_query->whereHas('customer', function ($q) use ($customer) {
                $q->where('firstname', 'like', '%' . $customer . '%');
            });
        }


        if ($request->customer_code) {
            $customer_code = $request->customer_code;
            $assign_inventory_post_query->whereHas('customer', function ($q) use ($customer_code) {
                $q->where('customer_code', $customer_code);
            });
        }

        if ($request->item) {
            $item = $request->item;
            $assign_inventory_post_query->whereHas('item', function ($q) use ($item) {
                $q->where('item_name', $item);
            });
        }

        if ($request->item_code) {
            $item_code = $request->item_code;
            $assign_inventory_post_query->whereHas('item', function ($q) use ($item_code) {
                $q->where('item_code', $item_code);
            });
        }

        if ($request->all) {
            $assign_inventory_post = $assign_inventory_post_query->get();
        } else {
            $assign_inventory_post = $assign_inventory_post_query->get();
        }

        return prepareResult(true, $assign_inventory_post, [], "Assign Inventory Post listing", $this->success);
    }

    public function showCustomerInventory($merchandiser_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$merchandiser_id) {
            return prepareResult(false, [], [], "Error while validating customer id", $this->unauthorized);
        }

        $date = date('Y-m-d');

        $customer_info = AssignInventoryCustomer::select()
            ->with(
                'assignInventory:id,activity_name,valid_from,valid_to,status',
                'customer:id,firstname,lastname',
                'customer.customerInfo:id,user_id,merchandiser_id,customer_code',
                'assignInventory.assignInventoryDetails:id,assign_inventory_id,item_id,item_uom_id,capacity',
                'assignInventory.assignInventoryDetails.item:id,item_name,item_code',
                'assignInventory.assignInventoryDetails.itemUom:id,name'
            )
            ->whereHas('assignInventory', function ($query) use ($date) {
                $query->whereDate('valid_to', '>=', $date);
            })
            ->whereHas('customer.customerInfo', function ($query) use ($merchandiser_id) {
                $query->where('merchandiser_id', $merchandiser_id);
            })
            ->get();

        // $customer_info = CustomerInfo::select('id', 'user_id', 'merchandiser_id')
        //     ->with(
        //         'user:id,firstname,lastname',
        //         'assignInventoryCustomer:id,assign_inventory_id,customer_id',
        //         'assignInventoryCustomer.assignInventory:id,activity_name,valid_from,valid_to,status',
        //         'assignInventoryCustomer.assignInventory.assignInventoryDetails:id,assign_inventory_id,item_id,item_uom_id,capacity',
        //         'assignInventoryCustomer.assignInventory.assignInventoryDetails.item:id,item_name',
        //         'assignInventoryCustomer.assignInventory.assignInventoryDetails.itemUom:id,name'
        //     )
        //     ->where('merchandiser_id', $merchandiser_id)
        //     ->whereHas('assignInventoryCustomer.assignInventory', function ($query) use ($date) {
        //         $query->whereDate('valid_to', '>=', $date);
        //     })
        //     ->get();

        // $dataArray = array();
        // $i = 0;
        // foreach ($customer_info as $key => $customer) {
        //     foreach ($customer->assignInventoryCustomer as $aKey => $aCustomer) {
        //         if (is_object($aCustomer->assignInventory)) {
        //             if ($aCustomer->assignInventory->valid_to >= date('Y-m-d')) {
        //             } else {
        //                 unset($customer_info[$key]);
        //             }
        //         }
        //     }
        // }
        // pre($customer_info);
        $merge_all_data = array();
        // foreach ($customer_info as $custKey => $customer) {
        //     $merge_data = new stdClass;
        //     $merge_data->cusotmer_id = $customer->user_id;
        //     $merge_data->user = $customer->user;
        //     foreach ($customer->assignInventoryCustomer as $aicKey => $assign_inventory_customer) {
        //         $merge_data->assign_inventory = $assign_inventory_customer->assignInventory;
        //     }
        //     $merge_all_data[] = $merge_data;
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

        return prepareResult(true, $data_array, [], "Assign Inventory listing", $this->success, $pagination);
    }

    public function showCustomerInventoryByRoute($route_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$route_id) {
            return prepareResult(false, [], [], "Error while validating route id", $this->unauthorized);
        }

        $date = date('Y-m-d');

        $customer_info = AssignInventoryCustomer::select()
        ->with(
            'assignInventory:id,activity_name,valid_from,valid_to,status',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id',
            'assignInventory.assignInventoryDetails:id,assign_inventory_id,item_id,item_uom_id,capacity',
            'assignInventory.assignInventoryDetails.item:id,item_name',
            'assignInventory.assignInventoryDetails.itemUom:id,name'
        )
        ->whereHas('assignInventory', function ($query) use ($date) {
            $query->whereDate('valid_to', '>=', $date);
        })
        ->whereHas('customer.customerInfo', function ($query) use ($route_id) {
            $query->where('route_id', $route_id);
        })
        ->orderBy('id', 'desc')
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

        return prepareResult(true, $data_array, [], "Assign Inventory listing", $this->success, $pagination);
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("Activity name", "Valid from", "Valid to", "Status", "Customer code", "Item", "Item UOM", "Capacity");

        return prepareResult(true, $mappingarray, [], "Assign inventory Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'assigninventory_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate assign inventory import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('assigninventory_file')->store('import');
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


            $import = new AssignInventoryImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

            // pre($import );
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
            //echo '<pre>';
            //print_r($import->failures());
            //echo '</pre>';
            $errors = $errror_array;
            $result['successrecordscount'] = $succussrecords;
            $result['errorrcount'] = $errorrecords;
            $result['successfileids'] = $successfileids;


            //}
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
        return prepareResult(true, $result, $errors, "assign inventory successfully imported", $this->success);
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

                    $current_organisation_id = request()->user()->organisation_id;

                    $customer = CustomerInfo::where('customer_code', $row[4])->first();
                    $item = Item::where('item_code', $row[5])->first();
                    $itemUom = ItemUom::where('name', $row[6])->first();

                    $assignInventory = AssignInventory::where('activity_name', $row[0])
                        ->whereDate('valid_from', date('Y-m-d', strtotime($row[1])))
                        ->whereDate('valid_to', date('Y-m-d', strtotime($row[2])))
                        ->first();

                    $skipduplicate = $request->skipduplicate;

                    if ($skipduplicate) {
                        if (is_object($assignInventory)) {
                        } else {
                            $assignInventory = new AssignInventory;
                            $assign_inventory_customers = new AssignInventoryCustomer;
                            $assign_inventory_details = new AssignInventoryDetails;
                        }
                    } else {
                    }

                    if (is_object($assignInventory)) {
                        if (!is_object($itemUom) or !is_object($customer) or !is_object($item)) {
                            if (!is_object($itemUom)) {
                                return prepareResult(false, [], [], "item uom not exists", $this->unauthorized);
                            }
                            if (!is_object($customer)) {
                                return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                            }
                            if (!is_object($item)) {
                                return prepareResult(false, [], [], "item not exists", $this->unauthorized);
                            }
                        }
                        $assignInventory->activity_name = $row[0];
                        $assignInventory->valid_from  = date('Y-m-d', strtotime($row[1]));
                        $assignInventory->valid_to = date('Y-m-d', strtotime($row[2]));
                        $assignInventory->status  = $row[3];
                        $assignInventory->save();

                        $assign_inventory_customers = AssignInventoryCustomer::where('assign_inventory_id', $assignInventory->id)
                            ->where('customer_id', $customer->user_id)
                            ->first();

                        if (!is_object($assign_inventory_customers)) {
                            $assign_inventory_customers = new AssignInventoryCustomer;
                        }

                        $assign_inventory_customers->assign_inventory_id  = $assignInventory->id;
                        $assign_inventory_customers->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $assign_inventory_customers->save();

                        $assign_inventory_details = AssignInventoryDetails::where('assign_inventory_id', $assignInventory->id)
                            ->where('item_id', $item->id)
                            ->where('item_uom_id', $itemUom->id)
                            ->first();

                        if (!is_object($assign_inventory_details)) {
                            $assign_inventory_details = new AssignInventoryDetails;
                        }

                        $assign_inventory_details->assign_inventory_id  = $assignInventory->id;
                        $assign_inventory_details->item_id  = (is_object($item)) ? $item->id : 0;
                        $assign_inventory_details->item_uom_id  = (is_object($itemUom)) ? $itemUom->id : 0;
                        $assign_inventory_details->capacity  = number_format((float) $row[7], 2, '.', '');
                        $assign_inventory_details->save();
                    } else {
                        if (!is_object($itemUom) or !is_object($customer) or !is_object($item)) {
                            if (!is_object($itemUom)) {
                                return prepareResult(false, [], [], "item uom not exists", $this->unauthorized);
                            }
                            if (!is_object($customer)) {
                                return prepareResult(false, [], [], "customer not exists", $this->unauthorized);
                            }
                            if (!is_object($item)) {
                                return prepareResult(false, [], [], "item not exists", $this->unauthorized);
                            }
                        }
                        $assignInventory = new AssignInventory;
                        $assignInventory->organisation_id  = $current_organisation_id;
                        $assignInventory->activity_name = $row[0];
                        $assignInventory->valid_from  = date('Y-m-d', strtotime($row[1]));
                        $assignInventory->valid_to = date('Y-m-d', strtotime($row[2]));
                        $assignInventory->status  = $row[3];
                        $assignInventory->save();

                        $assign_inventory_customers = new AssignInventoryCustomer;
                        $assign_inventory_customers->assign_inventory_id  = $assignInventory->id;
                        $assign_inventory_customers->customer_id = (is_object($customer)) ? $customer->user_id : 0;
                        $assign_inventory_customers->save();

                        $assign_inventory_details = new AssignInventoryDetails;
                        $assign_inventory_details->assign_inventory_id  = $assignInventory->id;
                        $assign_inventory_details->item_id  = (is_object($item)) ? $item->id : 0;
                        $assign_inventory_details->item_uom_id  = (is_object($itemUom)) ? $itemUom->id : 0;
                        $assign_inventory_details->capacity  = number_format((float) $row[7], 2, '.', '');
                        $assign_inventory_details->save();
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "assign inventory successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    public function assignDamageList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!$request->assign_inventory_id) {
            return prepareResult(false, [], [], "Error while validating asset tracking", $this->unprocessableEntity);
        }

        $assign_inventory_id = $request->assign_inventory_id;

        $assign_inventory_post_damage_query = AssignInventoryPostDamage::with(
            'item:id,item_name,item_code',
            'itemUom:id,name',
            'customer:id,firstname,lastname',
            'customer.customerInfo:id,user_id,customer_code',
            'salesman:id,firstname,lastname',
            'salesman.salesmanInfo:id,user_id,salesman_code',
            'assignInventory',
            'assignInventoryPost'
        )
            ->where('assign_inventory_id', $assign_inventory_id);
        if ($request->date) {
            $assign_inventory_post_damage_query->whereDate('created_at', date('Y-m-d', strtotime($request->date)));
        }

        if ($request->salesman_name) {
            $salesman_name = $request->salesman_name;
            $exploded_name = explode(" ", $salesman_name);
            if (count($exploded_name) < 2) {
                $assign_inventory_post_damage_query->whereHas('salesman', function ($q) use ($salesman_name) {
                    $q->where('firstname', 'like', '%' . $salesman_name . '%')
                        ->orWhere('lastname', 'like', '%' . $salesman_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $assign_inventory_post_damage_query->whereHas('salesman', function ($q) use ($n) {
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
                $assign_inventory_post_damage_query->whereHas('customer', function ($q) use ($customer_name) {
                    $q->where('firstname', 'like', '%' . $customer_name . '%')
                        ->orWhere('lastname', 'like', '%' . $customer_name . '%');
                });
            } else {
                foreach ($exploded_name as $n) {
                    $assign_inventory_post_damage_query->whereHas('customer', function ($q) use ($n) {
                        $q->where('firstname', 'like', '%' . $n . '%')
                            ->orWhere('lastname', 'like', '%' . $n . '%');
                    });
                }
            }
        }

        if ($request->customer_code) {
            $code = $request->customer_code;
            $assign_inventory_post_damage_query->whereHas('customer.customerInfo', function ($q) use ($code) {
                $q->where('customer_code', $code);
            });
        }

        if ($request->item_name) {
            $item_name = $request->item_name;
            $assign_inventory_post_damage_query->whereHas('item', function ($q) use ($item_name) {
                $q->where('item_name', $item_name);
            });
        }

        if ($request->item_code) {
            $code = $request->item_code;
            $assign_inventory_post_damage_query->whereHas('item', function ($q) use ($code) {
                $q->where('item_code', $code);
            });
        }

        if ($request->all) {
            $assign_inventory_post_damage = $assign_inventory_post_damage_query->orderBy('id', 'desc')->get();
        } else {
            if ($request->today) {
                $assign_inventory_post_damage_query->whereDate('created_at', date('Y-m-d'));
            }
            $assign_inventory_post_damage = $assign_inventory_post_damage_query->orderBy('id', 'desc')->get();
        }

        $assign_inventory_post_damage_array = array();
        if (is_object($assign_inventory_post_damage)) {
            foreach ($assign_inventory_post_damage as $key => $assign_inventory_post_damage1) {
                $assign_inventory_post_damage_array[] = $assign_inventory_post_damage[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($assign_inventory_post_damage_array[$offset])) {
                    $data_array[] = $assign_inventory_post_damage_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($assign_inventory_post_damage_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($assign_inventory_post_damage_array);
        } else {
            $data_array = $assign_inventory_post_damage_array;
        }

        return prepareResult(true, $data_array, [], "Assign Inventory post damage listing", $this->success, $pagination);
    }
}
