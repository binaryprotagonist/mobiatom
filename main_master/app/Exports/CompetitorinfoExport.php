<?php

namespace App\Exports;

use App\Model\CompetitorInfo;
use App\Model\CompetitorInfoImage;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class CompetitorinfoExport implements FromCollection, WithHeadings
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
		$competitorinfos = CompetitorInfo::select('*');
		if ($start_date != '' && $end_date != '') {
			$competitorinfos = $competitorinfos->whereBetween('created_at', [$start_date, $end_date]);
		}
		$competitorinfos = $competitorinfos->get();

		$EstimationCollection = new Collection();
		if (is_object($competitorinfos)) {
			foreach ($competitorinfos as $competitorinfo) {
				$salesman = User::find($competitorinfo->salesman_id);
				$competitorinfoimages = CompetitorInfoImage::where('competitor_info_id', $competitorinfo->id)->get();
				if (is_object($competitorinfoimages)) {
					foreach ($competitorinfoimages as $competitorinfoimage) {
						$EstimationCollection->push((object)[
							'company' => $competitorinfo->company,
							'brand' => $competitorinfo->brand,
							'item' => $competitorinfo->item,
							'price' => $competitorinfo->price,
							'note' => $competitorinfo->note,
							'salesman' => (is_object($salesman)) ? $salesman->email : '',
							'image' => $competitorinfoimage->image_string
						]);
					}
				}
			}
		}
		return $EstimationCollection;
	}
	public function headings(): array
	{
		return [
			'Company',
			'Brand',
			'Item',
			'Price',
			'Note',
			'Salesman Email',
			'Image'
		];
	}
}
