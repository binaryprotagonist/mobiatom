<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EstimateReportExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $estimation,$columns;
	public function __construct(object  $estimation,array $columns)
	{
		$this->estimation = $estimation;
		$this->columns = $columns;
	}
    public function collection()
    {
		$estimation = $this->estimation;
		return $estimation;
    }
	public function headings(): array
    {
		$columns = $this->columns;
        return $columns;
    }
}
