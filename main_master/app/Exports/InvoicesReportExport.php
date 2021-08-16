<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesReportExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $invoices, $columns;

	public function __construct(object  $invoices, array $columns)
	{
		$this->invoices = $invoices;
		$this->columns = $columns;
	}

	public function collection()
	{
		$invoices = $this->invoices;
		return $invoices;
	}

	public function headings(): array
	{
		$columns = $this->columns;

		return $columns;
	}
}
