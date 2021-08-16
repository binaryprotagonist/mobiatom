<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesmanReportExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $salesbysalesman,$columns;
	public function __construct(object  $salesbysalesman,array $columns)
	{
		$this->salesbysalesman = $salesbysalesman;
		$this->columns = $columns;
	}
    public function collection()
    {
		$salesbysalesman = $this->salesbysalesman;
		return $salesbysalesman;
    }
	public function headings(): array
    {
		$columns = $this->columns;
		
        return $columns;
    }
}
