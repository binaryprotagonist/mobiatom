<?php

namespace App\Imports;

use App\User;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\Order;
use App\Model\OrderType;
use App\Model\Delivery;
use App\Model\PaymentTerm;
use App\Model\PriceDiscoPromoPlan;
use Maatwebsite\Excel\Concerns\ToModel;

class InvoiceImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Invoice Number'){
			$invoice = Invoice::where('invoice_number',$row[0])->first();
			if(is_object($invoice)){
				$invoice_id = $invoice->id;
			}else{
				$customer = User::where('email',$row[13])->first();
				$order = Order::where('order_number',$row[14])->first();
				$order_type = OrderType::where('name',$row[15])->first();
				$delivery = Delivery::where('delivery_number',$row[16])->first();
				$payment_term = PaymentTerm::where('name',$row[17])->first();
				
				$order_id = (is_object($order)) ? $order->id : 0;
				$delivery_id = (is_object($delivery)) ? $delivery->id : 0;

				if ($row[1] == "2") {
					$order_id = null;
					$delivery_id = null;
				}
				
				$invoice = new Invoice;
				$invoice->invoice_number      = $row[0];
				$invoice->invoice_type        = $row[1];
				$invoice->invoice_date        = date('Y-m-d', strtotime($row[2]));
				$invoice->invoice_due_date    = date('Y-m-d', strtotime($row[3]));
				$invoice->total_qty           = $row[4];
				$invoice->total_gross         = $row[5];
				$invoice->total_discount_amount   = $row[6];
				$invoice->total_net           = $row[7];
				$invoice->total_vat           = $row[8];
				$invoice->total_excise        = $row[9];
				$invoice->grand_total         = $row[10];
				$invoice->status       = $row[11];
				$invoice->source       = $row[12];
				$invoice->customer_id         = (is_object($customer)) ? $customer->id : 0;
				$invoice->order_id            = $order_id;
				$invoice->current_stage       = "Approved";
				$invoice->current_stage_comment = "";
				$invoice->order_type_id       = (is_object($order_type)) ? $order_type->id : 0;
				$invoice->delivery_id         = $delivery_id;
				$invoice->payment_term_id     = (is_object($payment_term)) ? $payment_term->id : 0;
				$invoice->save();
				$invoice_id = $invoice->id;
			}
            
			$item = Item::where('item_name',$row[18])->first();
			$itemuom = ItemUom::where('name',$row[19])->first();
			$discount = PriceDiscoPromoPlan::where('name',$row[20])->first();
			$promotion = PriceDiscoPromoPlan::where('name',$row[23])->first();
			
			$invoiceDetail = new InvoiceDetail;
            $invoiceDetail->invoice_id      = $invoice_id;
            $invoiceDetail->item_id       = (is_object($item)) ? $item->id : 0;
            $invoiceDetail->item_uom_id   = (is_object($itemuom)) ? $itemuom->id : 0;
            $invoiceDetail->discount_id   = (is_object($discount)) ? $discount->id : 0;
            $invoiceDetail->is_free       = $row[21];
            $invoiceDetail->is_item_poi   = $row[22];
            $invoiceDetail->promotion_id  = (is_object($promotion)) ? $promotion->id : 0;
            $invoiceDetail->item_qty      = $row[24];
            $invoiceDetail->item_price    = $row[25];
            $invoiceDetail->item_gross    = $row[26];
            $invoiceDetail->item_discount_amount = $row[27];
            $invoiceDetail->item_net      = $row[28];
            $invoiceDetail->item_excise   = $row[29];
            $invoiceDetail->item_grand_total = $row[30];
            $invoiceDetail->save();
					
			return $itemuom;
		}
    }
}
