<?php

namespace App\Imports;

use App\Model\CampaignPicture;
use App\Model\CampaignPictureImage;
use App\Model\Order;
use App\Model\CustomerInfo; 
use App\Model\SalesmanInfo;
use App\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Throwable;


class CampaignPictureImport implements ToModel
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
		if(isset($row[0]) && $row[0]!='campaign_id')
		{
			
			$customer = User::where('email',$row[2])->first();
			$salesman = User::where('email',$row[3])->first();
			
			$campaignpictures = CampaignPicture::where('campaign_id',$row[0])->first();
			if(is_object($campaignpictures))
			{
				if($skipduplicate == 0)
				{
					$campaignpictures->campaign_id = $row[0];
					$campaignpictures->feedback = $row[1];
					$campaignpictures->salesman_id = (is_object($salesman))?$salesman->id:0;
					$campaignpictures->customer_id = (is_object($customer))?$customer->id:0;
					$campaignpictures->save();
				}
			}
			else
			{
				$campaignpictures = new CampaignPicture;
				$campaignpictures->campaign_id = $row[0];
				$campaignpictures->feedback = $row[1];
				$campaignpictures->salesman_id = (is_object($salesman))?$salesman->id:0;
				$campaignpictures->customer_id = (is_object($customer))?$customer->id:0;
				$campaignpictures->save();
			}
			
			$campaignpicturesimage = new CampaignPictureImage;
            $campaignpicturesimage->id_campaign_picture = $campaignpictures->id;
            $campaignpicturesimage->image_string = $row[4];
            $campaignpicturesimage->save();
			return $campaignpictures;
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
			'0.required' => 'Campaign id required',
			'1.required' => 'Feedback required',
			'2.required' => 'Customer name required',
			'3.required' => 'Salesman name required',
			'4.required' => 'Image required',			
		];
	}
}
