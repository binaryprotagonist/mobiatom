<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CreditnotesReportExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $credit_notes, $columns;

	public function __construct(object  $credit_notes, array $columns)
	{
		$this->credit_notes = $credit_notes;
		$this->columns = $columns;
	}

	public function collection()
	{
		$credit_notes = $this->credit_notes;
		return $credit_notes;
	}

	public function headings(): array
	{
		$columns = $this->columns;
		return $columns;
	}
}
