<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DebitnotesReportExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $debit_notes, $columns;
	public function __construct(object  $debit_notes, array $columns)
	{
		$this->debit_notes = $debit_notes;
		$this->columns = $columns;
	}
	public function collection()
	{
		$debit_notes = $this->debit_notes;
		return $debit_notes;
	}
	public function headings(): array
	{
		$columns = $this->columns;

		return $columns;
	}
}
