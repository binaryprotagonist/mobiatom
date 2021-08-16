<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\CombinationMaster;
use App\Model\CombinationPlanKey;
use App\Model\CustomerInfo;
use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\PDPArea;
use App\Model\PDPChannel;
use App\Model\PDPCountry;
use App\Model\PDPCustomer;
use App\Model\PDPCustomerCategory;
use App\Model\PDPDiscountSlab;
use App\Model\PDPItem;
use App\Model\PDPItemGroup;
use App\Model\PDPItemMajorCategory;
use App\Model\PDPItemSubCategory;
use App\Model\PDPPromotionItem;
use App\Model\PDPPromotionOfferItem;
use App\Model\PDPRegion;
use App\Model\PDPRoute;
use App\Model\PDPSalesOrganisation;
use App\Model\PriceDiscoPromoPlan;
use App\Model\Route;
use App\User;
use DB;
use Illuminate\Http\Request;

class PriceDiscoPromoController extends Controller
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

        $use_for = $request->use_for;

        $price_discon_promo_plan_query = PriceDiscoPromoPlan::where('use_for', $use_for);

        if ($request->name) {
            $price_discon_promo_plan_query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->start_date) {
            $price_discon_promo_plan_query->whereDate('start_date', date('Y-m-d', strtotime($request->start_date)));
        }

        if ($request->end_date) {
            $price_discon_promo_plan_query->whereDate('end_date', date('Y-m-d', strtotime($request->end_date)));
        }

        $price_discon_promo_plan = $price_discon_promo_plan_query->orderBy('id', 'desc')->get();

        $price_discon_promo_plan_array = array();
        if (is_object($price_discon_promo_plan)) {
            foreach ($price_discon_promo_plan as $key => $price_discon_promo_plan1) {
                $price_discon_promo_plan_array[] = $price_discon_promo_plan[$key];
            }
        }

        $data_array = array();
        $page = (isset($request->page)) ? $request->page : '';
        $limit = (isset($request->page_size)) ? $request->page_size : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($price_discon_promo_plan_array[$offset])) {
                    $data_array[] = $price_discon_promo_plan_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($price_discon_promo_plan_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($price_discon_promo_plan_array);
        } else {
            $data_array = $price_discon_promo_plan_array;
        }

        return prepareResult(true, $data_array, [], "listing", $this->success, $pagination);

        // $price_discon_promo_plan = PriceDiscoPromoPlan::with(
        //     'PDPCountries:id,uuid,price_disco_promo_plan_id,country_id',
        //     'PDPCountries.country:id,uuid,name,currency,currency_code,currency_symbol,status',
        //     'PDPRegions:id,uuid,price_disco_promo_plan_id,region_id',
        //     'PDPRegions.region:id,uuid,region_code,region_name,region_status',
        //     'PDPAreas:id,uuid,price_disco_promo_plan_id,area_id',
        //     'PDPAreas.area:id,uuid,depot_id,area_name,area_manager,area_manager_contact,status',
        //     'PDPSubAreas:id,uuid,price_disco_promo_plan_id,sub_area_id',
        //     'PDPSubAreas.subArea:id,uuid,subarea_code,subarea_name,status',
        //     'PDPRoutes:id,uuid,price_disco_promo_plan_id,route_id',
        //     'PDPRoutes.route:id,uuid,route_code,route_name,status',
        //     'PDPSalesOrganisations:id,uuid,price_disco_promo_plan_id,sales_organisation_id',
        //     'PDPSalesOrganisations.customerInfos.user:id,uuid,firstname,lastname,email',
        //     'PDPChannels:id,uuid,price_disco_promo_plan_id,channel_id',
        //     'PDPChannels.channel:id,uuid,code,name,status',
        //     'PDPSubChannels:id,uuid,price_disco_promo_plan_id,sub_channel_id',
        //     'PDPSubChannels.subChannel:id,uuid,name,code,status,channel_id',
        //     'PDPCustomerCategories:id,uuid,price_disco_promo_plan_id,customer_category_id',
        //     'PDPCustomerCategories.customerInfo.customerCategory:id,uuid,customer_category_code,customer_category_name,status',
        //     'PDPCustomers:id,uuid,price_disco_promo_plan_id,customer_id',
        //     'PDPCustomers.customerInfo.user:id,uuid,firstname,lastname',
        //     'PDPItemMajorCategories:id,uuid,price_disco_promo_plan_id',
        //     'PDPItemMajorCategories.itemMajorCategory:id,uuid,name,code',
        //     'PDPItemSubCategories:id,uuid,price_disco_promo_plan_id,item_sub_category_id',
        //     'PDPItemSubCategories.itemSubCategory:id,uuid,item_major_category_id,code,name,status',
        //     'PDPItemGroups:id,uuid,price_disco_promo_plan_id,item_group_id',
        //     'PDPItemGroups.itemGroup:id,uuid,name,code,status',
        //     'PDPItems:id,uuid,price_disco_promo_plan_id,item_id',
        //     'PDPItems.item:id,uuid,item_name,item_code,status',
        //     'PDPPromotionItems:id,uuid,price_disco_promo_plan_id,item_uom_id,item_qty,price',
        //     'PDPPromotionItems.item:id,uuid,item_code,item_name',
        //     'PDPPromotionItems.itemUom:id,uuid,name,code,status',
        //     'PDPPromotionOfferItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,offered_qty',
        //     'PDPPromotionOfferItems.item:id,uuid,item_code,item_name',
        //     'PDPPromotionOfferItems.itemUom:id,uuid,name,code,status'
        // )
        // ->get();

        return prepareResult(true, $data_array, [], "listing", $this->success);
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
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating pricing plan", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {
            if (is_null($request->combination_plan_key_id) && empty($request->combination_plan_key_id)) {

                $combination_keys = CombinationMaster::whereIn('name', $request->combination_key_value)->get();
                $name = $combination_keys->pluck('name')->toArray();
                $key_codes = $combination_keys->pluck('id')->toArray();

                $combination_plan_keys = new CombinationPlanKey;
                $combination_plan_keys->combination_key_name = implode(" ", $name);
                $combination_plan_keys->combination_key = implode('/', $name);
                $combination_plan_keys->combination_key_code = implode('/', $key_codes);
                $combination_plan_keys->status = 1;
                $combination_plan_keys->save();
            }

            $price_discon_promo_plan = new PriceDiscoPromoPlan;
            if (isset($combination_plan_keys->id) && $combination_plan_keys->id) {
                $price_discon_promo_plan->combination_plan_key_id = $combination_plan_keys->id;
            } else {
                $price_discon_promo_plan->combination_plan_key_id = $request->combination_plan_key_id;
            }
            $price_discon_promo_plan->use_for = $request->use_for;
            $price_discon_promo_plan->name = $request->name;
            $price_discon_promo_plan->start_date = $request->start_date;
            $price_discon_promo_plan->end_date = $request->end_date;
            $price_discon_promo_plan->combination_key_value = implode('/', $request->combination_key_value);

            if ($request->use_for == 'Promotion') {
                $price_discon_promo_plan->order_item_type = $request->order_item_type;
                $price_discon_promo_plan->offer_item_type = $request->offer_item_type;
            }

            if ($request->use_for == 'Discount') {
                $price_discon_promo_plan->type = $request->type;
                $price_discon_promo_plan->qty_from = $request->qty_from;
                $price_discon_promo_plan->qty_to = $request->qty_to;
                $price_discon_promo_plan->discount_type = $request->discount_type;
                $price_discon_promo_plan->discount_apply_on = (!empty($request->discount_apply_on)) ? $request->discount_apply_on : "0";
                $price_discon_promo_plan->discount_value = (!empty($request->discount_value)) ? $request->discount_value : "0.00";
                $price_discon_promo_plan->discount_percentage = $request->discount_percentage;
            }

            $price_discon_promo_plan->priority_sequence = count($request->combination_key_value);
            $price_discon_promo_plan->status = $request->status;
            $price_discon_promo_plan->discount_main_type = (!empty($request->discount_main_type)) ? $request->discount_main_type : 0;

            $price_discon_promo_plan->save();

            if (is_array($request->country_ids) && sizeof($request->country_ids) >= 1) {
                $this->dataAdd($request->country_ids, 'PDPCountry', $price_discon_promo_plan->id, 'country_id');
            }

            if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
                $this->dataAdd($request->region_ids, 'PDPRegion', $price_discon_promo_plan->id, 'region_id');
            }

            if (is_array($request->area_ids) && sizeof($request->area_ids) >= 1) {
                $this->dataAdd($request->area_ids, 'PDPArea', $price_discon_promo_plan->id, 'area_id');
            }

            if (is_array($request->route_ids) && sizeof($request->route_ids) >= 1) {
                $this->dataAdd($request->route_ids, 'PDPRoute', $price_discon_promo_plan->id, 'route_id');
            }
            if (is_array($request->sales_organisation_ids) && sizeof($request->sales_organisation_ids) >= 1) {
                $this->dataAdd($request->sales_organisation_ids, 'PDPSalesOrganisation', $price_discon_promo_plan->id, 'sales_organisation_id');
            }

            if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
                $this->dataAdd($request->channel_ids, 'PDPChannel', $price_discon_promo_plan->id, 'channel_id');
            }

            if (is_array($request->customer_category_ids) && sizeof($request->customer_category_ids) >= 1) {
                $this->dataAdd($request->customer_category_ids, 'PDPCustomerCategory', $price_discon_promo_plan->id, 'customer_category_id');
            }

            if (is_array($request->customer_ids) && sizeof($request->customer_ids) >= 1) {
                $this->dataAdd($request->customer_ids, 'PDPCustomer', $price_discon_promo_plan->id, 'customer_id');
            }

            if (is_array($request->item_major_category_ids) && sizeof($request->item_major_category_ids) >= 1) {
                $this->dataAdd($request->item_major_category_ids, 'PDPItemMajorCategory', $price_discon_promo_plan->id, 'item_major_category_id');
            }

            if (is_array($request->item_group_ids) && sizeof($request->item_group_ids) >= 1) {
                $this->dataAdd($request->item_group_ids, 'PDPItemGroup', $price_discon_promo_plan->id, 'item_group_id');
            }

            if ($request->use_for == 'Discount') {
                if (is_array($request->slabs) && sizeof($request->slabs) >= 1) {
                    foreach ($request->slabs as $slab) {
                        //save PDPDiscountSlab
                        $pdp_discount_slab = new PDPDiscountSlab;
                        $pdp_discount_slab->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_discount_slab->min_slab = $slab['min_slab'];
                        $pdp_discount_slab->max_slab = $slab['max_slab'];
                        $pdp_discount_slab->value = $slab['value'];
                        $pdp_discount_slab->percentage = $slab['percentage'];
                        $pdp_discount_slab->save();
                    }
                }
            }


            // if ($request->use_for == 'Pricing' || $request->use_for == 'Discount') {
            if ($request->use_for == 'Pricing') {
                if (is_array($request->item_ids) && sizeof($request->item_ids) >= 1) {
                    foreach ($request->item_ids as $item) {
                        //save PDPItem
                        $pdp_item = new PDPItem;
                        $pdp_item->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_item->item_id = $item['item_id'];
                        $pdp_item->item_uom_id = (!empty($item['item_uom_id'])) ? $item['item_uom_id'] : null;
                        $pdp_item->price = (!empty($item['price'])) ? $item['price'] : null;
                        $pdp_item->save();
                    }
                }
            }

            if ($request->use_for == 'Discount') {
                if ($request->discount_main_type == 1) {
                    if (is_array($request->item_ids) && sizeof($request->item_ids) >= 1) {
                        foreach ($request->item_ids as $item) {
                            //save PDPItem
                            $pdp_item = new PDPItem;
                            $pdp_item->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                            $pdp_item->item_id = $item['item_id'];
                            $pdp_item->item_uom_id = (!empty($item['item_uom_id'])) ? $item['item_uom_id'] : null;
                            $pdp_item->price = (!empty($item['price'])) ? $item['price'] : null;
                            $pdp_item->save();
                        }
                    }
                }
            }

            if ($request->use_for == 'Promotion') {
                if (is_array($request->promotion_items) && sizeof($request->promotion_items) >= 1) {
                    foreach ($request->promotion_items as $key => $promotion_item) {
                        $pdp_promotion_item = new PDPPromotionItem;
                        $pdp_promotion_item->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_promotion_item->item_id = $promotion_item['item_id'];
                        $pdp_promotion_item->item_uom_id = $promotion_item['item_uom_id'];
                        $pdp_promotion_item->item_qty = $promotion_item['item_qty'];
                        $pdp_promotion_item->price = $promotion_item['price'];
                        $pdp_promotion_item->save();
                    }
                }

                if (is_array($request->promotion_offer_items) && sizeof($request->promotion_offer_items) >= 1) {
                    foreach ($request->promotion_offer_items as $key => $promotion_offer_items) {
                        $pdp_promotion_offer_items = new PDPPromotionOfferItem;
                        $pdp_promotion_offer_items->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_promotion_offer_items->item_id = $promotion_offer_items['item_id'];
                        $pdp_promotion_offer_items->item_uom_id = $promotion_offer_items['item_uom_id'];
                        $pdp_promotion_offer_items->offered_qty = $promotion_offer_items['offered_qty'];
                        $pdp_promotion_offer_items->save();
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $price_discon_promo_plan, [], $price_discon_promo_plan->use_for . " added successfully", $this->success);
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
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $price_discon_promo_plan = PriceDiscoPromoPlan::where('uuid', $uuid)
            ->with(
                'PDPCountries:id,uuid,price_disco_promo_plan_id,country_id',
                'PDPCountries.country:id,uuid,name,currency,currency_code,currency_symbol,status',
                'PDPRegions:id,uuid,price_disco_promo_plan_id,region_id',
                'PDPRegions.region:id,uuid,region_code,region_name,region_status',
                'PDPAreas:id,uuid,price_disco_promo_plan_id,area_id',
                'PDPAreas.area:id,uuid,area_name,status',
                'PDPRoutes:id,uuid,price_disco_promo_plan_id,route_id',
                'PDPRoutes.route:id,uuid,route_code,route_name,status',
                'PDPSalesOrganisations:id,uuid,price_disco_promo_plan_id,sales_organisation_id',
                'PDPSalesOrganisations.salesOrganisation.customerInfos.user:id,uuid,firstname,lastname,email',
                'PDPChannels:id,uuid,price_disco_promo_plan_id,channel_id',
                'PDPChannels.channel:id,uuid,name,status',
                'PDPCustomerCategories:id,uuid,price_disco_promo_plan_id,customer_category_id',
                'PDPCustomerCategories.customerCategory:id,uuid,customer_category_code,customer_category_name,status',
                'PDPCustomers:id,uuid,price_disco_promo_plan_id,customer_id',
                'PDPCustomers.customerInfo.user:id,uuid,firstname,lastname',
                'PDPItemMajorCategories:id,uuid,price_disco_promo_plan_id,item_major_category_id',
                'PDPItemMajorCategories.itemMajorCategory:id,uuid,name',
                'PDPItemGroups:id,uuid,price_disco_promo_plan_id,item_group_id',
                'PDPItemGroups.itemGroup:id,uuid,name,code,status',
                'PDPItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,price',
                'PDPItems.item:id,uuid,item_name,item_code,item_description,status',
                'PDPItems.itemUom:id,uuid,name,code,status',
                'PDPPromotionItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,item_qty,price',
                'PDPPromotionItems.item:id,uuid,item_code,item_name',
                'PDPPromotionItems.itemUom:id,uuid,name,code,status',
                'PDPPromotionOfferItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,offered_qty',
                'PDPPromotionOfferItems.item:id,uuid,item_code,item_name',
                'PDPPromotionOfferItems.itemUom:id,uuid,name,code,status',
                'PDPDiscountSlabs:id,price_disco_promo_plan_id,min_slab,max_slab,value,percentage'
            )
            ->first();

        if (!is_object($price_discon_promo_plan)) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unprocessableEntity);
        }

        return prepareResult(true, $price_discon_promo_plan, [], $price_discon_promo_plan->use_for . " code Edit", $this->success);
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
            return prepareResult(true, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating outlet product code", $this->unprocessableEntity);
        }
        \DB::beginTransaction();
        try {
            $price_discon_promo_plan = PriceDiscoPromoPlan::where('uuid', $uuid)
                ->first();

            if (!is_object($price_discon_promo_plan)) {
                return prepareResult(false, [], [], "Oops!!!, something went wrong, please try again.", $this->unauthorized);
            }

            PDPArea::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPChannel::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPCountry::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPCustomer::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPCustomerCategory::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPItem::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPItemGroup::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPItemMajorCategory::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPPromotionItem::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPPromotionOfferItem::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPDiscountSlab::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPRegion::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPRoute::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();
            PDPSalesOrganisation::where('price_disco_promo_plan_id', $price_discon_promo_plan->id)->delete();

            if (is_null($request->combination_plan_key_id) && empty($request->combination_plan_key_id)) {

                $combination_keys = CombinationMaster::whereIn('name', $request->combination_key_value)->get();

                $name = $combination_keys->pluck('name')->toArray();
                $key_codes = $combination_keys->pluck('id')->toArray();

                $combination_plan_keys = new CombinationPlanKey;
                $combination_plan_keys->combination_key_name = implode(" ", $name);
                $combination_plan_keys->combination_key = implode('/', $name);
                $combination_plan_keys->combination_key_code = implode('/', $key_codes);
                $combination_plan_keys->status = 1;
                $combination_plan_keys->save();
            }

            if (isset($combination_plan_keys->id) && $combination_plan_keys->id) {
                $price_discon_promo_plan->combination_plan_key_id = $combination_plan_keys->id;
            } else {
                $price_discon_promo_plan->combination_plan_key_id = $request->combination_plan_key_id;
            }

            $price_discon_promo_plan->use_for = $request->use_for;
            $price_discon_promo_plan->name = $request->name;
            $price_discon_promo_plan->start_date = $request->start_date;
            $price_discon_promo_plan->end_date = $request->end_date;
            $price_discon_promo_plan->combination_key_value = implode('/', $request->combination_key_value);

            if ($request->use_for == 'Promotion') {
                $price_discon_promo_plan->order_item_type = $request->order_item_type;
                $price_discon_promo_plan->offer_item_type = $request->offer_item_type;
            }

            if ($request->use_for == 'Discount') {
                $price_discon_promo_plan->type = $request->type;
                $price_discon_promo_plan->qty_from = $request->qty_from;
                $price_discon_promo_plan->qty_to = $request->qty_to;
                $price_discon_promo_plan->discount_type = $request->discount_type;
                $price_discon_promo_plan->discount_apply_on = (!empty($request->discount_apply_on)) ? $request->discount_apply_on : "0";
                $price_discon_promo_plan->discount_value = (!empty($request->discount_value)) ? $request->discount_value : "0.00";
                $price_discon_promo_plan->discount_percentage = $request->discount_percentage;
            }

            $price_discon_promo_plan->priority_sequence = $request->priority_sequence;
            $price_discon_promo_plan->status = $request->status;
            $price_discon_promo_plan->discount_main_type = (!empty($request->discount_main_type)) ? $request->discount_main_type : 0;

            $price_discon_promo_plan->save();

            if (is_array($request->country_ids) && sizeof($request->country_ids) >= 1) {
                $this->dataAdd($request->country_ids, 'PDPCountry', $price_discon_promo_plan->id, 'country_id');
            }

            if (is_array($request->region_ids) && sizeof($request->region_ids) >= 1) {
                $this->dataAdd($request->region_ids, 'PDPRegion', $price_discon_promo_plan->id, 'region_id');
            }

            if (is_array($request->area_ids) && sizeof($request->area_ids) >= 1) {
                $this->dataAdd($request->area_ids, 'PDPArea', $price_discon_promo_plan->id, 'area_id');
            }

            if (is_array($request->route_ids) && sizeof($request->route_ids) >= 1) {
                $this->dataAdd($request->route_ids, 'PDPRoute', $price_discon_promo_plan->id, 'route_id');
            }

            if (is_array($request->sales_organisation_ids) && sizeof($request->sales_organisation_ids) >= 1) {
                $this->dataAdd($request->sales_organisation_ids, 'PDPSalesOrganisation', $price_discon_promo_plan->id, 'sales_organisation_id');
            }

            if (is_array($request->channel_ids) && sizeof($request->channel_ids) >= 1) {
                $this->dataAdd($request->channel_ids, 'PDPChannel', $price_discon_promo_plan->id, 'channel_id');
            }

            if (is_array($request->customer_category_ids) && sizeof($request->customer_category_ids) >= 1) {
                $this->dataAdd($request->customer_category_ids, 'PDPCustomerCategory', $price_discon_promo_plan->id, 'customer_category_id');
            }

            if (is_array($request->customer_ids) && sizeof($request->customer_ids) >= 1) {
                $this->dataAdd($request->customer_ids, 'PDPCustomer', $price_discon_promo_plan->id, 'customer_id');
            }

            if (is_array($request->item_major_category_ids) && sizeof($request->item_major_category_ids) >= 1) {
                $this->dataAdd($request->item_major_category_ids, 'PDPItemMajorCategory', $price_discon_promo_plan->id, 'item_major_category_id');
            }

            // if (is_array($request->item_sub_category_ids) && sizeof($request->item_sub_category_ids) >= 1) {
            //     $this->dataAdd($request->item_sub_category_ids, 'PDPItemMajorCategory', $price_discon_promo_plan->id, 'item_sub_category_id');
            // }

            if (is_array($request->item_group_ids) && sizeof($request->item_group_ids) >= 1) {
                $this->dataAdd($request->item_group_ids, 'PDPItemGroup', $price_discon_promo_plan->id, 'item_group_id');
            }

            if ($request->use_for == 'Discount') {
                if (is_array($request->slabs) && sizeof($request->slabs) >= 1) {
                    foreach ($request->slabs as $slab) {
                        //save PDPDiscountSlab
                        $pdp_discount_slab = new PDPDiscountSlab;
                        $pdp_discount_slab->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_discount_slab->min_slab = $slab['min_slab'];
                        $pdp_discount_slab->max_slab = $slab['max_slab'];
                        $pdp_discount_slab->value = $slab['value'];
                        $pdp_discount_slab->percentage = $slab['percentage'];
                        $pdp_discount_slab->save();
                    }
                }
            }

            if ($request->use_for == 'Pricing') {
                if (is_array($request->item_ids) && sizeof($request->item_ids) >= 1) {
                    foreach ($request->item_ids as $item) {
                        //save PDPItem
                        $pdp_item = new PDPItem;
                        $pdp_item->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_item->item_id = $item['item_id'];
                        $pdp_item->item_uom_id = (!empty($item['item_uom_id'])) ? $item['item_uom_id'] : null;
                        $pdp_item->price = (!empty($item['price'])) ? $item['price'] : null;
                        $pdp_item->save();
                    }
                }
            }

            if ($request->use_for == 'Discount') {
                if ($request->discount_main_type == 1) {
                    if (is_array($request->item_ids) && sizeof($request->item_ids) >= 1) {
                        foreach ($request->item_ids as $item) {
                            //save PDPItem
                            $pdp_item = new PDPItem;
                            $pdp_item->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                            $pdp_item->item_id = $item['item_id'];
                            $pdp_item->item_uom_id = (!empty($item['item_uom_id'])) ? $item['item_uom_id'] : null;
                            $pdp_item->price = (!empty($item['price'])) ? $item['price'] : null;
                            $pdp_item->save();
                        }
                    }
                }
            }



            if ($request->use_for == 'Promotion') {
                if (is_array($request->promotion_items) && sizeof($request->promotion_items) >= 1) {
                    foreach ($request->promotion_items as $key => $promotion_item) {
                        $pdp_promotion_item = new PDPPromotionItem;
                        $pdp_promotion_item->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_promotion_item->item_id = $promotion_item['item_id'];
                        $pdp_promotion_item->item_uom_id = $promotion_item['item_uom_id'];
                        $pdp_promotion_item->item_qty = $promotion_item['item_qty'];
                        $pdp_promotion_item->price = $promotion_item['price'];
                        $pdp_promotion_item->save();
                    }
                }


                if (is_array($request->promotion_offer_items) && sizeof($request->promotion_offer_items) >= 1) {
                    foreach ($request->promotion_offer_items as $key => $promotion_offer_items) {
                        $pdp_promotion_offer_items = new PDPPromotionOfferItem;
                        $pdp_promotion_offer_items->price_disco_promo_plan_id = $price_discon_promo_plan->id;
                        $pdp_promotion_offer_items->item_id = $promotion_offer_items['item_id'];
                        $pdp_promotion_offer_items->item_uom_id = $promotion_offer_items['item_uom_id'];
                        $pdp_promotion_offer_items->offered_qty = $promotion_offer_items['offered_qty'];
                        $pdp_promotion_offer_items->save();
                    }
                }
            }

            \DB::commit();
            return prepareResult(true, $price_discon_promo_plan, [], $price_discon_promo_plan->use_for . " updated successfully", $this->success);
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
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy($uuid)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$uuid) {
            return prepareResult(false, [], [], "Error while validating", $this->unprocessableEntity);
        }

        $price_discon_promo_plan = PriceDiscoPromoPlan::where('uuid', $uuid)
            ->first();

        if (is_object($price_discon_promo_plan)) {
            $price_discon_promo_plan->delete();

            return prepareResult(true, [], [], "Record delete successfully", $this->success);
        }

        return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  array  $data
     * @param  object  $obj create new object
     * @param  int  $id pdp id
     * @param  int  $sub_key child key
     * @return \Illuminate\Http\Response
     */
    private function dataAdd($data, $obj, $id, $sub_key)
    {
        foreach ($data as $data_id) {
            if ($obj == 'PDPCustomer') {
                $customer_info = CustomerInfo::where('user_id', $data_id)->first();
                if (!is_object($customer_info)) {
                    $customer_info = CustomerInfo::where('id', $data_id)->first();
                }
            }

            $obj_data = 'App\\Model\\' . $obj;
            $pdp = new $obj_data;
            $pdp->price_disco_promo_plan_id = $id;
            //$pdp->$sub_key = $data_id;
            if ($obj == 'PDPCustomer') {
                $pdp->$sub_key = $customer_info->id;
            } else {
                $pdp->$sub_key = $data_id;
            }
            $pdp->save();
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'use_for' => 'required',
                'name' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                // 'priority_sequence' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "routeByPDP") {
            $validator = \Validator::make($input, [
                'route_id' => 'required|integer|exists:routes,id',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        if ($type == "pdpMobile") {
            $validator = \Validator::make($input, [
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                $error = true;
                $errors = $validator->errors();
            }
        }

        return ["error" => $error, "errors" => $errors];
    }

    public function routeApplyPriceDiscPromotion(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "routeWisePDP");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating route", $this->unprocessableEntity);
        }

        $p_d_p_route = PDPRoute::where('route_id', $request->route_id)
            ->with(
                'priceDiscoPromoPlan',
                'priceDiscoPromoPlan.PDPPromotionItems.item',
                'priceDiscoPromoPlan.PDPPromotionItems.itemUom',
                'priceDiscoPromoPlan.PDPPromotionOfferItems.item',
                'priceDiscoPromoPlan.PDPPromotionOfferItems.itemUom',
                'priceDiscoPromoPlan.PDPDiscountSlabs',
                'route'
            )
            ->orderBy('id', 'desc')
            ->get();

        return prepareResult(true, $p_d_p_route, [], "Route wise promotion successfully", $this->success);
    }

    public function pdpMobile($type)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        if (!$type) {
            return prepareResult(false, [], [], "Error while validating pdp", $this->unprocessableEntity);
        }

        $price_discon_promo_plan = PriceDiscoPromoPlan::where('use_for', $type)
            ->with(
                'PDPCountries:id,uuid,price_disco_promo_plan_id,country_id',
                'PDPCountries.country:id,uuid,name,currency,currency_code,currency_symbol,status',
                'PDPRegions:id,uuid,price_disco_promo_plan_id,region_id',
                'PDPRegions.region:id,uuid,region_code,region_name,region_status',
                'PDPAreas:id,uuid,price_disco_promo_plan_id,area_id',
                'PDPAreas.area:id,uuid,area_name,status',
                'PDPRoutes:id,uuid,price_disco_promo_plan_id,route_id',
                'PDPRoutes.route:id,uuid,route_code,route_name,status',
                'PDPSalesOrganisations:id,uuid,price_disco_promo_plan_id,sales_organisation_id',
                'PDPSalesOrganisations.salesOrganisation.customerInfos.user:id,uuid,firstname,lastname,email',
                'PDPChannels:id,uuid,price_disco_promo_plan_id,channel_id',
                'PDPChannels.channel:id,uuid,name,status',
                'PDPCustomerCategories:id,uuid,price_disco_promo_plan_id,customer_category_id',
                'PDPCustomerCategories.customerCategory:id,uuid,customer_category_code,customer_category_name,status',
                'PDPCustomers:id,uuid,price_disco_promo_plan_id,customer_id',
                'PDPCustomers.customerInfo.user:id,uuid,firstname,lastname',
                'PDPItemMajorCategories:id,uuid,price_disco_promo_plan_id,item_major_category_id',
                'PDPItemMajorCategories.itemMajorCategory:id,uuid,name',
                'PDPItemGroups:id,uuid,price_disco_promo_plan_id,item_group_id',
                'PDPItemGroups.itemGroup:id,uuid,name,code,status',
                'PDPItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,price',
                'PDPItems.item:id,uuid,item_name,item_code,item_description,status',
                'PDPItems.itemUom:id,uuid,name,code,status',
                'PDPPromotionItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,item_qty,price',
                'PDPPromotionItems.item:id,uuid,item_code,item_name',
                'PDPPromotionItems.itemUom:id,uuid,name,code,status',
                'PDPPromotionOfferItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,offered_qty',
                'PDPPromotionOfferItems.item:id,uuid,item_code,item_name',
                'PDPPromotionOfferItems.itemUom:id,uuid,name,code,status',
                'PDPDiscountSlabs:id,price_disco_promo_plan_id,min_slab,max_slab,value,percentage'
            )
            ->orderBy('id', 'desc')
            ->get();

        $price_discon_promo_plan_array = array();
        if (is_object($price_discon_promo_plan)) {
            foreach ($price_discon_promo_plan as $key => $price_discon_promo_plan1) {
                $price_discon_promo_plan_array[] = $price_discon_promo_plan[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();
        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($price_discon_promo_plan_array[$offset])) {
                    $data_array[] = $price_discon_promo_plan_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($price_discon_promo_plan_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($price_discon_promo_plan_array);
        } else {
            $data_array = $price_discon_promo_plan_array;
        }

        return prepareResult(true, $data_array, [], ucfirst($type) . " listing", $this->success, $pagination);
    }

    public function pdpMobileByRoute(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = request()->json()->all();
        $validate = $this->validations($input, "routeByPDP");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating route", $this->unprocessableEntity);
        }

        $route = Route::find($request->route_id);
        $type = $request->type;
        // route wise customer
        $customer_info = CustomerInfo::where('route_id', $route->id)->get();
        $pdpArray = array();
        $data_array = array();
        $data_array1 = array();

        if (count($customer_info)) {
            foreach ($customer_info as $cKye => $customer) {
                //Get Customer Info
                // same for all customer
                //Location
                $customerCountry = $customer->user->country_id; //1
                $customerRegion = $customer->region_id; //2
                $customerRoute = $customer->route_id; //4

                //Customer
                $getAreaFromRoute = Route::find($customerRoute);
                $customerArea = ($getAreaFromRoute) ? $getAreaFromRoute->area_id : null; //3
                $customerSalesOrganisation = $customer->sales_organisation_id; //5
                $customerChannel = $customer->channel_id; //6
                $customerCustomerCategory = $customer->customer_category_id; //7
                $customerCustomer = $customer->id; //8

                $pdp_customer = PDPCustomer::select('p_d_p_customers.id as p_d_p_customer_id', 'combination_plan_key_id', 'price_disco_promo_plan_id', 'combination_key_name', 'combination_key', 'combination_key_code', 'price_disco_promo_plans.priority_sequence', 'price_disco_promo_plans.use_for')
                    ->join('price_disco_promo_plans', function ($join) {
                        $join->on('p_d_p_customers.price_disco_promo_plan_id', '=', 'price_disco_promo_plans.id');
                    })
                    ->join('combination_plan_keys', function ($join) {
                        $join->on('price_disco_promo_plans.combination_plan_key_id', '=', 'combination_plan_keys.id');
                    })
                    ->where('customer_id', $customer->id)
                    ->where('price_disco_promo_plans.organisation_id', auth()->user()->organisation_id)
                    ->where('start_date', '<=', date('Y-m-d'))
                    ->where('end_date', '>=', date('Y-m-d'))
                    ->where('price_disco_promo_plans.use_for', $type)
                    ->where('price_disco_promo_plans.status', 1)
                    ->where('combination_plan_keys.status', 1)
                    ->orderBy('priority_sequence', 'ASC')
                    ->orderBy('combination_key_code', 'DESC')
                    ->get();

                if ($pdp_customer->count() > 0) {
                    $getKey = [];
                    $getDiscountKey = [];
                    foreach ($pdp_customer as $key => $filterPrice) {
                        $getKey[] = $this->makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $filterPrice->combination_key_code, $filterPrice->combination_key, $filterPrice->price_disco_promo_plan_id, $filterPrice->p_d_p_customer_id, $filterPrice->price, $filterPrice->priority_sequence);
                    }

                    $useThisItem = '';
                    $isPromotion = false;
                    $isDiscount = false;
                    $lastKey = '';
                    foreach ($getKey as $checking) {
                        $usePrice = false;
                        if (isset($checking['combination_key_code'])) {
                            foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                                $combination_actual_id = explode('/', $checking['combination_actual_id']);
                                $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);

                                if ($isFind) {
                                    $usePrice = true;
                                } else {
                                    $usePrice = false;
                                    break;
                                }

                                if ($usePrice) {
                                    $useThisItem = $checking['price_disco_promo_plan_id'];
                                    if ($checking['use_for'] == 'Discount') {
                                        $isDiscount = true;
                                        $lArr = explode('/', $checking['combination_key']);
                                        $lastKey = end($lArr);
                                    }

                                    if ($checking['use_for'] == 'Promotion') {
                                        $isPromotion = true;
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    if ($useThisItem) {
                        if ($isPromotion) {
                            $price_promotion_discount = PriceDiscoPromoPlan::with(
                                'PDPPromotionItems',
                                'PDPPromotionItems.item:id,item_name',
                                'PDPPromotionItems.itemUom:id,name',
                                'PDPPromotionOfferItems',
                                'PDPPromotionOfferItems.item:id,item_name',
                                'PDPPromotionOfferItems.itemUom:id,name'
                            )
                                ->where('id', $useThisItem)
                                ->first();
                        } else if ($isDiscount) {
                            if ($lastKey == 'Item Group') {
                                $price_promotion_discount = PriceDiscoPromoPlan::with(
                                    'PDPDiscountSlabs',
                                    'PDPItemGroups:id,price_disco_promo_plan_id,item_group_id'
                                )
                                    ->where('id', $useThisItem)
                                    ->first();

                                $items = '';
                                foreach ($price_promotion_discount->PDPItemGroups as $pdpgroup) {
                                    $items = Item::select('id', 'uuid', 'item_name', 'lower_unit_uom_id', 'lower_unit_item_upc')
                                        ->with('itemUomLowerUnit:id,name,code')
                                        ->where('item_group_id', $pdpgroup['item_group_id'])
                                        ->get();
                                }

                                $price_promotion_discount->p_d_p_items = $items;
                            } else if ($lastKey == 'Major Category') {
                                $price_promotion_discount = PriceDiscoPromoPlan::with(
                                    'PDPDiscountSlabs',
                                    'PDPItemMajorCategories:id,price_disco_promo_plan_id,item_major_category_id'
                                )
                                    ->where('id', $useThisItem)
                                    ->first();

                                $items = '';
                                foreach ($price_promotion_discount->PDPItemMajorCategories as $pdpgroup) {
                                    $items = Item::select('id', 'uuid', 'item_name', 'lower_unit_uom_id', 'lower_unit_item_upc')
                                        ->with('itemUomLowerUnit:id,name,code')
                                        ->where('item_major_category_id', $pdpgroup['item_major_category_id'])
                                        ->get();
                                }

                                $price_promotion_discount->p_d_p_items = $items;
                            } else {

                                $price_promotion_discount = PriceDiscoPromoPlan::with(
                                    'PDPDiscountSlabs',
                                    'PDPItems:id,price_disco_promo_plan_id,item_id,item_uom_id,price',
                                    'PDPItems.item:id,item_name,lower_unit_uom_id,lower_unit_item_upc',
                                    'PDPItems.itemUom:id,name'
                                )
                                    ->where('id', $useThisItem)
                                    ->first();
                            }
                        } else {
                            $price_promotion_discount = PriceDiscoPromoPlan::with('PDPItems:id,price_disco_promo_plan_id,item_id,item_uom_id,price', 'PDPItems.item:id,item_name', 'PDPItems.itemUom:id,name')
                                ->where('id', $useThisItem)
                                ->first();
                        }

                        $user = $customer->user()->select('id', 'firstname', 'lastname')->first();

                        $data_array[] = array(
                            'customer' => $user,
                            'pdp' => $price_promotion_discount
                        );
                    }
                } else {
                    // table query

                    $price_disc_promo_plan_query = PriceDiscoPromoPlan::with(
                        'combinationPlanKeyPricingPlain',
                        'PDPAreas:id,price_disco_promo_plan_id,area_id',
                        'PDPAreas.area:id,area_name',
                        'PDPRoutes:id,price_disco_promo_plan_id,route_id',
                        'PDPRoutes.route:id,route_name',
                        'PDPSalesOrganisations:id,price_disco_promo_plan_id,sales_organisation_id',
                        'PDPSalesOrganisations.salesOrganisation:id,name',
                        'PDPChannels:id,price_disco_promo_plan_id,channel_id',
                        'PDPChannels.channel:id,name',
                        'PDPRegions:id,price_disco_promo_plan_id,region_id',
                        'PDPRegions.region:id,region_name'
                    )
                        ->where('organisation_id', auth()->user()->organisation_id)
                        ->where('start_date', '<=', date('Y-m-d'))
                        ->where('end_date', '>=', date('Y-m-d'))
                        ->where('use_for', $type)
                        ->where('status', 1)
                        // ->whereHas('combinationPlanKeyPricing', function ($que) {
                        //     $que->where('status', 1);
                        // })
                        ->orderBy('priority_sequence', 'ASC');
                    // ->whereHas('combinationPlanKeyPricingPlain', function ($q) {
                    //     $q->orderBy('combination_key_code', 'DESC');
                    // });

                    $price_disc_promo_plan = $price_disc_promo_plan_query->get()
                        ->sortByDesc('combinationPlanKeyPricingPlain.combination_key_code');

                    $getKey = [];
                    $getDiscountKey = [];
                    $isDiscount = false;
                    $lastKey = '';

                    foreach ($price_disc_promo_plan as $key => $filterPrice) {
                        if (!in_array('Customer', explode('/', $filterPrice->combination_key_value))) {
                            $getKey[] = $this->makeKeyValue(
                                $customerCountry, // Customer Country
                                $customerRegion, // Customer Region
                                $customerArea, // Customer Are
                                $customerRoute,
                                $customerSalesOrganisation,
                                $customerChannel,
                                $customerCustomerCategory,
                                $customerCustomer,
                                $filterPrice->combinationPlanKeyPricingPlain->combination_key_code, // Combination code Route / Material
                                $filterPrice->combinationPlanKeyPricingPlain->combination_key,
                                $filterPrice->id,
                                $filterPrice->combinationPlanKeyPricingPlain->p_d_p_customer_id,
                                $filterPrice->combinationPlanKeyPricingPlain->price,
                                $filterPrice->combinationPlanKeyPricingPlain->priority_sequence
                            );
                        } else {
                            unset($price_disc_promo_plan[$key]);
                        }
                    }

                    $useThisItem = '';
                    $isPromotion = false;
                    foreach ($getKey as $checking) {
                        $usePrice = false;
                        if (isset($checking['combination_key_code'])) {
                            if (!in_array(8, explode('/', $checking['combination_key_code']))) {
                                foreach (explode('/', $checking['combination_key_code']) as $key => $combination) {
                                    $combination_actual_id = explode('/', $checking['combination_actual_id']);
                                    $isFind = $this->checkDataExistOrNot($combination, $combination_actual_id[$key], $checking['price_disco_promo_plan_id']);
                                    if ($isFind) {
                                        $usePrice = true;
                                    } else {
                                        $usePrice = false;
                                        break;
                                    }

                                    if ($usePrice) {
                                        $useThisItem = $checking['price_disco_promo_plan_id'];
                                        if ($checking['use_for'] == 'Discount') {
                                            $lArr = explode('/', $checking['combination_key']);
                                            $lastKey = end($lArr);
                                            $isDiscount = true;
                                        }

                                        if ($checking['use_for'] == 'Promotion') {
                                            $isPromotion = true;
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    if ($useThisItem) {
                        if ($isPromotion) {
                            $price_promotion_discount = PriceDiscoPromoPlan::with(
                                'PDPPromotionItems',
                                'PDPPromotionItems.item:id,item_name',
                                'PDPPromotionItems.itemUom:id,name',
                                'PDPPromotionOfferItems',
                                'PDPPromotionOfferItems.item:id,item_name',
                                'PDPPromotionOfferItems.itemUom:id,name'
                            )
                                ->where('id', $useThisItem)
                                ->first();
                        } else if ($isDiscount) {
                            if ($lastKey == 'Item Group') {
                                $price_promotion_discount = PriceDiscoPromoPlan::with(
                                    'PDPDiscountSlabs',
                                    'PDPItemGroups:id,price_disco_promo_plan_id,item_group_id'
                                )
                                    ->where('id', $useThisItem)
                                    ->first();

                                $items = '';
                                foreach ($price_promotion_discount->PDPItemGroups as $pdpgroup) {
                                    $items = Item::select('id', 'uuid', 'item_name', 'lower_unit_uom_id', 'lower_unit_item_upc')
                                        ->with('itemUomLowerUnit:id,name,code')
                                        ->where('item_group_id', $pdpgroup['item_group_id'])
                                        ->get();
                                }

                                $price_promotion_discount->p_d_p_items = $items;
                            } else if ($lastKey == 'Major Category') {
                                $price_promotion_discount = PriceDiscoPromoPlan::with(
                                    'PDPDiscountSlabs',
                                    'PDPItemMajorCategories:id,price_disco_promo_plan_id,item_major_category_id'
                                )
                                    ->where('id', $useThisItem)
                                    ->first();

                                $items = '';
                                foreach ($price_promotion_discount->PDPItemMajorCategories as $pdpgroup) {
                                    $items = Item::select('id', 'uuid', 'item_name', 'lower_unit_uom_id', 'lower_unit_item_upc')
                                        ->with('itemUomLowerUnit:id,name,code')
                                        ->where('item_major_category_id', $pdpgroup['item_major_category_id'])
                                        ->get();
                                }

                                $price_promotion_discount->p_d_p_items = $items;
                            } else {

                                $price_promotion_discount = PriceDiscoPromoPlan::with(
                                    'PDPDiscountSlabs',
                                    'PDPItems:id,price_disco_promo_plan_id,item_id,item_uom_id,price',
                                    'PDPItems.item:id,item_name,lower_unit_uom_id,lower_unit_item_upc',
                                    'PDPItems.itemUom:id,name'
                                )
                                    ->where('id', $useThisItem)
                                    ->first();
                            }
                        } else {
                            $price_promotion_discount = PriceDiscoPromoPlan::with('PDPItems:id,price_disco_promo_plan_id,item_id,item_uom_id,price', 'PDPItems.item:id,item_name', 'PDPItems.itemUom:id,name')
                                ->where('id', $useThisItem)
                                ->first();
                        }

                        $user = $customer->user()->select('id', 'firstname', 'lastname')->first();

                        $data_array1[] = array(
                            'customer' => $user,
                            'pdp' => $price_promotion_discount
                        );
                    }
                }
            }
        }
        $d = array_merge($data_array, $data_array1);
        return prepareResult(true, $d, [], "pdp listing", $this->success);
    }

    private function makeKeyValue($customerCountry, $customerRegion, $customerArea, $customerRoute, $customerSalesOrganisation, $customerChannel, $customerCustomerCategory, $customerCustomer, $combination_key_code, $combination_key, $price_disco_promo_plan_id, $p_d_p_item_id, $price, $priority_sequence)
    {
        $keyCodes = '';
        $combination_actual_id = '';

        foreach (explode('/', $combination_key_code) as $hierarchyNumber) {
            if ($hierarchyNumber == 11) {
                break;
            }
            switch ($hierarchyNumber) {
                case '1':
                    if (empty($add)) {
                        $add = $customerCountry;
                    } else {
                        $add = '/' . $customerCountry;
                    }
                    // $add  = $customerCountry;
                    break;
                case '2':
                    if (empty($add)) {
                        $add = $customerRegion;
                    } else {
                        $add = '/' . $customerRegion;
                    }
                    // $add  = '/' . $customerRegion;
                    break;
                case '3':
                    if (empty($add)) {
                        $add = $customerArea;
                    } else {
                        $add = '/' . $customerArea;
                    }
                    // $add  = '/' . $customerArea;
                    break;
                case '4':
                    if (empty($add)) {
                        $add = $customerRoute;
                    } else {
                        $add = '/' . $customerRoute;
                    }
                    // $add  = '/' . $customerRoute;
                    break;
                case '5':
                    if (empty($add)) {
                        $add = $customerSalesOrganisation;
                    } else {
                        $add = '/' . $customerSalesOrganisation;
                    }
                    break;
                case '6':
                    if (empty($add)) {
                        $add = $customerChannel;
                    } else {
                        $add = '/' . $customerChannel;
                    }
                    // $add  = '/' . $customerChannel;
                    break;
                case '7':
                    if (empty($add)) {
                        $add = $customerCustomerCategory;
                    } else {
                        $add = '/' . $customerCustomerCategory;
                    }
                    // $add  = '/' . $customerCustomerCategory;
                    break;
                case '8':
                    if (empty($add)) {
                        $add = $customerCustomer;
                    } else {
                        $add = '/' . $customerCustomer;
                    }
                    // $add  = '/' . $customerCustomer;
                    break;
                    // case '9':
                    //     if (empty($add)) {
                    //         $add = $itemMajorCategory;
                    //     } else {
                    //         $add = '/' . $itemMajorCategory;
                    //     }
                    //     // $add  = '/' . $itemMajorCategory;
                    //     break;
                    // case '10':
                    //     if (empty($add)) {
                    //         $add = $itemItemGroup;
                    //     } else {
                    //         $add = '/' . $itemItemGroup;
                    //     }
                    //     // $add  = '/' . $itemItemGroup;
                    //     break;
                    // case '11':
                    //     if (empty($add)) {
                    //         $add = $item;
                    //     } else {
                    //         $add = '/' . $item;
                    //     }
                    // $add  = '/' . $item;
                    // break;
                default:
                    # code...
                    break;
            }
            $keyCodes .= $hierarchyNumber;

            $combination_actual_id .= $add;
        }

        $getIdentify = PriceDiscoPromoPlan::find($price_disco_promo_plan_id);

        $returnData = array();

        if (isset($getIdentify->id) && $getIdentify->use_for == 'Discount') {
            return array(
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key' => $combination_key,
                'combination_key_code' => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                'p_d_p_item_id' => $p_d_p_item_id,
                'priority_sequence' => $priority_sequence,
                'price' => $price,
                'use_for' => $getIdentify->use_for,
                'type' => $getIdentify->type,
                'qty_from' => $getIdentify->qty_from,
                'qty_to' => $getIdentify->qty_to,
                'discount_type' => $getIdentify->discount_type,
                'discount_value' => $getIdentify->discount_value,
                'discount_percentage' => $getIdentify->discount_percentage,
                'discount_apply_on' => $getIdentify->discount_apply_on
            );
        }

        if (is_object($getIdentify) && $getIdentify->use_for == 'Promotion') {
            return array(
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key' => $combination_key,
                'combination_key_code' => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                'p_d_p_promotion_items' => $p_d_p_item_id,
                'priority_sequence' => $priority_sequence,
                'price' => $price,
                'use_for' => $getIdentify->use_for
            );
        }

        if (isset($getIdentify->id)) {

            $returnData = [
                'price_disco_promo_plan_id' => $price_disco_promo_plan_id,
                'combination_key' => $combination_key,
                'combination_key_code' => $combination_key_code,
                'combination_actual_id' => $combination_actual_id,
                'auto_sequence_by_code' => $hierarchyNumber,
                'hierarchyNumber' => $keyCodes,
                // 'p_d_p_item_id' => $p_d_p_item_id,
                'p_d_p_customer_id' => $customerCustomer,
                'priority_sequence' => $priority_sequence,
                'price' => $price,
                'use_for' => $getIdentify->use_for
            ];
        }


        return $returnData;
    }

    private function checkDataExistOrNot($combination_key_number, $combination_actual_id, $price_disco_promo_plan_id)
    {
        switch ($combination_key_number) {
            case '1':
                $model = 'App\Model\PDPCountry';
                $field = 'country_id';
                break;
            case '2':
                $model = 'App\Model\PDPRegion';
                $field = 'region_id';
                break;
            case '3':
                $model = 'App\Model\PDPArea';
                $field = 'area_id';
                break;
            case '4':
                $model = 'App\Model\PDPRoute';
                $field = 'route_id';
                break;
            case '5':
                $model = 'App\Model\PDPSalesOrganisation';
                $field = 'sales_organisation_id';
                break;
            case '6':
                $model = 'App\Model\PDPChannel';
                $field = 'channel_id';
                break;
            case '7':
                $model = 'App\Model\PDPCustomerCategory';
                $field = 'customer_category_id';
                break;
            case '8':
                $model = 'App\Model\PDPCustomer';
                $field = 'customer_id';
                break;
            case '9':
                $model = 'App\Model\PDPItemMajorCategory';
                $field = 'item_major_category_id';
                break;
            case '10':
                $model = 'App\Model\PDPItemGroup';
                $field = 'item_group_id';
                break;
            case '11':
                $model = 'App\Model\PDPItem';
                $field = 'item_id';
                break;
            default:
                $model = '';
                $field = '';
                break;
        }


        $checkExistOrNot = $model::where('price_disco_promo_plan_id', $price_disco_promo_plan_id)->where($field, $combination_actual_id)->first();


        if ($checkExistOrNot) {
            return true;
        }

        return false;
    }

    public function PDPMobileIndex()
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = request()->json()->all();
        $validate = $this->validations($input, "pdpMobile");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating pricing plan", $this->unprocessableEntity);
        }


        $price_discon_promo_plan = PriceDiscoPromoPlan::with(
            'PDPCountries:id,uuid,price_disco_promo_plan_id,country_id',
            'PDPCountries.country:id,uuid,name,currency,currency_code,currency_symbol,status',
            'PDPRegions:id,uuid,price_disco_promo_plan_id,region_id',
            'PDPRegions.region:id,uuid,region_code,region_name,region_status',
            'PDPAreas:id,uuid,price_disco_promo_plan_id,area_id',
            'PDPAreas.area:id,uuid,parent_id,area_name,node_level,status',
            'PDPRoutes:id,uuid,price_disco_promo_plan_id,route_id',
            'PDPRoutes.route:id,uuid,route_code,route_name,status',
            'PDPSalesOrganisations:id,uuid,price_disco_promo_plan_id,sales_organisation_id',
            'PDPSalesOrganisations.salesOrganisation.customerInfos.user:id,uuid,firstname,lastname,email',
            'PDPChannels:id,uuid,price_disco_promo_plan_id,channel_id',
            'PDPChannels.channel:id,uuid,parent_id,name,node_level,status',
            'PDPCustomerCategories:id,uuid,price_disco_promo_plan_id,customer_category_id',
            'PDPCustomerCategories.customerCategory:id,uuid,customer_category_code,customer_category_name,status',
            'PDPCustomers:id,uuid,price_disco_promo_plan_id,customer_id',
            'PDPCustomers.customerInfo.user:id,uuid,firstname,lastname',
            'PDPItemMajorCategories:id,uuid,price_disco_promo_plan_id',
            'PDPItemMajorCategories.itemMajorCategory:id,uuid,name,parent_id,node_level',
            'PDPItemGroups:id,uuid,price_disco_promo_plan_id,item_group_id',
            'PDPItemGroups.itemGroup:id,uuid,name,code,status',
            'PDPItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,price',
            'PDPItems.item:id,uuid,item_name,item_code,status',
            'PDPItems.itemUom:id,uuid,name',
            'PDPPromotionItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,item_qty,price',
            'PDPPromotionItems.item:id,uuid,item_code,item_name',
            'PDPPromotionItems.itemUom:id,uuid,name,code,status',
            'PDPPromotionOfferItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,offered_qty',
            'PDPPromotionOfferItems.item:id,uuid,item_code,item_name',
            'PDPPromotionOfferItems.itemUom:id,uuid,name,code,status'
        )
            ->where('use_for', request()->type)
            ->where('start_date', '<=', date('Y-m-d'))
            ->where('end_date', '>=', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

        $price_discon_promo_plan_array = array();
        if (is_object($price_discon_promo_plan)) {
            foreach ($price_discon_promo_plan as $key => $price_discon_promo_plan1) {
                $price_discon_promo_plan_array[] = $price_discon_promo_plan[$key];
            }
        }

        $data_array = array();
        $page = (isset(request()->page)) ? request()->page : '';
        $limit = (isset(request()->page_size)) ? request()->page_size : '';

        $pagination = array();

        if ($page != '' && $limit != '') {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($price_discon_promo_plan_array[$offset])) {
                    $data_array[] = $price_discon_promo_plan_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($price_discon_promo_plan_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($price_discon_promo_plan_array);
        } else {
            $data_array = $price_discon_promo_plan_array;
        }

        return prepareResult(true, $data_array, [], "Price disc plan listing", $this->success, $pagination);
    }

    public function PDPMobileIdexnPricing(Request $request)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = request()->json()->all();
        $validate = $this->validations($input, "pdpMobilePricing");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating pricing plan", $this->unprocessableEntity);
        }

        $customer = DB::table('customer_infos')
        ->leftjoin('users', 'customer_infos.user_id', '=', 'users.id')
        ->leftJoin("customer_lobs", "customer_infos.id", '=', "customer_lobs.customer_info_id")
        ->leftJoin("p_d_p_customers", "customer_infos.id", '=', "p_d_p_customers.customer_id")
        ->leftJoin("price_disco_promo_plans", "p_d_p_customers.price_disco_promo_plan_id", '=', "price_disco_promo_plans.id")
        ->leftJoin("p_d_p_items", "p_d_p_items.price_disco_promo_plan_id", '=', "price_disco_promo_plans.id")
        ->leftJoin("items", "items.id", '=', "p_d_p_items.item_id")
        ->leftJoin("p_d_p_routes", "customer_lobs.route_id", '=', "p_d_p_routes.route_id")
        ->leftJoin("price_disco_promo_plans as rp", "p_d_p_routes.price_disco_promo_plan_id", '=', "rp.id")
        ->leftJoin("p_d_p_items as ri", "ri.price_disco_promo_plan_id", '=', "rp.id")
        ->leftJoin("items as it", "it.id", '=', "ri.item_id")
        ->select(
            'customer_infos.id as customer_id',
            'customer_infos.customer_code',
            DB::raw("concat(users.firstname,' ',users.lastname) AS 'customer_fullname'"),
            'customer_lobs.route_id',
            'price_disco_promo_plans.id as price_disco_promo_plans_id',
            'price_disco_promo_plans.combination_plan_key_id',
            'price_disco_promo_plans.combination_key_value',
            'p_d_p_items.item_id as item_id',
            'p_d_p_items.item_uom_id',
            'p_d_p_items.price',
            'items.item_code',
            'rp.id as route_plans_id',
            'rp.combination_plan_key_id as route_combination_plan_key_id',
            'rp.combination_key_value as route_combination_key_value',
            'ri.item_id as route_item_id',
            'ri.item_uom_id as route_item_uom_id',
            'ri.price as route_price',
            'it.item_code as route_item_code',
        );

        if (isset($request->route_id) && !empty($request->route_id)) {
            $customer = $customer->where('customer_lobs.route_id', $request->route_id);
        }

        if (isset($request->type) && !empty($request->type)) {
            $customer = $customer->where('price_disco_promo_plans.use_for', $request->type);
        }
        
        $customer = $customer->groupBy(['price_disco_promo_plans.id','p_d_p_items.item_id','rp.id','ri.item_id']);
        $customer = $customer->orderBy('customer_infos.id', 'ASC');
        $customer = $customer->orderBy('p_d_p_items.item_id', 'ASC');
        $customers = $customer->get();

        $records = [];

        $a = 0;
        $count_numc = (int)$a;
        $count_nump = (int)$a;
        $count_numi = (int)$a;
        foreach ($customers as $key => $customer) {

            if(($key != 0) && ($customers[$key-1]->customer_id != $customer->customer_id)){
                $count_numc = (int)($count_numc + 1);
                $count_nump = (int)$a;
                $count_numi = (int)$a;
            }

            if(($key != 0) && ($customers[$key-1]->price_disco_promo_plans_id != $customer->price_disco_promo_plans_id) && ($customers[$key-1]->customer_id == $customer->customer_id)){
                $count_nump = (int)($count_nump + 1);
                $count_numi = (int)$a;
            } 

            if(($key != 0) && ($customers[$key-1]->item_id != $customer->item_id) && ($customers[$key-1]->price_disco_promo_plans_id == $customer->price_disco_promo_plans_id)){
                $count_numi = (int)($count_numi + 1);
            }

            $records[$count_numc]['customer']['customer_code'] = $customer->customer_code;
            $records[$count_numc]['customer']['customer_fullname'] = $customer->customer_fullname;

            $records[$count_numc]['customer']['Priceinplan'][$count_nump]['price_disco_promo_plans_id'] = $customer->price_disco_promo_plans_id;
            $records[$count_numc]['customer']['Priceinplan'][$count_nump]['combination_plan_key_id'] = $customer->combination_plan_key_id;
            $records[$count_numc]['customer']['Priceinplan'][$count_nump]['combination_key_value'] = $customer->combination_key_value;
            if (!empty($customer->item_id)) {
                
                $records[$count_numc]['customer']['Priceinplan'][$count_nump]['Item'][$count_numi]['item_code'] = $customer->item_code;
                $records[$count_numc]['customer']['Priceinplan'][$count_nump]['Item'][$count_numi]['item_uom_id'] = $customer->item_uom_id;
                $records[$count_numc]['customer']['Priceinplan'][$count_nump]['Item'][$count_numi]['price'] = $customer->price;

            }

            if (!empty($customer->route_plans_id)) {
                
                $records[$count_numc]['customer']['Priceinplanroute'][$count_nump]['price_disco_promo_plans_id'] = $customer->route_plans_id;
                $records[$count_numc]['customer']['Priceinplanroute'][$count_nump]['combination_plan_key_id'] = $customer->route_combination_plan_key_id;
                $records[$count_numc]['customer']['Priceinplanroute'][$count_nump]['combination_key_value'] = $customer->route_combination_key_value;

                if (!empty($customer->route_item_id)) {

                    $records[$count_numc]['customer']['Priceinplanroute'][$count_nump]['Item'][$count_numi]['item_code'] = $customer->route_item_code;
                    $records[$count_numc]['customer']['Priceinplanroute'][$count_nump]['Item'][$count_numi]['item_uom_id'] = $customer->route_item_uom_id;
                    $records[$count_numc]['customer']['Priceinplanroute'][$count_nump]['Item'][$count_numi]['price'] = $customer->route_price;
                }

            }
           
        }
        return prepareResult(true, $records, [], "Price disc plan listing", $this->success);
    }


    public function PDPMobileIndexOther(Request $request)
    {

        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "Unauthorized access", $this->unauthorized);
        }

        $input = request()->json()->all();
        $validate = $this->validations($input, "pdpMobileNew");

        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating pricing plan", $this->unprocessableEntity);
        }

        $records = [];

        $plandetails = PriceDiscoPromoPlan::with(
            'PDPCountries:id,uuid,price_disco_promo_plan_id,country_id',
            'PDPCountries.country:id,uuid,name,currency,currency_code,currency_symbol,status',
            'PDPRegions:id,uuid,price_disco_promo_plan_id,region_id',
            'PDPRegions.region:id,uuid,region_code,region_name,region_status',
            'PDPAreas:id,uuid,price_disco_promo_plan_id,area_id',
            'PDPAreas.area:id,uuid,parent_id,area_name,node_level,status',
            'PDPRoutes:id,uuid,price_disco_promo_plan_id,route_id',
            'PDPRoutes.route:id,uuid,route_code,route_name,status',
            'PDPSalesOrganisations:id,uuid,price_disco_promo_plan_id,sales_organisation_id',
            'PDPSalesOrganisations.salesOrganisation.customerInfos.user:id,uuid,firstname,lastname,email',
            'PDPChannels:id,uuid,price_disco_promo_plan_id,channel_id',
            'PDPChannels.channel:id,uuid,parent_id,name,node_level,status',
            'PDPCustomerCategories:id,uuid,price_disco_promo_plan_id,customer_category_id',
            'PDPCustomerCategories.customerCategory:id,uuid,customer_category_code,customer_category_name,status',
            'PDPCustomers:id,uuid,price_disco_promo_plan_id,customer_id',
            'PDPCustomers.customerInfo.user:id,uuid,firstname,lastname',
            'PDPItemMajorCategories:id,uuid,price_disco_promo_plan_id',
            'PDPItemMajorCategories.itemMajorCategory:id,uuid,name,parent_id,node_level',
            'PDPItemGroups:id,uuid,price_disco_promo_plan_id,item_group_id',
            'PDPItemGroups.itemGroup:id,uuid,name,code,status',
            'PDPItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,price',
            'PDPItems.item:id,uuid,item_name,item_code,status',
            'PDPItems.itemUom:id,uuid,name',
            'PDPPromotionItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,item_qty,price',
            'PDPPromotionItems.item:id,uuid,item_code,item_name',
            'PDPPromotionItems.itemUom:id,uuid,name,code,status',
            'PDPPromotionOfferItems:id,uuid,price_disco_promo_plan_id,item_id,item_uom_id,offered_qty',
            'PDPPromotionOfferItems.item:id,uuid,item_code,item_name',
            'PDPPromotionOfferItems.itemUom:id,uuid,name,code,status'
        )
        ->where('use_for', $request->type)
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('end_date', '>=', date('Y-m-d'))
        ->orderBy('id', 'desc')
        ->get();

        $a = 0;
        $count_numc = (int)$a;
        $records = [];
        foreach ($plandetails as $key => $plandetail) {

            if(isset($plandetail['PDPRoutes'][0]->id) || isset($plandetail[0]['PDPCustomers'][0]->id)){

                $records[$count_numc] = $plandetail;
                $count_numc++;
            }
            
        }
        
        return prepareResult(true, $records, [], "Price disc plan listing", $this->success);
    }



}
