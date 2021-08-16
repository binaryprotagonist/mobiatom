<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Area;
use App\Model\Channel;
use App\Model\Country;
use App\Model\Depot;
use App\Model\Region;
use App\Model\Route;
use App\Model\SubArea;
use App\Model\SubChannel;
use Illuminate\Http\Request;

class DataFilterController extends Controller
{
    public function routeCountryList(array $data = null)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (!is_array($data)) {
            if (is_array(request()->route_ids) && sizeof(request()->route_ids) < 1) {
                return prepareResult(false, [], [], "Error Please add atleast one route.", $this->unprocessableEntity);
            }
        }

        if ($data) {
            $route = Country::whereIn('id', $data)->get();
        } else {
            $route = Country::whereIn('id', request()->route_ids)->get();
        }

        return prepareResult(true, $route, [], "Country listing", $this->success);   
    }

    public function areaRouteList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->area_ids) && sizeof($request->area_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one area.", $this->unprocessableEntity);
        }

        $route = Route::whereIn('area_id', $request->area_ids)->get();

        return prepareResult(true, $route, [], "Route listing", $this->success);   

    }

    public function depotAreaList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->depot_ids) && sizeof($request->depot_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one depot.", $this->unprocessableEntity);
        }

        $area = Area::whereIn('depot_id', $request->depot_ids)->get();

        return prepareResult(true, $area, [], "Area listing", $this->success);   

    }

    public function subAreaList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->depot_ids) && sizeof($request->depot_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one sub area.", $this->unprocessableEntity);
        }

        $sub_area = SubArea::whereIn('area_id', $request->area_id)->get();

        return prepareResult(true, $sub_area, [], "Sub Area listing", $this->success);   
    }

    public function depotRegionList(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->region_id) && sizeof($request->region_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one sub area.", $this->unprocessableEntity);
        }

        $depot = Depot::whereIn('region_id', $request->region_id)->get();

        return prepareResult(true, $depot, [], "Depot listing", $this->success);
    }

    public function regionContry(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->country_ids) && sizeof($request->country_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one country.", $this->unprocessableEntity);
        }

        $regions = Region::whereIn('country_ids', $request->country_ids)->get();

        return prepareResult(true, $regions, [], "Region listing", $this->success);
    }

    public function channleSalesOrg(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->sales_organisation_id) && sizeof($request->sales_organisation_id) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one sales Organisation.", $this->unprocessableEntity);
        }

        $channel = Channel::whereIn('sales_organisation_id', $request->sales_organisation_id)->get();

        return prepareResult(true, $channel, [], "Channel listing", $this->success);
    }

    public function subChannelChannel(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        if (is_array($request->channel_ids) && sizeof($request->channel_ids) < 1) {
            return prepareResult(false, [], [], "Error Please add atleast one Sub Channel.", $this->unprocessableEntity);
        }

        $sub_channel = SubChannel::whereIn('channel_ids', $request->channel_ids)->get();

        return prepareResult(true, $sub_channel, [], "Sub Channel listing", $this->success);
    }

    public function ComfinationResult(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
        
        $country = false;

        if ($request->area) {
            $area = true;
        }

        if ($area) {
            if ($request->countries) {
                
                $country = Country::with('regions','regions.depots', 'regions.depots.area')
                ->whereIn('id', array(6))
                ->get();

                $all_area = array();
                foreach ($country as $key => $regions) {
                    if (sizeof($regions) > 1) {
                        foreach ($regions as $k => $depots) {
                            if (sizeof($depots) > 1) {
                                foreach ($depots as $d => $areas) {
                                    if (sizeof($area) > 1) {
                                        foreach ($areas as $a => $area) {
                                            $all_area['id'] = $area->id;
                                            $all_area['area_code'] = $area->area_code;
                                            $all_area['area_name'] = $area->area_name;
                                            $all_area['status'] = $area->status;
                                        }
                                    } else {
                                        $all_area = array();
                                    }
                                }
                            } else {
                                $all_area = array();
                            }
                        }
                    } else {
                        $all_area = array();
                    }
                }
            }
        }
    }

}
