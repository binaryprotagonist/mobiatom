<?php

namespace App\Exports;

use App\Model\Depot;
use App\Model\Region;
use App\Model\Area;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class DepotExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $StartDate,$EndDate;
	public function __construct(String  $StartDate,String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
    public function collection()
    {
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;
        $depots = Depot::select('user_id', 'area_id', 'region_id', 'depot_code', 'depot_name', 'depot_manager', 'depot_manager_contact', 'status');
		if($start_date!='' && $end_date!=''){
			$depots = $depots->whereBetween('created_at', [$start_date, $end_date]);
		}
        $depots = $depots->get();
		
		if(is_object($depots)){
			foreach($depots as $key=>$depot){
				$user = User::find($depot->user_id);
				$region = Region::find($depot->region_id);
				$area = Area::find($depot->area_id);
				unset($depots[$key]->id);
				unset($depots[$key]->uuid);
				unset($depots[$key]->organisation_id);
				unset($depots[$key]->user_id);
				unset($depots[$key]->region_id);
				unset($depots[$key]->area_id);
				
				$depots[$key]->user_email = (is_object($user))?$user->email:'';
				$depots[$key]->region_code = (is_object($region))?$region->region_code:'';
				$depots[$key]->area_name = (is_object($area))?$area->area_name:'';
			}
		}
		
		return $depots;
    }
}
