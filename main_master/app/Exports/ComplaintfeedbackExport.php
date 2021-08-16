<?php

namespace App\Exports;

use App\Model\ComplaintFeedback;
use App\Model\ComplaintFeedbackImage;
use App\User;
use App\Model\Item;
use App\Model\Route;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ComplaintfeedbackExport implements FromCollection,WithHeadings
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
		$complaintfeedbacks = ComplaintFeedback::select('*');
		if($start_date!='' && $end_date!=''){
			$complaintfeedbacks = $complaintfeedbacks->whereBetween('created_at', [$start_date, $end_date]);
		}
        $complaintfeedbacks = $complaintfeedbacks->get();

		$EstimationCollection = new Collection();
		if(is_object($complaintfeedbacks)){
			foreach($complaintfeedbacks as $complaintfeedback){
				$salesman = User::find($complaintfeedback->salesman_id);
				$customer = User::find($complaintfeedback->customer_id);
				$route = Route::find($complaintfeedback->route_id);
				$items = Item::where('id',$complaintfeedback->item_id)->first();
				
				$complaintfeedbackimages = ComplaintFeedbackImage::where('complaint_feedback_id',$complaintfeedback->id)->get();
				if(is_object($complaintfeedbackimages)){
					foreach($complaintfeedbackimages as $complaintfeedbackimage){
						
						$EstimationCollection->push((object)[
							'complaint_id' => $complaintfeedback->complaint_id,
							'title' => $complaintfeedback->title,
							'item_id' => (isset($items))?$items['item_name']:'',
							'description' => $complaintfeedback->description,
							'status' => $complaintfeedback->status,
							'customer_id' => (is_object($customer))?$customer->email:'',
							'salesman_id' => (is_object($salesman))?$salesman->email:'',
							'route_id' => (is_object($route))?$route->route_name:'',
							'image_string' => $complaintfeedbackimage->image_string,
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
            'Complaint Id',
			'Title',
			'Item',
			'Description',
			'Status',
			'Customer Name',
			'Salesman Name',
			'Route Name',
			'Image'
        ];
    }
}
