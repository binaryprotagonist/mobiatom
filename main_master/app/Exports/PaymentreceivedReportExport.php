<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentreceivedReportExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $collections, $columns;

	public function __construct(object  $collections, array $columns)
	{
		$this->collections = $collections;
		$this->columns = $columns;
	}

	public function collection()
	{
		$collections = $this->collections;
		return $collections;
	}

	public function headings(): array
	{
		$columns = $this->columns;

		return $columns;
	}
}
