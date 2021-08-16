<?php

namespace App\Imports;

use App\Model\AssetTracking;
use App\Model\AssetTrackingPost;
use App\Model\AssetTrackingPostImage;
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

class AssettrackingImport implements ToModel
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
		if(isset($row[0]) && $row[0]!='Title')
		{
			$customer = User::where('email',$row[27])->first();
			$salesman = User::where('email',$row[28])->first();

			$assettrackinginfo = AssetTracking::where('title',$row[0])->first();
			if(is_object($assettrackinginfo))
			{
				if($skipduplicate == 0)
				{
					$assettrackinginfo->title = $row[0];
					$assettrackinginfo->code = $row[1];
					$assettrackinginfo->description = $row[2];
					$assettrackinginfo->start_date = date('Y-m-d', strtotime(str_replace("/", "-", $row[3])));
					$assettrackinginfo->end_date = date('Y-m-d', strtotime($row[4]));


					$assettrackinginfo->model_name = $row[5];
					$assettrackinginfo->barcode = $row[6];
					$assettrackinginfo->category = $row[7];
					$assettrackinginfo->location = $row[8];
					$assettrackinginfo->lat = $row[9];


					$assettrackinginfo->lng = $row[10];
					$assettrackinginfo->area = $row[11];
					$assettrackinginfo->parent_id = $row[12];
					$assettrackinginfo->wroker = $row[13];
					$assettrackinginfo->additional_wroker = $row[14];

					$assettrackinginfo->team = $row[15];
					$assettrackinginfo->vendors = $row[16];
					$assettrackinginfo->purchase_date = date('Y-m-d', strtotime($row[17]));
					$assettrackinginfo->placed_in_service = date('Y-m-d', strtotime($row[18]));
					$assettrackinginfo->purchase_price = $row[19];
				
					$assettrackinginfo->warranty_expiration = date('Y-m-d', strtotime($row[20]));
					$assettrackinginfo->residual_price = date('Y-m-d', strtotime($row[21]));
					$assettrackinginfo->additional_information = $row[22];
					$assettrackinginfo->useful_life = date('Y-m-d', strtotime($row[23]));
					$assettrackinginfo->image = $row[24];
				
					
					$assettrackinginfo->customer_id = (is_object($customer))?$customer->id:0;
					$assettrackinginfo->save();
				}
			}
			else
			{
				$assettrackinginfo = new AssetTracking;
				$assettrackinginfo->title = $row[0];
				$assettrackinginfo->code = $row[1];
				$assettrackinginfo->description = $row[2];
				$assettrackinginfo->start_date = date('Y-m-d', strtotime(str_replace("/", "-", $row[3])));
				$assettrackinginfo->end_date = date('Y-m-d', strtotime($row[4]));


				$assettrackinginfo->model_name = $row[5];
				$assettrackinginfo->barcode = $row[6];
				$assettrackinginfo->category = $row[7];
				$assettrackinginfo->location = $row[8];
				$assettrackinginfo->lat = $row[9];


				$assettrackinginfo->lng = $row[10];
				$assettrackinginfo->area = $row[11];
				$assettrackinginfo->parent_id = $row[12];
				$assettrackinginfo->wroker = $row[13];
				$assettrackinginfo->additional_wroker = $row[14];

				$assettrackinginfo->team = $row[15];
				$assettrackinginfo->vendors = $row[16];
				$assettrackinginfo->purchase_date = date('Y-m-d', strtotime($row[17]));
				$assettrackinginfo->placed_in_service = date('Y-m-d', strtotime($row[18]));
				$assettrackinginfo->purchase_price = $row[19];
			
				$assettrackinginfo->warranty_expiration = date('Y-m-d', strtotime($row[20]));
				$assettrackinginfo->residual_price = date('Y-m-d', strtotime($row[21]));
				$assettrackinginfo->additional_information = $row[22];
				$assettrackinginfo->useful_life = date('Y-m-d', strtotime($row[23]));
				$assettrackinginfo->image = $row[24];
			
				
				$assettrackinginfo->customer_id = (is_object($customer))?$customer->id:0;
				$assettrackinginfo->save();
			}

			$AssetTrackingPosts = new AssetTrackingPost;
			$AssetTrackingPosts->salesman_id = (is_object($salesman))?$salesman->id:0;
            $AssetTrackingPosts->asset_tracking_id = $assettrackinginfo->id;
            $AssetTrackingPosts->feedback = $row[25];
		    $AssetTrackingPosts->save();


			$AssetTrackingPostImage = new AssetTrackingPostImage;
			$AssetTrackingPostImage->asset_tracking_id = $assettrackinginfo->id;
			$AssetTrackingPostImage->asset_tracking_post_id  = $AssetTrackingPosts->id;
            $AssetTrackingPostImage->image_string = $row[26];
		    $AssetTrackingPostImage->save();
	
			return $assettrackinginfo;
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
			'8' => 'required',
			'9' => 'required',
			'10' => 'required',
			'11' => 'required',
			'12' => 'required',
			'13' => 'required',
			'14' => 'required',
			'15' => 'required',
			'16' => 'required',
			'17' => 'required',
			'18' => 'required',
			'19' => 'required',
			'20' => 'required',
			'21' => 'required',
			'22' => 'required',
			'23' => 'required',
			'24' => 'required',
			'25' => 'required',
			'26' => 'required',
			'27' => 'required',
			'28' => 'required',
        ];
    }
    public function customValidationMessages()
	{
    	return [
			'0.required' => 'Title required',
			'1.required' => 'Code required',
			'2.required' => 'Description required',
			'3.required' => 'Start date required',
			'4.required' => 'End date required',
			'5.required' => 'Model name required',
			'6.required' => 'Barcode required',
			'7.required' => 'Category required',
			'8.required' => 'Location required',
			'9.required' => 'Latitute required',
			'10.required' => 'Langitute required',
			'11.required' => 'Area required',
			'12.required' => 'Parent id required',
			'13.required' => 'Wroker required',
			'14.required' => 'Additional wroker required',
			'15.required' => 'Team required',
			'16.required' => 'Vendors required',
			'17.required' => 'Purchase date required',
			'18.required' => 'Placed in service required',
			'19.required' => 'Purchase price required',
			'20.required' => 'Warrenty expiration required',
			'21.required' => 'Residual price required',
			'22.required' => 'Additional information required',
			'23.required' => 'Useful life required',
			'24.required' => 'Image required',
			'25.required' => 'Feedback required',
			'26.required' => 'Post image required',
			'27.required' => 'Customer name required',
			'28.required' => 'Salesman name required',
		];
	}
}
