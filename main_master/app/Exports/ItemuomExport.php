<?php

namespace App\Exports;

use App\Model\ItemUom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemuomExport implements FromCollection, WithHeadings
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
		$ItemUoms = ItemUom::select('code', 'name', 'status');
		if ($start_date != '' && $end_date != '') {
			$ItemUoms = $ItemUoms->whereBetween('created_at', [$start_date, $end_date]);
		}
		$ItemUoms = $ItemUoms->get();
		if (is_object($ItemUoms)) {
			foreach ($ItemUoms as $key => $ItemUom) {
				unset($ItemUoms[$key]->id);
				unset($ItemUoms[$key]->uuid);
				unset($ItemUoms[$key]->organisation_id);
			}
		}
		return $ItemUoms;
	}
	
	public function headings(): array
	{
		return [
			'Code',
			'Name',
			'Status',
		];
	}
}
