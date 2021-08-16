<?php

namespace App\Imports;

use App\Model\CreditNote;
use App\Model\CreditNoteDetail;
use App\Model\Order;
use App\Model\CustomerInfo;
use App\Model\SalesmanInfo;
use App\User;
use App\Model\Invoice;
use App\Model\OrderType;
use App\Model\PaymentTerm;
use App\Model\Item;
use App\Model\ItemUom;
use Maatwebsite\Excel\Concerns\ToModel;

class ItemuomImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Code'){
				$itemuom = new ItemUom;
				$itemuom->code         = $row[0];
				$itemuom->name         = $row[1];
				$itemuom->status       = $row[2];
				$itemuom->save();
				return $itemuom;
		}
    }
}
