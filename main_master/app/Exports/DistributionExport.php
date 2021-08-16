<?php

namespace App\Exports;

use App\Model\Distribution;
use App\Model\DistributionCustomer;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class DistributionExport implements FromCollection,WithHeadings
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
		$Distributions = Distribution::select('*');
		if($start_date!='' && $end_date!=''){
			$Distributions = $Distributions->whereBetween('created_at', [$start_date, $end_date]);
		}
        $Distributions = $Distributions->get();
		
		$EstimationCollection = new Collection();
		if(is_object($Distributions)){
			foreach($Distributions as $Distribution){
				$DistributionCustomers = DistributionCustomer::where('distribution_id',$Distribution->id)->get();
				if(is_object($DistributionCustomers)){
					foreach($DistributionCustomers as $DistributionCustomer){
						$customer = User::find($DistributionCustomer->customer_id);
						$EstimationCollection->push((object)[
							'name' => $Distribution->name,
							'start_date' => $Distribution->start_date,
							'end_date' => $Distribution->end_date,
							'height' => $Distribution->height,
							'width' => $Distribution->width,
							'depth' => $Distribution->depth,
							'status' => $Distribution->status,
							'customer' => (is_object($customer))?$customer->email:'',
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
            'Name',
			'Start date',
			'End date',
			'Height',
			'Width',
			'Depth',
			'Status',
			'Customer email'
        ];
    }
}
