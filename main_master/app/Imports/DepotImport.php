<?php

namespace App\Imports;

use App\User;
use App\Model\Depot;
use App\Model\Region;
use App\Model\Area;
use Maatwebsite\Excel\Concerns\ToModel;

class DepotImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='User Email'){
			$user = User::where('email',$row[0])->first();
			$region = Region::where('region_code',$row[1])->first();
			$area = Area::where('area_name',$row[4])->first();
			$depot = new Depot;
			$depot->user_id = (is_object($user))?$user->id:0;
			$depot->region_id = (is_object($region))?$region->id:0;
			$depot->depot_code = $row[2];
			$depot->depot_name = $row[3];
			$depot->area_id = (is_object($area))?$area->id:0;
			$depot->depot_manager = $row[5];
			$depot->depot_manager_contact = $row[6];
			$depot->status = $row[7];
			$depot->save();
			updateNextComingNumber('App\Model\Depot', 'depot');
			return $depot;
		}
    }
}
