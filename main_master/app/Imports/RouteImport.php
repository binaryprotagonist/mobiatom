<?php

namespace App\Imports;

use App\Model\Route;
use App\Model\Area;
use App\Model\Depot;
use App\Model\Van;
use Maatwebsite\Excel\Concerns\ToModel;

class RouteImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if(isset($row[0]) && $row[0]!='Route Code'){
			$area = Area::where('area_name',$row[3])->first();
			$depot = Depot::where('depot_name',$row[4])->first();
			$van = Van::where('description',$row[5])->first();
			$route = new Route;
			$route->area_id = (is_object($area))?$area->id:0;
			$route->depot_id = (is_object($depot))?$depot->id:0;
			$route->van_id = (is_object($van))?$van->id:0;
			$route->route_code = $row[0];
			$route->route_name = $row[1];
			$route->status = $row[2];
			$route->save();
			updateNextComingNumber('App\Model\Route', 'route');
			return $route;
		}
    }
}
