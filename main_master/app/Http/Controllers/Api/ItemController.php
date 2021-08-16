<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\CustomFieldValueSave;
use App\Model\ImportTempFile;
use App\Model\ItemGroup;
use App\Model\ItemMajorCategory;
use App\Model\ItemUom;
use Illuminate\Http\Request;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\WorkFlowObject;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Imports\ItemImport;
use App\Model\ItemLob;
use App\Model\ProductCatalog;
use App\Model\Lob;
use App\Model\WorkFlowRuleApprovalUser;
use File;
use URL;

class ItemController extends Controller
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

        $product_catalog = $request->product_catalog;

        $itemList = Item::with(
            'itemUomLowerUnit:id,name,code',
            'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price,purchase_order_price,stock_keeping_unit',
            'ItemMainPrice.itemUom:id,name,code',
            'itemMajorCategory:id,uuid,name',
            'itemGroup:id,uuid,name,code,status',
            'brand:id,uuid,brand_name,status',
            'productCatalog',
            'itemLob',
            'itemLob.item:id,item_code,item_name',
            'itemLob.lob:id,name',
            'supervisorCategory:id,name,status'
        );

        if ($request->item_code) {
            $itemList->where('item_code', 'like', '%' . $request->item_code . '%');
        }

        if ($request->item_name) {
            $itemList->where('item_name', 'like', '%' . $request->item_name . '%');
        }

        if ($request->brand) {
            $brand = $request->brand;
            $itemList->whereHas('brand', function ($q) use ($brand) {
                $q->where('brand_name', 'like', '%' . $brand . '%');
            });
        }

        if ($request->category) {
            $category = $request->category;
            $itemList->whereHas('itemMajorCategory', function ($q) use ($category) {
                $q->where('name', 'like', '%' . $category . '%');
            });
        }

        if ($request->lob) {
            $lob = $request->lob;
            $itemList->whereHas('itemLob.lob', function ($q) use ($lob) {
                $q->where('name', 'like', '%' . $lob . '%');
            });
        }

        if ($product_catalog) {
            $item = $itemList->where('is_product_catalog', 1)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $item = $itemList->orderBy('id', 'desc')->get();
        }

        $results = GetWorkFlowRuleObject('Item');
        $approve_need_item = array();
        $approve_need_item_object_id = array();
        if (count($results) > 0) {
            foreach ($results as $raw) {
                $approve_need_item[] = $raw['object']->raw_id;
                $approve_need_item_object_id[$raw['object']->raw_id] = $raw['object']->uuid;
            }
        }

        // approval
        $item_array = array();
        if (is_object($item)) {
            foreach ($item as $key => $user1) {
                if (in_array($item[$key]->id, $approve_need_item)) {
                    $item[$key]->need_to_approve = 'yes';
                    if (isset($approve_need_item_object_id[$item[$key]->id])) {
                        $item[$key]->objectid = $approve_need_item_object_id[$item[$key]->id];
                    } else {
                        $item[$key]->objectid = '';
                    }
                } else {
                    $item[$key]->need_to_approve = 'no';
                    $item[$key]->objectid = '';
                }

                if (
                    $item[$key]->current_stage == 'Approved' ||
                    request()->user()->usertype == 1 ||
                    in_array(
                        $item[$key]->id,
                        $approve_need_item
                    )
                ) {
                    $item_array[] = $item[$key];
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
                if (isset($item_array[$offset])) {
                    $data_array[] = $item_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($item_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($item_array);
        } else {
            $data_array = $item_array;
        }

        return prepareResult(true, $data_array, [], "Item listing", $this->success, $pagination);
    }

    /**
     * Display a listing of the resource.
     * with child params
     * @return \Illuminate\Http\Response
     */
    public function indexMobile(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $product_catalog = $request->product_catalog;

        $itemList = Item::with(
            'itemUomLowerUnit:id,name,code',
            'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price,purchase_order_price,stock_keeping_unit',
            'ItemMainPrice.itemUom:id,name,code',
            'itemMajorCategory:id,uuid,name,parent_id,node_level',
            'itemMajorCategory.children:id,uuid,name,parent_id,node_level',
            'itemMajorCategory.parent:id,uuid,name,parent_id,node_level',
            'itemGroup:id,uuid,name,code,status',
            'brand:id,uuid,brand_name,parent_id,node_level,status',
            'brand.children:id,uuid,brand_name,parent_id,node_level,status',
            'brand.parent:id,uuid,brand_name,parent_id,node_level,status',
            'productCatalog',
            'itemLob',
            'itemLob.item:id,item_code,item_name',
            'itemLob.lob:id,name',
            'supervisorCategory:id,name,status'
        );
        if ($product_catalog) {
            $item = $itemList->where('is_product_catalog', 1)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $item = $itemList->orderBy('id', 'desc')->get();
        }

        $item_array = array();
        if (is_object($item)) {
            foreach ($item as $key => $item1) {
                $item_array[] = $item[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($item_array[$offset])) {
                    $data_array[] = $item_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($item_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($item_array);
        } else {
            $data_array = $item_array;
        }

        return prepareResult(true, $data_array, [], "Item listing", $this->success, $pagination);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item", $this->unprocessableEntity);
        }

        if ($request->new_lunch) {
            $validate = $this->validations($input, "new_lunch");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating item", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {

            $status = 1;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Item', 'create', $current_organisation_id)) {
                $status = 0;
                $current_stage = 'Pending';
                //$this->createWorkFlowObject($isActivate, 'Order',$request);
            }

            $item = new Item;
            $item->item_major_category_id = $request->item_major_category_id;
            $item->item_group_id = $request->item_group_id;
            $item->brand_id = $request->brand_id;
            $item->is_product_catalog = $request->is_product_catalog ? 1 : 0;
            $item->is_promotional = $request->is_promotional;
            $item->item_code = nextComingNumber('App\Model\Item', 'item', 'item_code', $request->item_code);
            $item->erp_code = $request->erp_code;
            $item->item_name = $request->item_name;
            $item->item_description = $request->item_description;
            $item->item_barcode = $request->item_barcode;
            $item->item_weight = $request->item_weight;
            $item->item_shelf_life = $request->item_shelf_life;
            $item->volume = $request->volume;
            $item->lower_unit_uom_id = $request->lower_unit_uom_id;
            $item->is_tax_apply = $request->is_tax_apply;
            $item->lower_unit_item_upc = $request->lower_unit_item_upc;
            $item->lower_unit_item_price = $request->lower_unit_item_price;
            $item->lower_unit_purchase_order_price = $request->lower_unit_purchase_order_price;
            $item->item_vat_percentage = $request->item_vat_percentage;
            $item->stock_keeping_unit = $request->stock_keeping_unit ? 1 : 0;
            // $item->secondary_stock_keeping_unit = $request->secondary_stock_keeping_unit ? 1 : 0;
            $item->item_excise = $request->item_excise;

            $item->new_lunch = $request->new_lunch ? 1 : 0;
            $item->start_date = $request->start_date;
            $item->end_date = $request->end_date;
            $item->supervisor_category_id = (!empty($request->supervisor_category_id)) ? $request->supervisor_category_id : null;

            $item->current_stage = $current_stage;
            $item->current_stage_comment = $request->current_stage_comment;
            $item->status = $status;
            // $item->lob_id =  (!empty($request->lob_id)) ? $request->lob_id : null;

            if ($request->item_image) {
                $item->item_image = saveImage($request->item_name, $request->item_image, 'items');
            }
            $item->save();

            if ($isActivate = checkWorkFlowRule('Item', 'create')) {
                $status = 0;
                $this->createWorkFlowObject($isActivate, 'Item', $request, $item);
            }

            if (is_array($request->item_lobs)) {
                foreach ($request->item_lobs as $lob) {
                    $itemLob = new ItemLob;
                    $itemLob->item_id = $item->id;
                    $itemLob->lob_id = $lob;
                    $itemLob->save();
                }
            }

            if (is_array($request->item_main_price)) {
                foreach ($request->item_main_price as $main_price) {
                    //save Main Price
                    $item_main_price = new ItemMainPrice;
                    $item_main_price->item_id = $item->id;
                    $item_main_price->item_upc = $main_price['item_upc'];
                    $item_main_price->item_uom_id = $main_price['item_uom_id'];
                    $item_main_price->item_price = $main_price['item_price'];
                    $item_main_price->purchase_order_price = $main_price['purchase_order_price'];
                    $item_main_price->stock_keeping_unit = $main_price['stock_keeping_unit'] ? 1 : 0;
                    $item_main_price->status = $main_price['status'];
                    $item_main_price->save();
                }
            }

            if ($request->is_product_catalog) {
                $product_catalog = new ProductCatalog;
                $product_catalog->barcode = $request->barcode;
                $product_catalog->item_id = $item->id;
                $product_catalog->net_weight = $request->net_weight;
                $product_catalog->flawer = $request->flawer;
                $product_catalog->shelf_file = $request->shelf_file;
                $product_catalog->ingredients = $request->ingredients;
                $product_catalog->energy = $request->energy;
                $product_catalog->fat = $request->fat;
                $product_catalog->protein = $request->protein;
                $product_catalog->carbohydrate = $request->carbohydrate;
                $product_catalog->calcium = $request->calcium;
                $product_catalog->sodium = $request->sodium;
                $product_catalog->potassium = $request->potassium;
                $product_catalog->crude_fibre = $request->crude_fibre;
                $product_catalog->vitamin = $request->vitamin;

                if ($request->image) {
                    $destinationPath    = 'uploads/product-catalog/';
                    $image_name = $request->item_name;
                    $image = $request->image;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $product_catalog->image_string           = URL('/') . '/' . $destinationPath . $fileName;
                }

                $product_catalog->save();
            }

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                foreach ($request->modules as $module) {
                    savecustomField($item->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            updateNextComingNumber('App\Model\Item', 'item');
            $item->getSaveData();
            return prepareResult(true, $item, [], "Item added successfully", $this->success);
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
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $item = Item::where('uuid', $uuid)
            ->with(
                'itemUomLowerUnit:id,name,code',
                'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price,purchase_order_price,stock_keeping_unit',
                'ItemMainPrice.itemUom:id,name,code',
                'itemMajorCategory:id,uuid,name',
                'itemGroup:id,uuid,name,code,status',
                'brand:id,uuid,brand_name,status',
                'customFieldValueSave',
                'customFieldValueSave.customField',
                'productCatalog',
                'lob'
            )
            ->first();

        if (!is_object($item)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unprocessableEntity);
        }

        return prepareResult(true, $item, [], "Item Edit", $this->success);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating outlet product code", $this->unprocessableEntity);
        }

        if ($request->new_lunch) {
            $validate = $this->validations($input, "new_lunch");
            if ($validate["error"]) {
                return prepareResult(false, [], $validate['errors']->first(), "Error while validating item", $this->unprocessableEntity);
            }
        }

        \DB::beginTransaction();
        try {
            $status = $request->status;
            $current_stage = 'Approved';
            $current_organisation_id = request()->user()->organisation_id;
            if ($isActivate = checkWorkFlowRule('Item', 'create', $current_organisation_id)) {
                $current_stage = 'Pending';
            }


            $item = Item::where('uuid', $uuid)->first();

            if (!is_object($item)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            ItemLob::whereIn('item_id', $item->id)->delete();
            ItemMainPrice::where('item_id', $item->id)->delete();

            $item->item_major_category_id = $request->item_major_category_id;
            $item->item_group_id = $request->item_group_id;
            $item->is_product_catalog = $request->is_product_catalog ? 1 : 0;
            $item->is_promotional = $request->is_promotional;
            $item->brand_id = $request->brand_id;
            $item->item_name = $request->item_name;
            $item->erp_code = $request->erp_code;
            $item->item_description = $request->item_description;
            $item->item_barcode = $request->item_barcode;
            $item->item_weight = $request->item_weight;
            $item->item_shelf_life = $request->item_shelf_life;
            $item->volume = $request->volume;
            $item->lower_unit_uom_id = $request->lower_unit_uom_id;
            $item->is_tax_apply = $request->is_tax_apply;

            $item->lower_unit_item_upc = $request->lower_unit_item_upc;
            $item->lower_unit_item_price = $request->lower_unit_item_price;
            $item->lower_unit_purchase_order_price = $request->lower_unit_purchase_order_price;
            $item->item_vat_percentage = $request->item_vat_percentage;
            $item->item_excise = $request->item_excise;
            $item->stock_keeping_unit = $request->stock_keeping_unit  ? 1 : 0;

            $item->supervisor_category_id = (!empty($request->supervisor_category_id)) ? $request->supervisor_category_id : null;
            $item->new_lunch = $request->new_lunch ? 1 : 0;
            $item->start_date = $request->start_date;
            $item->end_date = $request->end_date;

            // $item->secondary_stock_keeping_unit = $request->secondary_stock_keeping_unit  ? 1 : 0;
            $item->current_stage = $current_stage;
            $item->current_stage_comment = $request->current_stage_comment;
            $item->status = $status;
            // $item->lob_id =  (!empty($request->lob_id)) ? $request->lob_id : null;

            if ($request->item_image) {
                $item->item_image = saveImage($request->item_name, $request->item_image, 'items');
            }
            $item->save();

            // if (is_array($request->item_main_price) && sizeof($request->item_main_price) < 1) {
            //     return prepareResult(false, [], [], "Error Please add atleast one main price.", $this->unprocessableEntity);
            // }

            if ($isActivate = checkWorkFlowRule('Item', 'edit')) {
                $this->createWorkFlowObject($isActivate, 'Item', $request, $item);
            }

            if (is_array($request->item_lobs)) {
                foreach ($request->item_lobs as $lob) {
                    $itemLob = new ItemLob;
                    $itemLob->item_id = $item->id;
                    $itemLob->lob_id = $lob;
                    $itemLob->save();
                }
            }

            if (is_array($request->item_main_price)) {
                foreach ($request->item_main_price as $main_price) {
                    //save Main Price
                    $item_main_price = new ItemMainPrice;
                    $item_main_price->item_id = $item->id;
                    $item_main_price->item_upc = $main_price['item_upc'];
                    $item_main_price->item_uom_id = $main_price['item_uom_id'];
                    $item_main_price->item_price = $main_price['item_price'];
                    $item_main_price->purchase_order_price = $main_price['purchase_order_price'];
                    $item_main_price->stock_keeping_unit = $main_price['stock_keeping_unit'] ? 1 : 0;
                    $item_main_price->status = $main_price['status'];
                    $item_main_price->save();
                }
            }

            if ($request->is_product_catalog) {
                $product_catalog = ProductCatalog::where('item_id', $item->id)->first();
                if (is_object($product_catalog)) {
                    $product_catalog->delete();
                }
                $product_catalog = new ProductCatalog;
                $product_catalog->barcode = $request->barcode;
                $product_catalog->item_id = $item->id;
                $product_catalog->net_weight = $request->net_weight;
                $product_catalog->flawer = $request->flawer;
                $product_catalog->shelf_file = $request->shelf_file;
                $product_catalog->ingredients = $request->ingredients;
                $product_catalog->energy = $request->energy;
                $product_catalog->fat = $request->fat;
                $product_catalog->protein = $request->protein;
                $product_catalog->carbohydrate = $request->carbohydrate;
                $product_catalog->calcium = $request->calcium;
                $product_catalog->sodium = $request->sodium;
                $product_catalog->potassium = $request->potassium;
                $product_catalog->crude_fibre = $request->crude_fibre;
                $product_catalog->vitamin = $request->vitamin;

                if ($request->image) {
                    $destinationPath    = 'uploads/product-catalog/';
                    $image_name = $request->item_name;
                    $image = $request->image;
                    $getBaseType = explode(',', $image);
                    $getExt = explode(';', $image);
                    $image = str_replace($getBaseType[0] . ',', '', $image);
                    $image = str_replace(' ', '+', $image);
                    $fileName = $image_name . '-' . time() . '.' . basename($getExt[0]);
                    \File::put($destinationPath . $fileName, base64_decode($image));
                    $product_catalog->image_string           = URL('/') . '/' . $destinationPath . $fileName;
                }

                $product_catalog->save();
            }

            if (is_array($request->modules) && sizeof($request->modules) >= 1) {
                CustomFieldValueSave::where('record_id', $item->id)->delete();
                foreach ($request->modules as $module) {
                    savecustomField($item->id, $module['module_id'], $module['custom_field_id'], $module['custom_field_value']);
                }
            }

            \DB::commit();
            $item->getSaveData();
            return prepareResult(true, $item, [], "Item update successfully", $this->success);
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
            return prepareResult(false, [], [], "Error while validating Item", $this->unauthorized);
        }

        $item = Item::where('uuid', $uuid)
            ->first();

        if (is_object($item)) {
            $item->delete();

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
                'item_major_category_id' => 'required|integer|exists:item_major_categories,id',
                'item_group_id' => 'required|integer|exists:item_groups,id',
                'item_name' => 'required',
                'item_code' => 'required',
                'lower_unit_uom_id' => 'required',
                'is_tax_apply' => 'required',
                'lower_unit_item_upc' => 'required',
                'lower_unit_item_price' => 'required',
                'lower_unit_purchase_order_price' => 'required',
                // 'item_vat_percentage' => 'required',
                // 'item_excise' => 'required',
                'status' => 'required'
                // 'brand_id' => 'required|integer|exists:brands,id',
                // 'item_barcode' => 'required',
                // 'item_weight' => 'required',
                // 'item_shelf_life' => 'required',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "new_lunch") {
            $validator = \Validator::make($input, [
                'start_date' => 'required',
                'end_date' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == 'bulk-action') {
            $validator = \Validator::make($input, [
                'action' => 'required',
                'item_ids' => 'required'
            ]);
            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
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

    public function promotionalItems()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $item = Item::with(
            'itemUomLowerUnit:id,name,code',
            'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price',
            'ItemMainPrice.itemUom:id,name,code',
            'itemMajorCategory:id,uuid,name',
            'itemGroup:id,uuid,name,code,status',
            'brand:id,uuid,brand_name,status'
        )
            ->where('is_promotional', 1)
            ->orderBy('id', 'desc')
            ->get();

        $item_array = array();
        if (is_object($item)) {
            foreach ($item as $key => $item1) {
                $item_array[] = $item[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($item_array[$offset])) {
                    $data_array[] = $item_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($item_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($item_array);
        } else {
            $data_array = $item_array;
        }

        return prepareResult(true, $data_array, [], "Promotional Item listing", $this->success, $pagination);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating item.", $this->unprocessableEntity);
        }

        $action = $request->action;

        if (empty($action)) {
            return prepareResult(false, [], [], "Please provide valid action parameter value.", $this->unprocessableEntity);
        }

        if ($action == 'active' || $action == 'inactive') {
            $uuids = $request->item_ids;

            foreach ($uuids as $uuid) {
                Item::where('uuid', $uuid)->update([
                    'status' => ($action == 'active') ? 1 : 0
                ]);
            }

            // $CustomerInfo = $this->index();
            return prepareResult(true, "", [], "Item status updated", $this->success);
        } else if ($action == 'delete') {
            $uuids = $request->item_ids;
            foreach ($uuids as $uuid) {
                Item::where('uuid', $uuid)->delete();
            }

            // $CustomerInfo = $this->index();
            return prepareResult(true, "", [], "Item deleted success", $this->success);
        }
    }

    public function imports(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'item_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate item import", $this->unauthorized);
        }

        Excel::import(new ItemImport, request()->file('item_file'));
        return prepareResult(true, [], [], "Item successfully imported", $this->success);
    }

    public function getmappingfield()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $mappingarray = array("Item Code", "Item Name", "Item Description", "Barcode", "Weight", "Shelf Life", "Tax Apply", "Item Uom", "Lower Unit Item UPC", "Lower Unit Item Price", "Vat Percentage", "Stock Keeping Unit", "VolumeÂ (ltr)", "Item Major Category", "Item Sub Category", "Item Group", "Brand", "Sub Brand", "LOB", "Secondary UOM", "Secondary Item UPC", "Secondary UOM Price", "Item Stock Keeping Unit", "Item Status", "Promotional", "Product Catalog", "Item Image", "Net Weight", "Flawer", "Shelf File", "Ingredients", "Energy", "Fat", "Protein", "Carbohydrate", "Calcium", "Sodium", "Potassium", "Crude Fibre", "Vitamin", "Catalog Image", "ERP Code");

        return prepareResult(true, $mappingarray, [], "Complaint Feedback Mapping Field.", $this->success);
    }

    public function import(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $validator = Validator::make($request->all(), [
            'item_file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            $error = $validator->messages()->first();
            return prepareResult(false, [], $error, "Failed to validate complaint feedback import", $this->unauthorized);
        }
        $errors = array();
        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);
            $file = request()->file('item_file')->store('import');
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
            $import = new ItemImport($request->skipduplicate, $map_key_value_array, $heading_array);
            $import->import($file);

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
                            $error_result = array();
                            $error_row_loop = 0;
                            foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
                                $error_result[$map_key_value_array_value] = isset($failure->values()[$error_row_loop]) ? $failure->values()[$error_row_loop] : '';
                                $error_row_loop++;
                            }
                            $errror_array[] = array(
                                'errormessage' => "There was an error on row " . $failure->row() . ". " . $error_msg,
                                'errorresult' => $error_result, //$failure->values(),
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
        return prepareResult(true, $result, $errors, "item successfully imported", $this->success);
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
                foreach ($finaldata as $rk => $row) :
                    if ($row[0] == "Item Code") {
                        continue;
                    }

                    $ItemUom = ItemUom::where('name', $row[7])->first();
                    if (!is_object($ItemUom)) {
                        $ItemUom = new ItemUom;
                        $ItemUom->name = $row[7];
                        $ItemUom->save();
                    }

                    $ItemMajorCategory = ItemMajorCategory::where('name', $row[13])->first();
                    if (!is_object($ItemMajorCategory)) {
                        if (isset($row[13]) && $row[13] != '') {
                            $ItemMajorCategory = new ItemMajorCategory;
                            $ItemMajorCategory->name = $row[13];
                            $ItemMajorCategory->save();
                        }
                    }

                    $ItemSubCategory = ItemMajorCategory::where('name', $row[14])->first();
                    if (!is_object($ItemSubCategory)) {
                        if (isset($row[14]) && $row[14] != '') {
                            $ItemSubCategory = new ItemMajorCategory;
                            $ItemSubCategory->name = $row[14];
                            $ItemSubCategory->parent_id = $ItemMajorCategory->id;
                            $ItemSubCategory->node_level = $ItemMajorCategory->node_level + 1;
                            $ItemSubCategory->save();
                        }
                    }

                    $ItemGroup = ItemGroup::where('name', $row[15])->first();
                    if (!is_object($ItemGroup)) {
                        if (isset($row[15]) && $row[15] != '') {
                            $ItemGroup = new ItemGroup;
                            $ItemGroup->name = $row[15];
                            $ItemGroup->code = "ITEMG0000" . $rk;
                            $ItemGroup->save();
                        }
                    }

                    $Brand = Brand::where('brand_name', $row[16])->first();
                    if (!is_object($Brand)) {
                        if (isset($row[16]) && $row[16] != '') {
                            $Brand = new Brand;
                            $Brand->brand_name = $row[16];
                            $Brand->save();
                        }
                    }

                    $subBrand = Brand::where('brand_name', $row[17])->first();
                    if (!is_object($subBrand)) {
                        if (isset($row[17]) && $row[17] != '') {
                            $subBrand = new Brand;
                            $subBrand->brand_name = $row[17];
                            $subBrand->parent_id = $Brand->id;
                            $subBrand->node_level = $Brand->node_level + 1;
                            $subBrand->save();
                        }
                    }

                    $SecondaryItemUom = ItemUom::where('name', $row[19])->first();
                    if (!is_object($SecondaryItemUom)) {
                        if (isset($row[19]) && $row[19] != '') {
                            $SecondaryItemUom = new ItemUom;
                            $SecondaryItemUom->name = $row[19];
                            $SecondaryItemUom->code = "IUOM0000" . $rk;
                            $SecondaryItemUom->status = 1;
                            $SecondaryItemUom->save();
                        }
                    }

                    $item = Item::where('item_code', $row[0])->first();
                    $organisation_id = request()->user()->organisation_id;
                    $skipduplicate = $request->skipduplicate;

                    if (isset($subBrand->id) && $subBrand->id) {
                        $brand_id = $subBrand->id;
                    } else {
                        $brand_id = $Brand->id;
                    }

                    if (isset($ItemSubCategory->id) && $ItemSubCategory->id) {
                        $category_id = $ItemSubCategory->id;
                    } else {
                        $category_id = $ItemMajorCategory->id;
                    }

                    if ($skipduplicate == 1) {
                        if (isset($item->id) && $item->id) {
                            continue;
                        }
                        $saveItem = $this->saveItem(
                            1,
                            $row,
                            $organisation_id,
                            $category_id,
                            (is_object($ItemGroup)) ? $ItemGroup->id : NULL,
                            $brand_id,
                            (is_object($ItemUom)) ? $ItemUom->id : NULL,
                            (is_object($SecondaryItemUom)) ? $SecondaryItemUom->id : NULL
                        );
                        if (!isset($saveItem)) {
                            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    } else {
                        $saveItem = $this->saveItem(
                            0,
                            $row,
                            $organisation_id,
                            $category_id,
                            (is_object($ItemGroup)) ? $ItemGroup->id : NULL,
                            $brand_id,
                            (is_object($ItemUom)) ? $ItemUom->id : NULL,
                            (is_object($SecondaryItemUom)) ? $SecondaryItemUom->id : NULL
                        );
                        if (!isset($saveItem)) {
                            return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
                        }
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "Item successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    public function newLunchIndex(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $item = Item::with(
            'itemUomLowerUnit:id,name,code',
            'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price,purchase_order_price,stock_keeping_unit',
            'ItemMainPrice.itemUom:id,name,code',
            'itemMajorCategory:id,uuid,name',
            'itemGroup:id,uuid,name,code,status',
            'brand:id,uuid,brand_name,status',
            'productCatalog'
        )
            ->where('new_lunch', 1)
            ->where('current_stage', 'Approved')
            ->WhereDate('end_date', '>=', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        $item_array = array();
        if (is_object($item)) {
            foreach ($item as $key => $item1) {
                $item_array[] = $item[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($item_array[$offset])) {
                    $data_array[] = $item_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($item_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($item_array);
        } else {
            $data_array = $item_array;
        }

        return prepareResult(true, $data_array, [], "New Lunch Item listing", $this->success, $pagination);
    }

    private function saveItem(
        $is_skip,
        $row,
        $organisation_id,
        $ItemMajorCategory,
        $ItemGroup,
        $Brand,
        $ItemUom,
        $SecondaryItemUom
    ) {
        \DB::beginTransaction();
        try {
            if (isset($is_skip)) {
                $item = Item::where('item_code', $row[0])->first();
                if (!is_object($item)) {
                    $item = new Item;
                }
            } else {
                $item = Item::where('item_code', $row[0])->first();
                if (!is_object($item)) {
                    $item = new Item;
                }
            }

            $lob = Lob::where('name', 'like', '%' . $row[18] . '%')->first();

            $is_promotional = 0;
            $is_product_catalog = 0;
            $stock_keeping_unit = 0;
            $item->organisation_id  = $organisation_id;
            $item->item_major_category_id = $ItemMajorCategory;
            $item->item_group_id  = $ItemGroup;
            $item->brand_id = $Brand;
            $item->lob_id = (is_object($lob)) ? $lob->id : null;
            if (isset($row[24]) && $row[24] == "Yes") {
                $is_promotional = 1;
            }
            if (isset($row[23]) && $row[23] == "Yes") {
                $status = 1;
            }
            if (isset($row[25]) && $row[25] == "Yes") {
                $is_product_catalog = 1;
            }
            if (isset($row[11]) && $row[11] == "Yes") {
                $stock_keeping_unit = 1;
            }
            $item->item_code = $row[0];
            $item->item_name = $row[1];
            $item->item_description = $row[2];
            $item->item_barcode = $row[3];
            $item->item_weight = $row[4];
            $item->item_shelf_life = $row[5];
            $item->volume = $row[12];
            $item->lower_unit_item_upc = $row[8];
            $item->lower_unit_uom_id = $ItemUom;
            $item->lower_unit_item_price = $row[9];
            $item->item_vat_percentage = $row[10];
            $item->stock_keeping_unit = $stock_keeping_unit;
            $item->status = (isset($status)) ? 1 : 0;
            $item->is_promotional = $is_promotional;
            $item->is_product_catalog = $is_product_catalog;
            $item->item_image = $row[27];
            $item->erp_code = $row[41];
            $item->current_stage = "Approved";
            $item->save();

            if (isset($is_skip)) {
                $item_main_price = new ItemMainPrice;
            } else {
                $item_main_price = ItemMainPrice::where('item_id', $item->id)
                    ->where('item_uom_id', $SecondaryItemUom)
                    ->first();
                if (!is_object($item_main_price)) {
                    $item_main_price = new ItemMainPrice;
                }
            }

            $item_main_price->item_id = $item->id;
            $item_main_price->item_upc = $row[20];
            $item_main_price->item_uom_id = $SecondaryItemUom;
            $item_main_price->item_price = $row[21];
            $item_main_price->stock_keeping_unit = ($row[22] == "Yes") ? 1 : 0;
            if ($row[20] != '' && $row[21] != '' && $row[22] != '') {
                $item_main_price->save();
            }

            if (isset($row[25]) && $row[25] == "Yes") {
                if (isset($is_skip)) {
                    $product_catalogs = new ProductCatalog;
                } else {
                    $product_catalogs = ProductCatalog::where('item_id', $item->id)
                        ->first();
                    if (is_object($product_catalogs)) {
                        $product_catalogs->delete();
                        $product_catalogs = new ProductCatalog;
                    }
                }
            } else {
                $product_catalogs = ProductCatalog::where('item_id', $item->id)->first();
                if (is_object($product_catalogs)) {
                    $product_catalogs->delete();
                }
            }

            if (isset($row[25]) && $row[25] == "Yes") {
                $product_catalogs->organisation_id  = $organisation_id;
                $product_catalogs->item_id = $item->id;
                $product_catalogs->net_weight = $row[26];
                $product_catalogs->flawer = $row[28];
                $product_catalogs->shelf_file = $row[29];
                $product_catalogs->ingredients = $row[30];
                $product_catalogs->energy = $row[31];
                $product_catalogs->fat = $row[32];
                $product_catalogs->protein = $row[33];
                $product_catalogs->carbohydrate = $row[34];
                $product_catalogs->calcium = $row[35];
                $product_catalogs->sodium = $row[36];
                $product_catalogs->potassium = $row[37];
                $product_catalogs->crude_fibre = $row[38];
                $product_catalogs->vitamin = $row[39];
                $product_catalogs->image_string = $row[40];
                $product_catalogs->save();
            }

            \DB::commit();
            return prepareResult(true, [], [], "", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }
}
