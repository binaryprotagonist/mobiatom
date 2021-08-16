<?php

namespace App\Exports;

use App\Model\AssetTracking;
use App\Model\AssetTrackingPost;
use App\Model\AssetTrackingPostImage;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class AssetTrackingExport implements FromCollection,WithHeadings
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
		$assettrackings = AssetTracking::select('*');
		if($start_date!='' && $end_date!='')
		{
			$assettrackings = $assettrackings->whereBetween('created_at', [$start_date, $end_date]);
		}
        $assettrackings = $assettrackings->get();

		$EstimationCollection = new Collection();
		if(is_object($assettrackings))
		{
			foreach($assettrackings as $assettracking)
			{
				$customer = User::find($assettracking->customer_id);
				$assettrackingposts = AssetTrackingPost::where('asset_tracking_id',$assettracking->id)->get();
				if(is_object($assettrackingposts))
				{
					foreach($assettrackingposts as $assettrackingpost)
					{
						$salesman = User::find($assettrackingpost->salesman_id);
						
						$assettrackingpostimages = AssetTrackingPostImage::where('asset_tracking_id',$assettracking->id)->where('asset_tracking_post_id',$assettrackingpost->id)->get();

						if(is_object($assettrackingpostimages)):
							foreach($assettrackingpostimages as $assettrackingpostimage):
							$EstimationCollection->push((object)[
								'title' => $assettracking->title,
								'code' => $assettracking->code,
								'description' => $assettracking->description,
								'start_date' => date('m/d/Y',strtotime($assettracking->start_date)),
								'end_date' => date('m/d/Y',strtotime($assettracking->end_date)),
								'model_name' => $assettracking->model_name,
								'barcode' => $assettracking->barcode,
								'category' => $assettracking->category,
								'location' => $assettracking->location,
								'lat' => $assettracking->lat,
								'lng' => $assettracking->lng,
								'area' => $assettracking->area,
								'parent_id' => $assettracking->parent_id,
								'wroker' => $assettracking->wroker,
								'additional_wroker' => $assettracking->additional_wroker,
								'team' => $assettracking->team,
								'vendors' => $assettracking->vendors,
								'purchase_date' => date('m/d/Y',strtotime($assettracking->purchase_date)),
								'placed_in_service' => date('m/d/Y',strtotime($assettracking->placed_in_service)),
								'purchase_price' => $assettracking->purchase_price,
								'warranty_expiration' => date('m/d/Y',strtotime($assettracking->warranty_expiration)),
								'residual_price' => date('m/d/Y',strtotime($assettracking->residual_price)),
								'additional_information' => $assettracking->additional_information,
								'useful_life' => date('m/d/Y',strtotime($assettracking->useful_life)),
								'image' => $assettracking->image,
								'feedback' => $assettrackingpost->feedback,
								'image_string' => $assettrackingpostimage->image_string,
								'customer_id' => (is_object($customer))?$customer->email:'',
								'salesman_id' => (is_object($salesman))?$salesman->email:''
							]);
							endforeach;
						else:
							$EstimationCollection->push((object)[
							'title' => $assettracking->title,
							'code' => $assettracking->code,
							'description' => $assettracking->description,
							'start_date' => date('m/d/Y',strtotime($assettracking->start_date)),
							'end_date' => date('m/d/Y',strtotime($assettracking->end_date)),
							'model_name' => $assettracking->model_name,
							'barcode' => $assettracking->barcode,
							'category' => $assettracking->category,
							'location' => $assettracking->location,
							'lat' => $assettracking->lat,
							'lng' => $assettracking->lng,
							'area' => $assettracking->area,
							'parent_id' => $assettracking->parent_id,
							'wroker' => $assettracking->wroker,
							'additional_wroker' => $assettracking->additional_wroker,
							'team' => $assettracking->team,
							'vendors' => $assettracking->vendors,
							'purchase_date' => date('m/d/Y',strtotime($assettracking->purchase_date)),
							'placed_in_service' => date('m/d/Y',strtotime($assettracking->placed_in_service)),
							'purchase_price' => $assettracking->purchase_price,
							'warranty_expiration' => date('m/d/Y',strtotime($assettracking->warranty_expiration)),
							'residual_price' => date('m/d/Y',strtotime($assettracking->residual_price)),
							'additional_information' => $assettracking->additional_information,
							'useful_life' => date('m/d/Y',strtotime($assettracking->useful_life)),
							'image' => $assettracking->image_string,
							'feedback' => $assettrackingpost->feedback,
							'image_string' =>'',
							'customer_id' => (is_object($customer))?$customer->email:'',
							'salesman_id' => (is_object($salesman))?$salesman->email:''
						]);
						endif;
					}
				}
			}
		}
		return $EstimationCollection;
    }
	public function headings(): array
    {
        return [
            'Title',
			'Code',
			'Description',
			'Start Date',
			'End Date',
			'Model Name',
			'Bar Code',
			'Category',
			'Location',
			'Latitude',
			'Longitude',
			'Area',
			'Parent Id',
			'Worker',
			'Additional Worker',
			'Team',
			'Vendors',
			'Purchase Date',
			'Placed In Service',
			'Purchase Price',
			'Warrenty Expiration',
			'Residual Price',
			'Additional Information',
			'Useful life',
			'Image',
			'Feedback',
			'Post Image',
			'Customer Name',
			'Salesman Name'
        ];
    }
}
