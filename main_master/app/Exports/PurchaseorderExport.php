<?php

namespace App\Exports;

use App\Model\Purchaseorder;
use App\Model\Purchaseorderdetail;
use App\Model\Vendor;
use App\Model\Item;
use App\Model\ItemUom;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class PurchaseorderExport implements FromCollection,WithHeadings
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
		$purchaseorders = Purchaseorder::select('*');
		if($start_date!='' && $end_date!=''){
			$purchaseorders = $purchaseorders->whereBetween('created_at', [$start_date, $end_date]);
		}
        $purchaseorders = $purchaseorders->get();
		
		$PurchaseorderCollection = new Collection();
		if(is_object($purchaseorders)){
			foreach($purchaseorders as $purchaseorder){
				$Purchaseorderdetails = Purchaseorderdetail::where('purchase_order_id',$purchaseorder->id)->get();
				if(is_object($Purchaseorderdetails)){
					foreach($Purchaseorderdetails as $Purchaseorderdetail){
						$vendor = Vendor::find($purchaseorder->vendor_id);
						
						$item = Item::find($Purchaseorderdetail->item_id);
						$itemuom = ItemUom::find($Purchaseorderdetail->item_uom_id);
						
						$PurchaseorderCollection->push((object)[
							'purchase_order' => $purchaseorder->purchase_order,
							'vendor' => (is_object($vendor)) ? $vendor->vender_code:"",
							'reference' => $purchaseorder->reference,
							'purchase_order_date' => $purchaseorder->purchase_order_date,
							'expected_delivery_date' => $purchaseorder->expected_delivery_date,
							'customer_note' => $purchaseorder->customer_note,
							'gross_total' => $purchaseorder->gross_total,
							'vat_total' => $purchaseorder->vat_total,
							'excise_total' => $purchaseorder->excise_total,
							'net_total' => $purchaseorder->net_total,
							'discount_total' => $purchaseorder->discount_total,
							'status' => $purchaseorder->status,
							'item' => (is_object($item)) ? $item->item_name:"",
							'item_uom' => (is_object($itemuom)) ? $itemuom->name:"",
							'qty' => $Purchaseorderdetail->qty,
							'price' => $Purchaseorderdetail->price,
							'discount' => $Purchaseorderdetail->discount,
							'vat' => $Purchaseorderdetail->vat,
							'net' => $Purchaseorderdetail->net,
							'excise' => $Purchaseorderdetail->excise,
							'total' => $Purchaseorderdetail->total,
						]);
					}
				}
			}
		}
		return $PurchaseorderCollection;
    }
	public function headings(): array
    {
        return [
            'Purchase order',
            'Vendor',
			'Reference',
			'Purchase order date',
			'Expected delivery date',
			'Customer note',
			'Gross total',
			'Vat total',
			'Excise total',
			'Net total',
			'Discount total',
			'Status',
			'Item',
			'Item UOM',
			'Qty',
			'Price',
			'Discount',
			'Vat',
			'Net',
			'Excise',
			'Total'
        ];
    }
}
