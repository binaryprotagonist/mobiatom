<?php

namespace App\Imports;

use App\Model\Region;
use App\Model\Country;
use Maatwebsite\Excel\Concerns\ToModel;

class RegionImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if(isset($row[0]) && $row[0]!='Country'){
			$country = Country::where('name','LIKE', '%' . $row[0] . '%')->first();
			
			$region = new Region;
			$region->country_id = (is_object($country))?$country->id:0;
			$region->region_code =  $row[1];
			$region->region_name = $row[2];
			$region->region_status = $row[3];
			$region->save();
			return $region;
		}
    }
}
