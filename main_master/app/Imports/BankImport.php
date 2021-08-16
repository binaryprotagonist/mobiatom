<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Model\BankInformation;
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

class BankImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError
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
		if(!empty($row)){
		if(isset($row[0]) && $row[0]!='Bank name'){
			$bank_information = BankInformation::where('bank_name',$row[0])
			->where('bank_code',$row[1])
			->where('account_number',$row[3])
			->first();
			if(is_object($bank_information)){
				if($skipduplicate == 0){
					$bank_information->bank_name = $row[0];
					$bank_information->bank_code = $row[1];
					$bank_information->bank_address = $row[2];
					$bank_information->account_number = $row[3];
					$bank_information->status = ($row[4] == 'active')?1:0;
					$bank_information->save();
				}
			}else{
				$bank_information = new BankInformation;
				$bank_information->bank_name = $row[0];
				$bank_information->bank_code = $row[1];
				$bank_information->bank_address = $row[2];
				$bank_information->account_number = $row[3];
				$bank_information->status = ($row[4] == 'active')?1:0;
				$bank_information->save();
				if ($bank_information) {
					updateNextComingNumber('App\Model\BankInformation', 'bank_information');
				}
			}
			return $bank_information;
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
        ];
    }
	public function customValidationMessages()
	{
		return [
			'0.required' => 'Bank name required',
			'1.required' => 'Bank code required',
			'2.required' => 'Bank address required',
			'3.required' => 'Account number required',
			'4.required' => 'Status required',
		];
	}
	/* public function onFailure(Failure ...$failures)
    {
        // Handle the failures how you'd like.
    } */
}
