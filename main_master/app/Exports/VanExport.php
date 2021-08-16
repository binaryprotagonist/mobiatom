<?php

namespace App\Exports;

use App\Model\Van;
use App\Model\VanType;
use App\Model\VanCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VanExport implements FromCollection, WithHeadings
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
		$vans = Van::select('id', 'uuid', 'organisation_id', 'van_code', 'plate_number', 'description', 'capacity', 'van_type_id', 'van_category_id', 'van_status');
		if ($start_date != '' && $end_date != '') {
			$vans = $vans->whereBetween('created_at', [$start_date, $end_date]);
		}
		$vans = $vans->get();
		if (is_object($vans)) {
			foreach ($vans as $key => $van) {
				$VanType = VanType::find($van->van_type_id);
				$VanCategory = VanCategory::find($van->van_category_id);

				unset($vans[$key]->id);
				unset($vans[$key]->uuid);
				unset($vans[$key]->organisation_id);
				unset($vans[$key]->van_type_id);
				unset($vans[$key]->van_category_id);

				$vans[$key]->VanType = (is_object($VanType)) ? $VanType->type : '';
				$vans[$key]->VanCategory = (is_object($VanCategory)) ? $VanCategory->name : '';
			}
		}
		return $vans;
	}

	public function headings(): array
	{
		return ["Van Code", "Van Description", 'Plate number', 'Capacity', 'Status'];
	}
}
