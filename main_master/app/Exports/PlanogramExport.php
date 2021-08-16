<?php

namespace App\Exports;

use App\User;
use App\Model\Planogram;
use App\Model\PlanogramImage;
use App\Model\Distribution;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class PlanogramExport implements FromCollection,WithHeadings
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
		$Planograms = Planogram::select('*');
		if($start_date!='' && $end_date!=''){
			$Planograms = $Planograms->whereBetween('created_at', [$start_date, $end_date]);
		}
        $Planograms = $Planograms->get();
		
		$PlanogramCollection = new Collection();
		if(is_object($Planograms)){
			foreach($Planograms as $Planogram)
			{
				$customer = User::find($Planogram->customer_id);
				$planogramimages = PlanogramImage::where('planogram_id',$Planogram->id)->get();
				if(is_object($planogramimages)):

					foreach($planogramimages as $planogramimage):

						$Distributions = Distribution::find($planogramimage->distribution_id);

						$PlanogramCollection->push((object)[
							'name' => $Planogram->name,
							'customer' => (is_object($customer))?$customer->email:'',
							'start_date' => date('m/d/Y',strtotime($Planogram->start_date)),
							'end_date' =>  date('m/d/Y',strtotime($Planogram->end_date)),
							'status' => $Planogram->status,
							'distribution_id' => (is_object($Distributions))?$Distributions->name:'',
							'image_string' => $planogramimage->image_string
						]);

					endforeach;
				endif;
			}
		}
		return $PlanogramCollection;
    }
	public function headings(): array
    {
        return [
            'Name',
			'Custome email',
			'Start date',
			'End date',
			'Status',
			'Distribution',
			'Image'
        ];
    }
}
