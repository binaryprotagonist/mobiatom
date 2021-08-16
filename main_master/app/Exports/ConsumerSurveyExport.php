<?php

namespace App\Exports;

use App\Model\Survey;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ConsumerSurveyExport implements FromCollection,WithHeadings
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
		$campaignpictures = CampaignPicture::select('*');
		if($start_date!='' && $end_date!=''){
			$campaignpictures = $campaignpictures->whereBetween('created_at', [$start_date, $end_date]);
		}
        $campaignpictures = $campaignpictures->get();

		$EstimationCollection = new Collection();
		if(is_object($campaignpictures))
		{
			foreach($campaignpictures as $campaignpicture)
			{
				$salesman = User::find($campaignpicture->salesman_id);
				$customer = User::find($campaignpicture->customer_id);
		
				$campaignpictureimages = CampaignPictureImage::where('id_campaign_picture',$campaignpicture->id)->get();
				if(is_object($campaignpictureimages)){
					foreach($campaignpictureimages as $campaignpictureimage){
						
						$EstimationCollection->push((object)[
							'campaign_id' => $campaignpicture->campaign_id,
							'feedback' => $campaignpicture->feedback,
							'customer_id' => (is_object($customer))?$customer->email:'',
							'salesman_id' => (is_object($salesman))?$salesman->email:'',
							'image_string' => $campaignpictureimage->image_string
						]);
					}
				}
			}
		}
		return $EstimationCollection;
    }
	public function headings(): array
    {
        return [
            'Campaign Id',
			'Feedback',
			'Customer Name',
			'Salesman Name',
			'Image'
        ];
    }
}
