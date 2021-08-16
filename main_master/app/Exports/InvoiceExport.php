<?php

namespace App\Exports;

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
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class InvoiceExport implements FromCollection,WithHeadings
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
		$invoices = Invoice::select('*');
		if($start_date!='' && $end_date!=''){
			$invoices = $invoices->whereBetween('created_at', [$start_date, $end_date]);
		}
        $invoices = $invoices->get();
		
		$InvoicesCollection = new Collection();
		if(is_object($invoices)){
			foreach($invoices as $invoice){
				$invoicedetails = InvoiceDetail::where('invoice_id',$invoice->id)->get();
				if(is_object($invoicedetails)){
					foreach($invoicedetails as $invoicedetail){
						$customer = User::find($invoice->customer_id);
						$order = Order::find($invoice->order_id);
						$order_type = OrderType::find($invoice->order_type_id);
						$delivery = Delivery::find($invoice->delivery_id);
						$payment_term = PaymentTerm::find($invoice->payment_term_id);
						
						$item = Item::find($invoicedetail->item_id);
						$itemuom = ItemUom::find($invoicedetail->item_uom_id);
						$discount = PriceDiscoPromoPlan::find($invoicedetail->discount_id);
						$promotion = PriceDiscoPromoPlan::find($invoicedetail->promotion_id);
						
						$invoice_type_name  = "N/A";

						if($invoice->invoice_type==1){
							$invoice_type_name  = 'Invoicing';
						}elseif($invoice->invoice_type ==2){
							$invoice_type_name  ='OTC-Order to Cash';							
						}elseif($invoice->invoice_type == 3){
							$invoice_type_name  = 'DTC Delivery to Cash';							
						}elseif($invoice->invoice_type == 4){
							$invoice_type_name  = 'other';				
						}else{
							$invoice_type_name  = "N/A";						
						}

			
						$InvoicesCollection->push((object)[
							'invoice_number' => $invoice->invoice_number,

							'invoice_type' => $invoice_type_name,

							'invoice_date' => $invoice->invoice_date,
							'invoice_due_date' => $invoice->invoice_due_date,
							'total_qty' => $invoice->total_qty,
							'total_gross' => $invoice->total_gross,
							'total_discount_amount' => $invoice->total_discount_amount,
							'total_net' => $invoice->total_net,
							'total_vat' => $invoice->total_vat,
							'total_excise' => $invoice->total_excise,
							'grand_total' => $invoice->grand_total,
							'status' => $invoice->status,
							//'source' => $invoice->source,
							//'Customer' => (is_object($customer)) ? $customer->email:"",
							'order' => (is_object($order)) ? $order->order_number:"",
							'order_type' => (is_object($order_type)) ? $order_type->name:"",
							'delivery' => (is_object($delivery)) ? $delivery->delivery_number:"",
							'payment_term' => (is_object($payment_term)) ? $payment_term->name:"",
							'item' => (is_object($item)) ? $item->item_name:"",
							'itemuom' => (is_object($itemuom)) ? $itemuom->name:"",
							'discount' => (is_object($discount)) ? $discount->name:"",
							'is_free' => $invoicedetail->is_free,
							'is_item_poi' => $invoicedetail->is_item_poi,
							'promotion' => (is_object($promotion)) ? $promotion->name:"",
							'item_qty' => $invoicedetail->item_qty,
							'item_price' => $invoicedetail->item_price,
							'item_gross' => $invoicedetail->item_gross,
							'item_discount_amount' => $invoicedetail->item_discount_amount,
							'item_net' => $invoicedetail->item_net,
							'item_excise' => $invoicedetail->item_excise,
							'item_grand_total' => $invoicedetail->item_grand_total,
						]);
					}
				}
			}
		}
		return $InvoicesCollection;
    }
	public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Type',
			'Invoice Date',
			'Invoice due date',
			'Total Qty',
			'Total Gross',
			'Total discount amount',
			'Total net',
			'Total vat',
			'Total Excise',
			'Grand Total',
			'Status',
			/* 'Source',
			'Customer Email', */
			'Order Number',
			'Order Type',
			'Delivery Number',
			'Payment Term',
			'Item',
			'Item UOM',
			'Discount',
			'Is Free',
			'Is Item POI',
			'Promotion',
			'Qty',
			'Price',
			'Gross',
			'Discount Amount',
			'Net',
			'Excise',
			'Grand Total',
        ];
    }
}
