<?php

namespace App\Exports;

use App\Model\BankInformation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BankExport implements FromCollection, WithHeadings
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
		$BankInformation = BankInformation::select('bank_code', 'bank_name', 'bank_address', 'account_number', 'status');
		if ($start_date != '' && $end_date != '') {
			$BankInformation = $BankInformation->whereBetween('created_at', [$start_date, $end_date]);
		}
		$BankInformation = $BankInformation->get();
		/* if(is_object($BankInformation)){
			foreach($BankInformation as $key=>$Bank){
				unset($BankInformation[$key]->id);
				unset($BankInformation[$key]->uuid);
				unset($BankInformation[$key]->organisation_id);
			}
		} */
		return $BankInformation;
	}
	public function headings(): array
	{
		return [
			'Bank Code',
			'Bank name',
			'Bank address',
			'Account number',
			'Status'
		];
	}
}
