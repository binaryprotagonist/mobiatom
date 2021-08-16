<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Area;
use App\Model\Channel;
use App\Model\Country;
use App\Model\CustomerCategory;
use App\Model\CustomerInfo;
use App\Model\Depot;
use App\Model\Item;
use App\Model\ItemGroup;
use App\Model\ItemMajorCategory;
use App\Model\Region;
use App\Model\Route;
use App\Model\SalesOrganisation;
use Illuminate\Http\Request;

class KeyCombinationController extends Controller
{
    public function countryList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->country_id) && sizeof(request()->country_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one country.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $country = Country::with(
            'regions',
            'regions.depots', 
            'regions.depots.area',
            'regions.depots.area.children',
            'regions.depots.area.routes'
        )
        ->whereIn('id', request()->country_id)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($country, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $country, [], "Country listing", $this->success);
    }
    /* 
        Country / region / area
    */

    private function getListByParam($obj, $param)
    {
        // $object = json_decode($obj);
        $object = $obj;

        $array = [];
        $get = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($object), \RecursiveIteratorIterator::SELF_FIRST);

            foreach($get as $key => $value) {
                if($key === $param)
                {
                    $array = array_merge($array, $value);
                }
            }
        
        return $array;
        // return prepareResult(true, $array, [], "Regions listing", $this->success);
    }

    public function depotList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->depot_id) && sizeof(request()->depot_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one depot.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $depots = Depot::with(
            'area',
            'area.routes'
        )
        ->whereIn('id', request()->depot_id)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($depots, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $depots, [], "Depot listing", $this->success);
    }

    public function areaList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->area_id) && sizeof(request()->area_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one area.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $area = Area::with(
            'routes'
        )
        ->whereIn('id', request()->area_id)
        ->get();


        if ($param) {
            $param_data =  $this->getListByParam(json_decode($area, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $area, [], "Area listing", $this->success);
    }

    public function RouteList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->route_id) && sizeof(request()->route_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one route.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $route = Route::whereIn('id', request()->route_id)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($route, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $route, [], "Route listing", $this->success);
    }
    
    public function SalesOrganisationList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->sales_organisation_ids) && sizeof(request()->sales_organisation_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one sales organisation.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $sales_organisation = SalesOrganisation::with(
            // 'customerInfos:id,uuid,region_id,route_id,user_id,sales_organisation_id',
            // 'customerInfos.user:id,uuid,firstname,lastname',
            'channels:id,uuid,sales_organisation_id,name,status',
            'channels.customerInfos:id,uuid,region_id,route_id,user_id,sales_organisation_id,channel_id'
        )
        ->whereIn('id', request()->sales_organisation_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($sales_organisation, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $sales_organisation, [], "Sales Organisation listing", $this->success);
    }
    
    public function ChannelList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->channel_ids) && sizeof(request()->channel_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one channel.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $channel = Channel::with(
            'channels.customerInfos:id,uuid,region_id,route_id,user_id,sales_organisation_id,channel_id'
        )
        ->whereIn('id', request()->channel_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($channel, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $channel, [], "Channel listing", $this->success);
    }
    
    public function CustomerCategoryList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->customer_category_ids) && sizeof(request()->customer_category_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer category.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $customer_category = CustomerCategory::whereIn('id', request()->customer_category_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($customer_category, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $customer_category, [], "Customer Category listing", $this->success);
    }
    
    public function CustomerList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->customer_ids) && sizeof(request()->customer_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one customer.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $customer = CustomerInfo::whereIn('id', request()->customer_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($customer, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $customer, [], "Customer listing", $this->success);
    }
    
    public function ItemMajorCategoryList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->item_major_category_ids) && sizeof(request()->item_major_category_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one item major category.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $item_major_category = ItemMajorCategory::whereIn('id', request()->item_major_category_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($item_major_category, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $item_major_category, [], "Item major category listing", $this->success);
    }
    
    public function ItemGroupList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->item_group_ids) && sizeof(request()->item_group_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one item group.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $item_group = ItemGroup::whereIn('id', request()->item_group_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($item_group, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $item_group, [], "Item Group listing", $this->success);
    }
    
    public function ItemList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->item_ids) && sizeof(request()->item_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one item.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $item = Item::whereIn('id', request()->item_ids)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($item, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $item, [], "Item listing", $this->success);
    }
    
    public function CombinationItems()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $items = Item::whereIn('item_major_category_id', request()->item_major_category_ids)
        ->orWhereIn('item_group_id', request()->item_group_ids)
        ->orWhereIn('id', request()->items_ids)
        ->with(
            'itemUomLowerUnit:id,name', 
            'itemMainPrice:id,item_upc,item_uom_id,item_price,item_id',
            'itemMainPrice.itemUom:id,name'
        )
        ->get();

        if (sizeof($items) < 1) {
            $items = Item::all();
        }

        return prepareResult(true, $items, [], "Item listing", $this->success);
    }

    public function regionsList()
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array(request()->region_id) && sizeof(request()->region_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one regions.", $this->unprocessableEntity);
        }

        $param = request()->input('param');

        $region = Region::with(
            'depots', 
            'depots.area',
            'depots.area.routes'
        )
        ->whereIn('id', request()->region_id)
        ->get();

        if ($param) {
            $param_data =  $this->getListByParam(json_decode($region, true), $param);
            return prepareResult(true, $param_data, [], $param. " listing", $this->success);
        }

        return prepareResult(true, $region, [], "Regions listing", $this->success);
    }
}
