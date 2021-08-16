<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Model\CombinationMaster;
use App\Model\CombinationPlanKey;
use App\Model\Vendor;
use App\User;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Throwable;

class VendorImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError
{
	use Importable, SkipsErrors, SkipsFailures;
	protected $skipduplicate;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
	public function __construct(String  $skipduplicate)
	{
		$this->skipduplicate = $skipduplicate;
	}
	public function startRow(): int
    {
        return 2;
    }
    public function model(array $row)
    {
		$skipduplicate = $this->skipduplicate;
		if(isset($row[0]) && $row[0]!='Vender Code'){
			$vendor = Vendor::where('vender_code',$row[0])->first();
			if(is_object($vendor)){
				if($skipduplicate == 0){
					$vendor->vender_code         = $row[0];
					$vendor->firstname            = $row[1];
					$vendor->lastname            = $row[2];
					$vendor->email       = $row[3];
					$vendor->company_name        = $row[4];
					$vendor->mobile        = $row[5];
					$vendor->website        = $row[6];
					$vendor->address1        = $row[7];
					$vendor->address2        = $row[8];
					$vendor->city        = $row[9];
					$vendor->state        = $row[10];
					$vendor->zip        = $row[11];
					$vendor->current_stage = 'Approved';
					$vendor->status        = $row[12];
					$vendor->save();
				}
			}else{
				$vendor = new Vendor;
				$vendor->vender_code         = $row[0];
				$vendor->firstname            = $row[1];
				$vendor->lastname            = $row[2];
				$vendor->email       = $row[3];
				$vendor->company_name        = $row[4];
				$vendor->mobile        = $row[5];
				$vendor->website        = $row[6];
				$vendor->address1        = $row[7];
				$vendor->address2        = $row[8];
				$vendor->city        = $row[9];
				$vendor->state        = $row[10];
				$vendor->zip        = $row[11];
				$vendor->current_stage = 'Approved';
				$vendor->status        = $row[12];
				$vendor->save();
			}
		}
    }
	public function rules(): array
    {
        return [
            '0' => 'required',
            '1' => 'required',
			'2' => 'required',
			'3' => 'required',
			'4' => 'required',
			'5' => 'required',
			'6' => 'required',
			'7' => 'required',
			'9' => 'required',
			'10' => 'required',
			'11' => 'required',
			'12' => 'required',
        ];
    }
	public function customValidationMessages()
	{
		return [
			'0.required' => 'Vender code required',
			'1.required' => 'First name required',
			'2.required' => 'Last name required',
			'3.required' => 'Email required',
			'4.required' => 'Company name required',
			'5.required' => 'Mobile required',
			'6.required' => 'Website required',
			'7.required' => 'Address one required',
			'9.required' => 'City required',
			'10.required' => 'State required',
			'11.required' => 'Zip required',
			'12.required' => 'Status required',
		];
	}
	/* public function onFailure(Failure ...$failures)
    {
        // Handle the failures how you'd like.
    } */
}
