<?php

namespace App\Exports;

use App\Model\Route;
use App\Model\Area;
use App\Model\Depot;
use App\Model\Van;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RouteExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $StartDate, $EndDate;

	public function __construct(String  $StartDate, String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
	public function collection()
	{
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;
		$routes = Route::with(
			'areas:id,area_name',
			'depot:id,depot_name',
			'van:id,van_code',

		);
		if ($start_date != '' && $end_date != '') {
			$routes = $routes->whereBetween('created_at', [$start_date, $end_date]);
		}
		$routes = $routes->get();

		if (is_object($routes)) {
			foreach ($routes as $key => $route) {
				$depot = Depot::find($route->depot_id);
				$area = Area::find($route->area_id);
				$van = Van::find($route->van_id);
				unset($routes[$key]->id);
				unset($routes[$key]->van_id);
				unset($routes[$key]->uuid);
				unset($routes[$key]->organisation_id);
				unset($routes[$key]->depot_id);
				unset($routes[$key]->area_id);
				unset($routes[$key]->created_at);
				unset($routes[$key]->updated_at);
				unset($routes[$key]->deleted_at);

				$routes[$key]->van_code = (is_object($van)) ? $van->van_code : '';
				$routes[$key]->depot_name = (is_object($depot)) ? $depot->depot_name : '';
				$routes[$key]->area_name = (is_object($area)) ? $area->area_name : '';
			}
		}
		return $routes;
	}

	public function headings(): array
	{
		return ["Route Code", "Route Name", 'Status', 'Van', "Depot Name", "Area Name"];
	}
}
