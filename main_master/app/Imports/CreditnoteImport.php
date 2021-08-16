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
use App\Model\PriceDiscoPromoPlan;
use Maatwebsite\Excel\Concerns\ToModel;

class CreditnoteImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Credit Note Number'){
			$creditnote_id = 0;
			$creditnote_exist = CreditNote::where('credit_note_number',$row[0])->first();
			if(is_object($creditnote_exist)){
				$creditnote_id = $creditnote_exist->id;
				$creditnote = $creditnote_exist;
			}else{
				$user = User::where('email',$row[1])->first();
				if(is_object($user)){
					$CustomerInfo = CustomerInfo::where('user_id',$user->id)->first();
					if(is_object($CustomerInfo)){
						$customer_id = $CustomerInfo->id;
					}else{
						$customer_id = 0;
					}
				}else{
					$customer_id = 0;
				}
				
				$Invoice = Invoice::where('invoice_number',$row[2])->first();
				
				$user1 = User::where('email',$row[3])->first();
				if(is_object($user1)){
					$SalesmanInfo = SalesmanInfo::where('user_id',$user1->id)->first();
					if(is_object($SalesmanInfo)){
						$salesman_id = $SalesmanInfo->id;
					}else{
						$salesman_id = 0;
					}
				}else{
					$salesman_id = 0;
				}
				
				$PaymentTerm = PaymentTerm::where('name',$row[5])->first();
				
				$creditnote = new CreditNote;
				$creditnote->customer_id         = $customer_id;
				$creditnote->invoice_id        = (is_object($Invoice))?$Invoice->id:0;
				$creditnote->salesman_id          = $salesman_id;
				$creditnote->credit_note_date       = date('Y-m-d',strtotime($row[4]));
				$creditnote->credit_note_number     = $row[0];
				$creditnote->payment_term_id            = (is_object($PaymentTerm))?$PaymentTerm->id:0;
				$creditnote->total_qty           = $row[6];
				$creditnote->total_gross         = $row[7];
				$creditnote->total_discount_amount   = $row[8];
				$creditnote->total_net           = $row[9];
				$creditnote->total_vat           = $row[10];
				$creditnote->total_excise        = $row[11];
				$creditnote->grand_total         = $row[12];
				$creditnote->status       = $row[13];
				$creditnote->current_stage       = $row[14];
				$creditnote->current_stage_comment         = $row[15];
				$creditnote->source         = 2;
				$creditnote->reason         = "";
				$creditnote->save();
				$creditnote_id = $creditnote->id;
			}	
			
				$item = Item::where('item_name',$row[16])->first();
				$item_uom = ItemUom::where('name',$row[18])->first();
				$PriceDiscoPromoPlan = PriceDiscoPromoPlan::where('name',$row[19])->first();
				$PriceDiscoPromoPlan2 = PriceDiscoPromoPlan::where('name',$row[22])->first();
				
				$creditnoteDetail = new CreditNoteDetail;
                $creditnoteDetail->credit_note_id      = $creditnote_id;
                $creditnoteDetail->item_id       = (is_object($item))?$item->id:0;
                $creditnoteDetail->item_condition   = ($row[17]=='Good')?1:2;
                $creditnoteDetail->item_uom_id   = (is_object($item_uom))?$item_uom->id:0;
                $creditnoteDetail->discount_id       = (is_object($PriceDiscoPromoPlan))?$PriceDiscoPromoPlan->id:0;
                $creditnoteDetail->is_free       = $row[20];
                $creditnoteDetail->is_item_poi   = $row[21];
                $creditnoteDetail->promotion_id  = (is_object($PriceDiscoPromoPlan2))?$PriceDiscoPromoPlan2->id:0;;
                $creditnoteDetail->item_qty      = $row[23];
                $creditnoteDetail->item_price    = $row[24];
                $creditnoteDetail->item_gross    = $row[25];
                $creditnoteDetail->item_discount_amount = $row[26];
                $creditnoteDetail->item_net      = $row[27];
                $creditnoteDetail->item_vat      = $row[28];
                $creditnoteDetail->item_excise   = $row[29];
                $creditnoteDetail->item_grand_total = $row[30];
                $creditnoteDetail->batch_number = $row[31];
                $creditnoteDetail->reason = $row[32]; 
                $creditnoteDetail->save();
				return $creditnote;
		}
    }
}
