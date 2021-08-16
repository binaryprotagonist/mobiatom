<?php

namespace App\Exports;

use App\Model\Estimation;
use App\Model\EstimationDetail;
use App\User;
use App\Model\SalesPerson;
use App\Model\Item;
use App\Model\ItemUom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class EstimationExport implements FromCollection,WithHeadings
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
		$Estimations = Estimation::select('*');
		if($start_date!='' && $end_date!=''){
			$Estimations = $Estimations->whereBetween('created_at', [$start_date, $end_date]);
		}
        $Estimations = $Estimations->get();
		
		$EstimationCollection = new Collection();
		if(is_object($Estimations)){
			foreach($Estimations as $Estimation){
				$EstimationDetails = EstimationDetail::where('estimation_id',$Estimation->id)->get();
				if(is_object($EstimationDetails)){
					foreach($EstimationDetails as $EstimationDetail){
						$SalesPerson = SalesPerson::find($Estimation->salesperson_id);
						$customer = User::find($Estimation->customer_id);
						
						$item = Item::find($EstimationDetail->item_id);
						$itemuom = ItemUom::find($EstimationDetail->item_uom_id);
						
						$EstimationCollection->push((object)[
							'estimate_code' => $Estimation->estimate_code,
							'estimate_date' => $Estimation->estimate_date,
							'expairy_date' => $Estimation->expairy_date,
							'reference' => $Estimation->reference,
							'SalesPerson' => (is_object($SalesPerson))?$SalesPerson->email:'',
							'customer' => (is_object($customer))?$customer->email:'',
							'subject' => $Estimation->subject,
							'customer_note' => $Estimation->customer_note,
							'gross_total' => $Estimation->gross_total,
							'vat' => $Estimation->vat,
							'exise' => $Estimation->exise,
							'net_total' => $Estimation->net_total,
							'discount' => $Estimation->discount,
							'total' => $Estimation->total,
							'status' => $Estimation->status,
							'item' => (is_object($item)) ? $item->item_name:"",
							'item_uom' => (is_object($itemuom)) ? $itemuom->name:"",
							'item_qty' => $EstimationDetail->item_qty ,
							'item_price' => $EstimationDetail->item_price,
							'item_discount_amount' => $EstimationDetail->item_discount_amount,
							'item_vat' => $EstimationDetail->item_vat,
							'item_excise' => $EstimationDetail->item_excise,
							'item_grand_total' => $EstimationDetail->item_grand_total,
							'item_net' => $EstimationDetail->item_net,
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
            'Estimate code',
			'Estimate date',
			'Expairy date',
			'Reference',
			'Sales Person',
			'Customer',
			'Subject',
			'Customer note',
			'Gross total',
			'Vat',
			'Exise',
			'Net total',
			'Discount',
			'Total',
			'Status',
			'Item',
			'Item UOM',
			'Item qty',
			'Item price',
			'Item discount amount',
			'Item vat',
			'Item excise',
			'Item grand total',
			'Item net'
        ];
    }
}
