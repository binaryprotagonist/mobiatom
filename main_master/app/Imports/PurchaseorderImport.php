<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Model\Purchaseorder;
use App\Model\Purchaseorderdetail;
use App\Model\Vendor;
use App\Model\Item;
use App\Model\ItemUom;
use App\User;

class PurchaseorderImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Purchase order'){
			$purchaseorder = Purchaseorder::where('purchase_order',$row[0])->first();
			if(!is_object($purchaseorder)){
				$vendor = Vendor::where('vender_code',$row[1])->first();
				$purchaseorder = new Purchaseorder;
				$purchaseorder->vendor_id            = (is_object($vendor)) ? $vendor->id : 0;
				$purchaseorder->reference            = $row[2];
				$purchaseorder->purchase_order            = $row[0];
				$purchaseorder->purchase_order_date       = date('Y-m-d',strtotime($row[3]));
				$purchaseorder->expected_delivery_date       = date('Y-m-d',strtotime($row[4]));
				$purchaseorder->customer_note            = $row[5];
				$purchaseorder->gross_total            = $row[6];
				$purchaseorder->vat_total            = $row[7];
				$purchaseorder->excise_total            = $row[8];
				$purchaseorder->net_total            = $row[9];
				$purchaseorder->discount_total            = $row[10];
				$purchaseorder->status            = $row[11];
				$purchaseorder->save();
			}
				$item = Item::where('item_name',$row[12])->first();
				$itemuom = ItemUom::where('name',$row[13])->first();
				$purchaseorderdetail = new Purchaseorderdetail;
                $purchaseorderdetail->purchase_order_id      = $purchaseorder->id;
                $purchaseorderdetail->item_id       = (is_object($item))?$item->id:0;
                $purchaseorderdetail->item_uom_id   = (is_object($itemuom))?$itemuom->id:0;
                $purchaseorderdetail->qty   = $row[14];
                $purchaseorderdetail->price       = $row[15];
                $purchaseorderdetail->discount   = $row[16];
                $purchaseorderdetail->vat  = $row[17];
                $purchaseorderdetail->net      = $row[18];
                $purchaseorderdetail->excise    = $row[19];
                $purchaseorderdetail->total    = $row[20];
                $purchaseorderdetail->save();
			
		}
    }
}
