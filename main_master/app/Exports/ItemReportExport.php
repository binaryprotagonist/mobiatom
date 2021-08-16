<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemReportExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $salesbyitem,$columns;
	public function __construct(object  $salesbyitem,array $columns)
	{
		$this->salesbyitem = $salesbyitem;
		$this->columns = $columns;
	}
    public function collection()
    {
		$salesbyitem = $this->salesbyitem;
		return $salesbyitem;
    }
	public function headings(): array
    {
		$columns = $this->columns;
		
        return $columns;
    }
}
