<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerStatementReportExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $balanceStatement_report,$columns;
	public function __construct(object  $balanceStatement_report,array $columns)
	{
		$this->balanceStatement_report = $balanceStatement_report;
		$this->columns = $columns;
	}
    public function collection()
    {
		$balanceStatement_report = $this->balanceStatement_report;
		return $balanceStatement_report;
    }
	public function headings(): array
    {
		$columns = $this->columns;
        return $columns; 
    }
}
