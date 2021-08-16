<?php

namespace App\Exports;

use App\Model\Distribution;
use App\Model\DistributionCustomer;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class DistributionModelStockExport implements FromCollection, WithHeadings
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

		return;
	}
	public function headings(): array
	{
		return [
			'Name',
			'Start date',
			'End date',
			'Height',
			'Width',
			'Depth',
			'Status',
			'Customer email'
		];
	}
}
