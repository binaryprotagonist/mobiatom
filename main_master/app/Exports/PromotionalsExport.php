<?php

namespace App\Exports;

use App\Model\Promotional;
use App\Model\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class PromotionalsExport implements FromCollection,WithHeadings
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
		$promotionals = Promotional::select('*');
		if($start_date!='' && $end_date!='')
		{
			$promotionals = $promotionals->whereBetween('created_at', [$start_date, $end_date]);
		}
        $promotionals = $promotionals->get();

		$EstimationCollection = new Collection();
		if(is_object($promotionals))
		{
			foreach($promotionals as $promotional)
			{
				$items = Item::find($promotional->item_id);
				$EstimationCollection->push((object)[
					'item_id' => (is_object($items))?$items->item_name:'',
					'amount' => $promotional->amount,
					'start_date' => date('m/d/Y',strtotime($promotional->start_date)),
					'end_date' => date('m/d/Y',strtotime($promotional->end_date))
				]);
			}
		}
		return $EstimationCollection;
    }
	public function headings(): array
    {
        return [
            'Item',
			'Amount',
			'Satrt Date',
			'End Date'
        ];
    }
}
