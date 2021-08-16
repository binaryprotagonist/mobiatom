<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\RouteItemGrouping;
use App\Model\RouteItemGroupingDetail;

class RouteItemGroupingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getitems($route_id)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }
		if (!$route_id) {
            return prepareResult(false, [], [], "Error while validating route item", $this->unauthorized);
        }

        $RouteItemGrouping = RouteItemGrouping::with(
            'routeItemGroupingDetails',
			'routeItemGroupingDetails.item:id,item_name'
        )
        ->where('route_id', $route_id)
        ->orderBy('id', 'desc')
        ->get();

        $RouteItemGrouping_array = array();
        if (is_object($RouteItemGrouping)) {
            foreach ($RouteItemGrouping as $key => $RouteItemGrouping1) {
                $RouteItemGrouping_array[] = $RouteItemGrouping[$key];
            }
        }

        $data_array = array();
        $page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $limit = (isset($_REQUEST['page_size'])) ? $_REQUEST['page_size'] : '';
        $pagination = array();

        if ($page && $limit) {
            $offset = ($page - 1) * $limit;
            for ($i = 0; $i < $limit; $i++) {
                if (isset($RouteItemGrouping_array[$offset])) {
                    $data_array[] = $RouteItemGrouping_array[$offset];
                }
                $offset++;
            }

            $pagination['total_pages'] = ceil(count($RouteItemGrouping_array) / $limit);
            $pagination['current_page'] = (int)$page;
            $pagination['total_records'] = count($RouteItemGrouping_array);
        } else {
            $data_array = $RouteItemGrouping_array;
        }

        return prepareResult(true, $data_array, [], "Route Item Grouping listing", $this->success, $pagination);

        // return prepareResult(true, $RouteItemGrouping, [], "Route Item Grouping listing", $this->success);
    }
}
