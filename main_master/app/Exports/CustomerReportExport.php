<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerReportExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $salesbycustomer,$columns;
	public function __construct(object  $salesbycustomer,array $columns)
	{
		$this->salesbycustomer = $salesbycustomer;
		$this->columns = $columns;
	}
    public function collection()
    {
		$salesbycustomer = $this->salesbycustomer;
		return $salesbycustomer;
    }
	public function headings(): array
    {
		$columns = $this->columns;
		
        return $columns;
    }
}
