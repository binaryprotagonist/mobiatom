<?php

namespace App\Exports;

use App\Model\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VendorExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $StartDate,$EndDate;
	public function __construct(String  $StartDate,String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
    public function collection()
    {
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;
		$vendors = Vendor::select('vender_code', 'firstname', 'lastname','email','company_name','mobile','website','address1','address2','city','state','zip','status');
		if($start_date!='' && $end_date!=''){
			$vendors = $vendors->whereBetween('created_at', [$start_date, $end_date]);
		}
        $vendors = $vendors->get();
		if(is_object($vendors)){
			foreach($vendors as $key=>$vendor){
				unset($vendors[$key]->id);
				unset($vendors[$key]->uuid);
				unset($vendors[$key]->organisation_id);
			}
		}
		return $vendors;
    }
	public function headings(): array
    {
        return [
            'Vendor Code',
            'First name',
			'Last name',
			'Email',
			'Company name',
			'Mobile',
			'Website',
			'Address one',
			'Address two',
			'City',
			'State',
			'Zip',
			'Status'
        ];
    }
}
