<?php

namespace App\Exports;

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
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;

class CreditnoteExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $StartDate, $EndDate;
	public function __construct(String  $StartDate, String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
	public function collection()
	{
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;
		$creditnotes = DB::table('credit_notes')
			->join('customer_infos', 'customer_infos.id', '=', 'credit_notes.customer_id', 'left')
			->join('users', 'users.id', '=', 'customer_infos.user_id', 'left')
			//->join('orders', 'orders.id', '=', 'credit_notes.order_id','left')
			->join('invoices', 'invoices.id', '=', 'credit_notes.invoice_id', 'left')
			//->join('order_types', 'order_types.id', '=', 'credit_notes.delivery_type','left')
			->join('payment_terms', 'payment_terms.id', '=', 'credit_notes.payment_term_id', 'left')
			->join('credit_note_details', 'credit_note_details.credit_note_id', '=', 'credit_notes.id', 'left')
			->join('items', 'items.id', '=', 'credit_note_details.item_id', 'left')
			->join('item_uoms', 'item_uoms.id', '=', 'credit_note_details.item_uom_id', 'left')
			->join('price_disco_promo_plans', 'price_disco_promo_plans.id', '=', 'credit_note_details.promotion_id', 'left')
			->select(
				'credit_notes.credit_note_number',
				/* 'users.email', */
				'invoices.invoice_number',
				'credit_notes.salesman_id',
				'credit_notes.credit_note_date',
				'payment_terms.name as payment_term',
				'credit_notes.total_qty',
				'credit_notes.total_gross',
				'credit_notes.total_discount_amount',
				'credit_notes.total_net',
				'credit_notes.total_vat',
				'credit_notes.total_excise',
				'credit_notes.grand_total',
				'credit_notes.status',
				'credit_notes.current_stage',
				'credit_notes.current_stage_comment',
				'items.item_name',
				'credit_note_details.item_condition',
				'item_uoms.name as item_uom',
				/* 'credit_note_details.discount_id', */
				'credit_note_details.is_free',
				'credit_note_details.is_item_poi',
				/* 'price_disco_promo_plans.name as price_disco_promo_plan', */
				'credit_note_details.item_qty',
				'credit_note_details.item_price',
				'credit_note_details.item_gross',
				'credit_note_details.item_discount_amount',
				'credit_note_details.item_net',
				'credit_note_details.item_vat',
				'credit_note_details.item_excise',
				'credit_note_details.item_grand_total',
				'credit_note_details.batch_number',
				'credit_note_details.reason'
			);

		if ($start_date != '' && $end_date != '') {
			$creditnotes = $creditnotes->whereBetween('created_at', [$start_date, $end_date]);
		}
		$creditnotes = $creditnotes->where('credit_notes.organisation_id', auth()->user()->organisation_id);
		$creditnotes = $creditnotes->get(); 

		if (is_object($creditnotes)) {
			foreach ($creditnotes as $key => $creditnote) {
				/* $SalesmanInfo = SalesmanInfo::find($creditnote->salesman_id);
				if (is_object($SalesmanInfo)) { */
					$User = User::find($creditnote->salesman_id);
					if (is_object($User)) {
						$creditnotes[$key]->salesman_id =$User->firstname.' '.$User->lastname;
					} else {
						$creditnotes[$key]->salesman_id = '';
					}
				/* } else {
					$creditnotes[$key]->salesman_id = '';
				} */

				/* $PriceDiscoPromoPlan = PriceDiscoPromoPlan::find($creditnote->discount_id);
				if (is_object($PriceDiscoPromoPlan)) {
					$creditnotes[$key]->discount_id = $PriceDiscoPromoPlan->name;
				} else {
					$creditnotes[$key]->discount_id = "";
				} */
			}
		}
	// echo "<br>"; print_r($creditnotes); exit;
		return $creditnotes;
	}

	public function headings(): array
	{
		return [ 
			"Credit Note Number",
			/* "Email", */
			"Invoice Number",
			"Salesman Name",
			"Credit Note Date",
			"Payment Term",
			"Total Qty",
			"Total Gross",
			"Total Discount Amount",
			"Total Net",
			"Total Vat",
			"Total Excise",
			"Grand Total",
			"Status",
			"Current Stage",
			"Current Stage Comment",
			"Item Name",
			"Item Condition",
			"Item Uom",
			/* "Discount Id", */
			"Is Free",
			"Is Item Poi",
			/* "Price Disco Promo plan", */
			"Item Qty",
			"Item Price",
			"Item Gross",
			"Item Discount Amount",
			"Item Net",
			"Item Vat",
			"Item excise",
			"Item grand total",
			"Batch number",
			"Reason",
		];
	}
}
